<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Route;
use App\Models\Store;
use App\Models\StoreReceivable;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreReceivableService
{
    public const TYPE_OPENING = 'opening';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const ALLOCATION_RECEIVABLE = 'receivable';
    public const ALLOCATION_CURRENT_PO = 'current_po';
    public const ALLOCATION_MIXED = 'mixed';

    public const METHOD_CASH = 'tunai';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_QRIS = 'qris';

    /**
     * Generate a globally unique, sequential transaction code.
     * Format: TRX-YYYYMMDD-XXXX (e.g. TRX-20260901-0001)
     */
    public static function generateTransactionCode(?string $date = null): string
    {
        $d = $date ? Carbon::parse($date)->format('Ymd') : now()->format('Ymd');
        $prefix = "TRX-{$d}-";

        return DB::transaction(function () use ($prefix, $d) {
            $latest = StoreTransaction::where('transaction_code', 'like', "{$prefix}%")
                ->orderByDesc('transaction_code')
                ->lockForUpdate()
                ->first();

            $seq = 1;
            if ($latest && preg_match('/-(\d{4})$/', $latest->transaction_code, $matches)) {
                $seq = (int) $matches[1] + 1;
            }

            $code = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            // Safety check against collisions
            while (StoreTransaction::where('transaction_code', $code)->exists()) {
                $seq++;
                $code = $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }

            return $code;
        });
    }

    /**
     * Calculate total receivable balance for a given store by aggregating all unpaid transactions.
     * Also factors in legacy store_receivables rows for backward compatibility.
     */
    public static function balanceForStore(string $storeId): string
    {
        // 1. If store has transactions in the transaction-based ledger, that is the source of truth
        $hasTransactions = StoreTransaction::where('store_id', $storeId)->exists();

        if ($hasTransactions) {
            $trxTotal = (float) StoreTransaction::where('store_id', $storeId)->sum('transaction_amount');
            $trxPaid = (float) StoreTransactionPayment::whereHas('transaction', fn ($q) => $q->where('store_id', $storeId))->sum('amount');
            $transactionRemaining = max(0.0, round($trxTotal - $trxPaid, 2));

            return number_format($transactionRemaining, 2, '.', '');
        }

        // 2. Legacy store_receivables fallback if no StoreTransaction records exist
        $legacySum = (float) StoreReceivable::where('store_id', $storeId)
            ->selectRaw("SUM(CASE WHEN type IN ('opening','invoice','adjustment') THEN amount WHEN type='payment' THEN -amount ELSE 0 END) as bal")
            ->value('bal') ?? 0.0;

        return number_format(max(0.0, round($legacySum, 2)), 2, '.', '');
    }

    /**
     * Calculate historical receivable balance for a given store as of a specific date/time.
     * Pure historical calculation based on ledger transactions and payments up to $asOfDate/$asOfTime.
     */
    public static function historicalBalanceForStore(string $storeId, Carbon|string|null $asOfDate = null, Carbon|string|null $asOfTime = null): float
    {
        if (! $storeId) {
            return 0.0;
        }

        $targetDate = $asOfDate instanceof Carbon ? $asOfDate->toDateString() : ($asOfDate ? Carbon::parse($asOfDate)->toDateString() : null);
        $targetTime = $asOfTime ? ($asOfTime instanceof Carbon ? $asOfTime : Carbon::parse($asOfTime)) : null;

        $hasTransactions = StoreTransaction::where('store_id', $storeId)->exists();

        if ($hasTransactions) {
            $storeTransactions = StoreTransaction::where('store_id', $storeId)->with('payments')->get();
            $balance = 0.0;

            foreach ($storeTransactions as $tx) {
                $txDate = $tx->transaction_date ? $tx->transaction_date->toDateString() : ($tx->created_at ? $tx->created_at->toDateString() : null);

                if ($targetDate && $txDate && $txDate > $targetDate) {
                    continue;
                }
                if ($targetDate && $txDate && $txDate === $targetDate && $targetTime && $tx->created_at && $tx->created_at > $targetTime) {
                    continue;
                }
                if (! $targetDate && $targetTime && $tx->created_at && $tx->created_at > $targetTime) {
                    continue;
                }

                $txAmount = (float) $tx->transaction_amount;
                $paid = 0.0;
                $txPayments = $tx->relationLoaded('payments') ? $tx->payments : $tx->payments()->get();

                foreach ($txPayments as $p) {
                    $pDate = $p->payment_date ? $p->payment_date->toDateString() : ($p->created_at ? $p->created_at->toDateString() : null);

                    if ($targetDate && $pDate && $pDate > $targetDate) {
                        continue;
                    }
                    if ($targetDate && $pDate && $pDate === $targetDate && $targetTime && $p->created_at && $p->created_at > $targetTime) {
                        continue;
                    }
                    if (! $targetDate && $targetTime && $p->created_at && $p->created_at > $targetTime) {
                        continue;
                    }

                    $paid += (float) $p->amount;
                }

                $txOutstanding = max(0.0, round($txAmount - $paid, 2));
                $balance += $txOutstanding;
            }

            return max(0.0, round($balance, 2));
        }

        // Fallback legacy store_receivables
        $legacySum = (float) StoreReceivable::where('store_id', $storeId)
            ->when($targetDate, fn ($q) => $q->where(function ($sub) use ($targetDate, $targetTime) {
                $sub->whereDate('transaction_date', '<', $targetDate)
                    ->orWhere(function ($qq) use ($targetDate, $targetTime) {
                        $qq->whereDate('transaction_date', $targetDate)
                            ->when($targetTime, fn ($q3) => $q3->where('created_at', '<=', $targetTime));
                    });
            }))
            ->selectRaw("SUM(CASE WHEN type IN ('opening','invoice','adjustment') THEN amount WHEN type='payment' THEN -amount ELSE 0 END) as bal")
            ->value('bal') ?? 0.0;

        return max(0.0, round($legacySum, 2));
    }

    /**
     * Create a new StoreTransaction with auto-generated transaction_code.
     * Includes automatic retry mechanism against concurrency collisions.
     * Optionally records an initial payment in the same atomic transaction.
     */
    public static function createTransaction(array $data, ?User $actor = null): StoreTransaction
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                return DB::transaction(function () use ($data, $actor) {
                    $store = Store::findOrFail($data['store_id']);

                    if ($store->status !== 'active') {
                        throw ValidationException::withMessages(['store_id' => 'Toko tidak aktif.']);
                    }

                    $amount = (string) $data['transaction_amount'];
                    if (! is_numeric($amount) || (float) $amount <= 0) {
                        throw ValidationException::withMessages(['transaction_amount' => 'Nominal transaksi harus lebih besar dari 0.']);
                    }

                    $txDate = $data['transaction_date'] ?? now()->toDateString();
                    $code = $data['transaction_code'] ?? self::generateTransactionCode($txDate);

                    $transaction = StoreTransaction::create([
                        'store_id' => $store->id,
                        'transaction_code' => $code,
                        'transaction_date' => $txDate,
                        'description' => $data['description'] ?? null,
                        'transaction_amount' => $amount,
                        'status' => StoreTransaction::STATUS_BELUM_LUNAS,
                        'reference_type' => $data['reference_type'] ?? null,
                        'reference_id' => $data['reference_id'] ?? null,
                        'created_by' => $actor?->id ?? $data['created_by'] ?? null,
                    ]);

                    // If an initial payment amount is provided upon creation
                    $initialPaid = (float) ($data['paid_amount'] ?? 0);
                    if ($initialPaid > 0) {
                        if ($initialPaid > (float) $amount) {
                            throw ValidationException::withMessages(['paid_amount' => 'Pembayaran awal tidak boleh melebihi total transaksi.']);
                        }

                        $method = $data['payment_method'] ?? self::METHOD_CASH;
                        StoreTransactionPayment::create([
                            'store_transaction_id' => $transaction->id,
                            'amount' => $initialPaid,
                            'payment_method' => $method,
                            'payment_date' => $txDate,
                            'recorded_by' => $actor?->id ?? $data['created_by'] ?? null,
                            'source' => $data['reference_type'] ?? 'initial_payment',
                            'source_id' => $data['reference_id'] ?? null,
                            'notes' => 'Pembayaran awal saat transaksi dibuat',
                        ]);

                        $transaction->recalculateStatus();
                    }

                    // Sync to legacy store_receivables for backward-compatibility with existing views/reports
                    StoreReceivable::create([
                        'store_id' => $store->id,
                        'type' => self::TYPE_INVOICE,
                        'amount' => $amount,
                        'reference_type' => 'store_transaction',
                        'reference_id' => $transaction->id,
                        'transaction_date' => $txDate,
                        'notes' => $data['description'] ?? "Transaksi {$code}",
                        'created_by' => $actor?->id ?? $data['created_by'] ?? null,
                    ]);

                    if ($initialPaid > 0) {
                        StoreReceivable::create([
                            'store_id' => $store->id,
                            'type' => self::TYPE_PAYMENT,
                            'amount' => $initialPaid,
                            'reference_type' => 'store_transaction_payment',
                            'reference_id' => $transaction->id,
                            'transaction_date' => $txDate,
                            'payment_method' => $data['payment_method'] ?? self::METHOD_CASH,
                            'allocation' => self::ALLOCATION_CURRENT_PO,
                            'notes' => "Pembayaran transaksi {$code}",
                            'created_by' => $actor?->id ?? $data['created_by'] ?? null,
                        ]);
                    }

                    return $transaction;
                });
            } catch (\Illuminate\Database\UniqueConstraintViolationException|\Illuminate\Database\QueryException $e) {
                // If collision on explicit custom code, rethrow immediately
                if (! empty($data['transaction_code']) || $attempt >= $maxAttempts) {
                    throw $e;
                }
                // Sleep tiny microsecond before retry to let concurrent transaction finish
                usleep(10000);
            }
        }

        throw new \RuntimeException('Gagal membuat kode transaksi unik setelah beberapa percobaan.');
    }

    /**
     * Record a payment allocated to a specific StoreTransaction.
     * Validates payment amount against remaining balance of the transaction.
     */
    public static function recordTransactionPayment(array $data, User $actor): StoreTransactionPayment
    {
        return DB::transaction(function () use ($data, $actor) {
            $transaction = StoreTransaction::with('store')->lockForUpdate()->findOrFail($data['store_transaction_id']);
            $store = $transaction->store;

            self::assertCanAccessStore($actor, $store);

            if ($store->trashed() || $store->status !== 'active') {
                throw ValidationException::withMessages(['store_transaction_id' => 'Toko tidak aktif.']);
            }

            $amount = (string) $data['amount'];
            if (! is_numeric($amount) || (float) $amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih besar dari 0.']);
            }

            $remaining = $transaction->remaining_amount;
            if ((float) $amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran melebihi sisa piutang transaksi.',
                ]);
            }

            $method = $data['payment_method'] ?? self::METHOD_CASH;
            if (! in_array($method, [self::METHOD_CASH, self::METHOD_TRANSFER, self::METHOD_QRIS], true)) {
                throw ValidationException::withMessages(['payment_method' => 'Metode pembayaran tidak valid.']);
            }

            $pDate = $data['payment_date'] ?? now()->toDateString();

            $payment = StoreTransactionPayment::create([
                'store_transaction_id' => $transaction->id,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_date' => $pDate,
                'recorded_by' => $actor->id,
                'source' => $data['source'] ?? 'manual_payment',
                'source_id' => $data['source_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $transaction->recalculateStatus();

            // Sync to legacy store_receivables
            StoreReceivable::create([
                'store_id' => $store->id,
                'type' => self::TYPE_PAYMENT,
                'amount' => $amount,
                'reference_type' => 'store_transaction_payment',
                'reference_id' => $payment->id,
                'transaction_date' => $pDate,
                'payment_method' => $method,
                'allocation' => self::ALLOCATION_RECEIVABLE,
                'notes' => $data['notes'] ?? "Pembayaran transaksi {$transaction->transaction_code}",
                'created_by' => $actor->id,
            ]);

            return $payment;
        });
    }

    /**
     * Record a payment from Sales Check-Out or Admin dashboard, allocating automatically or to specific transaction.
     */
    public static function recordPayment(array $data, User $actor): StoreReceivable
    {
        return DB::transaction(function () use ($data, $actor) {
            $store = Store::withTrashed()->findOrFail($data['store_id']);
            self::assertSalesOwnsStore($actor, $store);

            if ($store->trashed() || $store->status !== 'active') {
                throw ValidationException::withMessages(['store_id' => 'Toko tidak aktif.']);
            }

            $amount = (string) $data['amount'];
            if (! is_numeric($amount) || (float) $amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran tidak valid.']);
            }

            $allocation = $data['allocation'] ?? self::ALLOCATION_RECEIVABLE;
            if (! in_array($allocation, [self::ALLOCATION_RECEIVABLE, self::ALLOCATION_CURRENT_PO, self::ALLOCATION_MIXED], true)) {
                throw ValidationException::withMessages(['allocation' => 'Alokasi pembayaran tidak valid.']);
            }

            if ($allocation === self::ALLOCATION_RECEIVABLE) {
                $balance = (float) self::balanceForStore($store->id);
                if ((float) $amount > $balance) {
                    throw ValidationException::withMessages(['amount' => 'Pembayaran melebihi saldo piutang. Sisa saldo Rp' . number_format($balance, 0, ',', '.') . '.']);
                }
            }

            $method = $data['payment_method'] ?? self::METHOD_CASH;
            if (! in_array($method, [self::METHOD_CASH, self::METHOD_TRANSFER, self::METHOD_QRIS], true)) {
                throw ValidationException::withMessages(['payment_method' => 'Metode pembayaran tidak valid.']);
            }

            $pDate = $data['transaction_date'] ?? now()->toDateString();

            // If a specific transaction ID was provided
            if (! empty($data['store_transaction_id'])) {
                self::recordTransactionPayment([
                    'store_transaction_id' => $data['store_transaction_id'],
                    'amount' => $amount,
                    'payment_method' => $method,
                    'payment_date' => $pDate,
                    'source' => $data['reference_type'] ?? 'visit',
                    'source_id' => $data['reference_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ], $actor);
            } else {
                // FIFO Allocation across open transactions of this store
                $remainingToPay = (float) $amount;
                $openTransactions = StoreTransaction::where('store_id', $store->id)
                    ->whereIn('status', [StoreTransaction::STATUS_BELUM_LUNAS, StoreTransaction::STATUS_SEBAGIAN])
                    ->orderBy('transaction_date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($openTransactions as $trx) {
                    if ($remainingToPay <= 0.005) {
                        break;
                    }

                    $trxRemaining = $trx->remaining_amount;
                    if ($trxRemaining <= 0.005) {
                        continue;
                    }

                    $allocAmount = min($remainingToPay, $trxRemaining);

                    StoreTransactionPayment::create([
                        'store_transaction_id' => $trx->id,
                        'amount' => $allocAmount,
                        'payment_method' => $method,
                        'payment_date' => $pDate,
                        'recorded_by' => $actor->id,
                        'source' => $data['reference_type'] ?? 'visit',
                        'source_id' => $data['reference_id'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    $trx->recalculateStatus();
                    $remainingToPay -= $allocAmount;
                }
            }

            return StoreReceivable::create([
                'store_id' => $store->id,
                'type' => self::TYPE_PAYMENT,
                'amount' => $amount,
                'reference_type' => $data['reference_type'] ?? 'visit',
                'reference_id' => $data['reference_id'] ?? null,
                'transaction_date' => $pDate,
                'payment_method' => $method,
                'allocation' => $allocation,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * Add opening receivable balance as a distinct StoreTransaction for auditing.
     */
    public static function addOpeningBalance(array $data, User $actor): StoreReceivable
    {
        if (! $actor->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Hanya Admin atau Super Admin yang berhak memasukkan saldo piutang awal.');
        }

        return DB::transaction(function () use ($data, $actor) {
            $store = Store::findOrFail($data['store_id']);

            if ($store->status !== 'active') {
                throw ValidationException::withMessages(['store_id' => 'Toko tidak aktif.']);
            }

            $amount = (string) $data['amount'];
            if (! is_numeric($amount) || (float) $amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal saldo awal harus lebih besar dari 0.']);
            }

            $txDate = $data['transaction_date'] ?? now()->toDateString();
            $code = self::generateTransactionCode($txDate);
            $notes = $data['notes'] ?? 'Saldo piutang existing perusahaan saat implementasi ISA SmartWork.';

            // Create StoreTransaction record
            StoreTransaction::create([
                'store_id' => $store->id,
                'transaction_code' => $code,
                'transaction_date' => $txDate,
                'description' => $notes,
                'transaction_amount' => $amount,
                'status' => StoreTransaction::STATUS_BELUM_LUNAS,
                'reference_type' => 'opening_balance',
                'reference_id' => null,
                'created_by' => $actor->id,
            ]);

            // Maintain legacy StoreReceivable row
            return StoreReceivable::create([
                'store_id' => $store->id,
                'type' => self::TYPE_OPENING,
                'amount' => $amount,
                'reference_type' => 'opening_balance',
                'reference_id' => null,
                'transaction_date' => $txDate,
                'payment_method' => null,
                'allocation' => null,
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);
        });
    }

    public static function addReceivable(array $data, ?User $actor = null): StoreReceivable
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                return DB::transaction(function () use ($data, $actor) {
                    $store = Store::findOrFail($data['store_id']);

                    if ($store->status !== 'active') {
                        throw ValidationException::withMessages(['store_id' => 'Toko tidak aktif.']);
                    }

                    $amount = (string) $data['amount'];
                    if (! is_numeric($amount) || (float) $amount <= 0) {
                        throw ValidationException::withMessages(['amount' => 'Nominal tidak valid.']);
                    }

                    $txDate = $data['transaction_date'] ?? now()->toDateString();
                    $code = self::generateTransactionCode($txDate);

                    StoreTransaction::create([
                        'store_id' => $store->id,
                        'transaction_code' => $code,
                        'transaction_date' => $txDate,
                        'description' => $data['notes'] ?? 'Tagihan Toko',
                        'transaction_amount' => $amount,
                        'status' => StoreTransaction::STATUS_BELUM_LUNAS,
                        'reference_type' => $data['reference_type'] ?? null,
                        'reference_id' => $data['reference_id'] ?? null,
                        'created_by' => $actor?->id ?? $data['created_by'] ?? null,
                    ]);

                    return StoreReceivable::create([
                        'store_id' => $store->id,
                        'type' => $data['type'] ?? self::TYPE_INVOICE,
                        'amount' => $amount,
                        'reference_type' => $data['reference_type'] ?? null,
                        'reference_id' => $data['reference_id'] ?? null,
                        'transaction_date' => $txDate,
                        'payment_method' => null,
                        'allocation' => $data['allocation'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'created_by' => $actor?->id ?? $data['created_by'] ?? null,
                    ]);
                });
            } catch (\Illuminate\Database\UniqueConstraintViolationException|\Illuminate\Database\QueryException $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                usleep(10000);
            }
        }

        throw new \RuntimeException('Gagal membuat piutang baru setelah beberapa percobaan.');
    }

    public static function assertSalesOwnsStore(User $user, Store $store): void
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return;
        }

        if (! $user->hasRole('sales')) {
            abort(403, 'Akses ditolak.');
        }

        if ((string) $store->sales_penanggung_jawab_id !== (string) $user->id) {
            abort(403, 'Toko bukan tanggung jawab Anda.');
        }
    }

    public static function assertCanAccessStore(User $user, Store $store): void
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return;
        }

        if ($user->hasRole('driver')) {
            abort(403, 'Driver tidak memiliki akses piutang.');
        }

        if ($user->hasRole('sales')) {
            self::assertSalesOwnsStore($user, $store);
            return;
        }

        abort(403, 'Akses ditolak.');
    }

    public static function historyForStore(string $storeId, ?int $limit = null)
    {
        $q = StoreReceivable::where('store_id', $storeId)
            ->with('creator:id,name')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at');

        return $limit ? $q->limit($limit)->get() : $q->get();
    }

    /**
     * Compute financial receivable summary for a specific visit based strictly on the ledger.
     *
     * @return array{
     *   balance_before: float,
     *   old_debt_paid: float,
     *   new_tx_total: float,
     *   new_tx_initial_paid: float,
     *   new_tx_remaining: float,
     *   balance_after: float
     * }
     */
    public static function getVisitReceivableSummary(\App\Models\Visit $visit): array
    {
        $storeId = $visit->store_id;

        if (! $storeId) {
            return [
                'balance_before' => 0.0,
                'old_debt_paid' => 0.0,
                'new_tx_total' => 0.0,
                'new_tx_initial_paid' => 0.0,
                'new_tx_remaining' => 0.0,
                'balance_after' => 0.0,
            ];
        }

        // 1. Pembayaran Piutang Lama pada Kunjungan Ini
        $vPayments = ($visit->payments ?? collect())->filter(fn ($p) =>
            $p->source !== 'initial_payment' && ! str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
        );
        $oldDebtPaid = (float) $vPayments->sum('amount');
        if ($oldDebtPaid <= 0 && in_array($visit->transaction_status, ['piutang', 'mixed'], true) && (float) $visit->cash_received > 0 && ($visit->transactions ?? collect())->isEmpty()) {
            $oldDebtPaid = (float) $visit->cash_received;
        }
        $thisVisitPaymentIds = $vPayments->pluck('id')->all();

        // 2. Transaksi Baru pada Kunjungan Ini (StoreTransaction ledger / fallback visit attribute)
        $vTransactions = $visit->transactions ?? collect();
        $newTxTotal = 0.0;
        $newTxInitialPaid = 0.0;
        $thisVisitTxIds = [];

        if ($vTransactions->isNotEmpty()) {
            foreach ($vTransactions as $tx) {
                $thisVisitTxIds[] = $tx->id;
                $txAmount = (float) $tx->transaction_amount;
                $newTxTotal += $txAmount;
                $txPayments = $tx->payments ?? collect();
                $initPaid = (float) $txPayments->filter(fn ($p) =>
                    $p->source === 'initial_payment' || str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                )->sum('amount');
                $newTxInitialPaid += $initPaid;
            }
        } elseif ((float) $visit->transaction_amount > 0 && in_array($visit->transaction_status, ['paid', 'mixed'], true)) {
            $newTxTotal = (float) $visit->transaction_amount;
            $newTxInitialPaid = $visit->transaction_status === 'paid' ? (float) ($visit->cash_received ?: $newTxTotal) : 0.0;
        }

        $newTxRemaining = max(0.0, round($newTxTotal - $newTxInitialPaid, 2));

        // 3. Saldo Piutang Toko Sebelum Kunjungan
        // Menghitung seluruh sisa piutang toko sebelum aktivitas keuangan pada kunjungan ini dilakukan.
        // Yaitu seluruh transaksi toko sebelum kunjungan (selain transaksi baru yang dibuat visit ini)
        // dikurangi seluruh pembayaran yang telah terjadi sebelum kunjungan ini (selain pembayaran yang dilakukan visit ini).
        $visitDate = $visit->check_in_at ? $visit->check_in_at->toDateString() : ($visit->created_at ? $visit->created_at->toDateString() : null);
        $visitTime = $visit->check_in_at ?? $visit->created_at;

        $hasTransactions = StoreTransaction::where('store_id', $storeId)->exists();

        if ($hasTransactions) {
            $storeTransactions = StoreTransaction::where('store_id', $storeId)->with('payments')->get();
            $balanceBefore = 0.0;

            foreach ($storeTransactions as $tx) {
                // Kecualikan transaksi yang dibuat pada kunjungan ini
                if (in_array($tx->id, $thisVisitTxIds, true) || ($tx->reference_type === 'visit' && (string) $tx->reference_id === (string) $visit->id)) {
                    continue;
                }

                $txDate = $tx->transaction_date ? $tx->transaction_date->toDateString() : ($tx->created_at ? $tx->created_at->toDateString() : null);

                // Kecualikan transaksi yang terjadi setelah kunjungan ini
                if ($visitDate && $txDate && $txDate > $visitDate) {
                    continue;
                }
                if ($visitDate && $txDate && $txDate === $visitDate && $visitTime && $tx->created_at && $tx->created_at > $visitTime) {
                    continue;
                }
                if (! $visitDate && $visitTime && $tx->created_at && $tx->created_at > $visitTime) {
                    continue;
                }

                $txAmount = (float) $tx->transaction_amount;

                // Hitung pembayaran transaksi ini yang terjadi SEBELUM kunjungan ini
                $paidBefore = 0.0;
                $txPayments = $tx->relationLoaded('payments') ? $tx->payments : $tx->payments()->get();

                foreach ($txPayments as $p) {
                    // Kecualikan pembayaran yang dilakukan pada kunjungan ini
                    if (in_array($p->id, $thisVisitPaymentIds, true) || ((string) $p->source_id === (string) $visit->id && $p->source === 'visit')) {
                        continue;
                    }

                    $pDate = $p->payment_date ? $p->payment_date->toDateString() : ($p->created_at ? $p->created_at->toDateString() : null);

                    // Kecualikan pembayaran yang terjadi setelah kunjungan ini
                    if ($visitDate && $pDate && $pDate > $visitDate) {
                        continue;
                    }
                    if ($visitDate && $pDate && $pDate === $visitDate && $visitTime && $p->created_at && $p->created_at > $visitTime) {
                        continue;
                    }
                    if (! $visitDate && $visitTime && $p->created_at && $p->created_at > $visitTime) {
                        continue;
                    }

                    $paidBefore += (float) $p->amount;
                }

                $txOutstandingBefore = max(0.0, round($txAmount - $paidBefore, 2));
                $balanceBefore += $txOutstandingBefore;
            }

            $balanceBefore = max(0.0, round($balanceBefore, 2));
        } else {
            // Fallback legacy store_receivables
            $legacySum = (float) StoreReceivable::where('store_id', $storeId)
                ->when($visitDate, fn ($q) => $q->where(function ($sub) use ($visitDate, $visitTime) {
                    $sub->whereDate('transaction_date', '<', $visitDate)
                        ->orWhere(function ($qq) use ($visitDate, $visitTime) {
                            $qq->whereDate('transaction_date', $visitDate)
                                ->when($visitTime, fn ($q3) => $q3->where('created_at', '<=', $visitTime));
                        });
                }))
                ->selectRaw("SUM(CASE WHEN type IN ('opening','invoice','adjustment') THEN amount WHEN type='payment' THEN -amount ELSE 0 END) as bal")
                ->value('bal') ?? 0.0;

            $balanceBefore = max(0.0, round($legacySum, 2));
        }

        // 4. Saldo Piutang Toko Setelah Kunjungan
        // Hubungan fundamental ledger:
        // SaldoSetelah = SaldoSebelum - PembayaranPiutangLama + TransaksiBaruMenjadiPiutang
        $balanceAfter = max(0.0, round($balanceBefore - $oldDebtPaid + $newTxRemaining, 2));

        return [
            'balance_before' => $balanceBefore,
            'old_debt_paid' => $oldDebtPaid,
            'new_tx_total' => $newTxTotal,
            'new_tx_initial_paid' => $newTxInitialPaid,
            'new_tx_remaining' => $newTxRemaining,
            'balance_after' => $balanceAfter,
        ];
    }

    /**
     * Compute performance and financial breakdown for a collection of Sales users.
     *
     * @param iterable $salesUsers
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array{
     *   performance: array<int, array{
     *      id: int,
     *      name: string,
     *      attendance: int,
     *      routes: int,
     *      completed_routes: int,
     *      completion: int,
     *      visits: int,
     *      completed_visits: int,
     *      new_tx_amount: float,
     *      new_tx_paid: float,
     *      old_debt_paid: float,
     *      total_cash_in: float,
     *      transaction: float
     *   }>,
     *   summary: array{
     *      total_sales: int,
     *      total_attendance: int,
     *      total_routes: int,
     *      total_completed_routes: int,
     *      total_visits: int,
     *      total_completed_visits: int,
     *      avg_completion: int,
     *      total_new_tx_amount: float,
     *      total_new_tx_paid: float,
     *      total_old_debt_paid: float,
     *      total_cash_in: float,
     *      total_transaction: float
     *   }
     * }
     */
    public static function getSalesActivitiesPerformanceData($salesUsers, ?string $fromDate = null, ?string $toDate = null, ?string $archiveId = null): array
    {
        $performance = [];
        $totalSales = 0;
        $totalAttendance = 0;
        $totalRoutes = 0;
        $totalCompletedRoutes = 0;
        $totalVisits = 0;
        $totalCompletedVisits = 0;
        $totalNewTxAmount = 0.0;
        $totalNewTxPaid = 0.0;
        $totalOldDebtPaid = 0.0;

        foreach ($salesUsers as $user) {
            $totalSales++;

            // 1. Presensi
            if ($archiveId) {
                $attendanceQuery = Attendance::withoutGlobalScope('notArchived')
                    ->where('user_id', $user->id)
                    ->where('status', '!=', 'canceled')
                    ->where('data_archive_id', $archiveId);
            } else {
                $attendanceQuery = Attendance::where('user_id', $user->id)
                    ->where('status', '!=', 'canceled');
                if ($fromDate && $toDate) {
                    $attendanceQuery->whereBetween('date', [$fromDate, $toDate]);
                }
            }
            $attendance = $attendanceQuery->count();

            // 2. Rencana Rute
            if ($archiveId) {
                $routesQuery = Route::withTrashed()->withoutGlobalScope('notArchived')
                    ->where('user_id', $user->id)
                    ->where('data_archive_id', $archiveId);
                $completedRoutesQuery = Route::withTrashed()->withoutGlobalScope('notArchived')
                    ->with(['stops.visit'])
                    ->where('user_id', $user->id)
                    ->where('data_archive_id', $archiveId);
            } else {
                $routesQuery = Route::where('user_id', $user->id);
                if ($fromDate && $toDate) {
                    $routesQuery->whereBetween('date', [$fromDate, $toDate]);
                }
                $completedRoutesQuery = Route::with(['stops.visit'])
                    ->where('user_id', $user->id);
                if ($fromDate && $toDate) {
                    $completedRoutesQuery->whereBetween('date', [$fromDate, $toDate]);
                }
            }
            $routes = $routesQuery->count();
            $completedRoutes = $completedRoutesQuery->get()
                ->filter(fn ($r) => $r->computed_status === 'completed')
                ->count();

            $completion = $routes > 0 ? round(($completedRoutes / $routes) * 100) : 0;

            // 3. Kunjungan
            if ($archiveId) {
                $visitsQuery = Visit::withoutGlobalScope('notArchived')
                    ->where('user_id', $user->id)
                    ->where('data_archive_id', $archiveId);
                $completedVisitsQuery = Visit::withoutGlobalScope('notArchived')
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->where('data_archive_id', $archiveId);
            } else {
                $visitsQuery = Visit::where('user_id', $user->id);
                if ($fromDate && $toDate) {
                    $visitsQuery->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                }
                $completedVisitsQuery = Visit::where('user_id', $user->id)
                    ->where('status', 'completed');
                if ($fromDate && $toDate) {
                    $completedVisitsQuery->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                }
            }
            $visits = $visitsQuery->count();
            $completedVisits = $completedVisitsQuery->count();

            // 4. Financial Breakdown
            // Toko yang menjadi tanggung jawab Sales ini
            $salesStoreIds = Store::withTrashed()
                ->where('sales_penanggung_jawab_id', $user->id)
                ->pluck('id');

            // A. TOTAL NILAI TRANSAKSI BARU (Nilai invoice/transaksi baru)
            if ($archiveId) {
                $newTxQuery = StoreTransaction::withoutGlobalScope('notArchived')
                    ->with('payments')
                    ->whereIn('store_id', $salesStoreIds)
                    ->where('data_archive_id', $archiveId);
                $legacyVisitsQuery = Visit::withoutGlobalScope('notArchived')
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->where('data_archive_id', $archiveId)
                    ->doesntHave('transactions')
                    ->where('transaction_amount', '>', 0)
                    ->whereIn('transaction_status', ['paid', 'mixed']);
            } else {
                $newTxQuery = StoreTransaction::with('payments')
                    ->whereIn('store_id', $salesStoreIds);
                if ($fromDate && $toDate) {
                    $newTxQuery->whereBetween('transaction_date', [$fromDate, $toDate]);
                }
                $legacyVisitsQuery = Visit::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->doesntHave('transactions')
                    ->where('transaction_amount', '>', 0)
                    ->whereIn('transaction_status', ['paid', 'mixed']);
                if ($fromDate && $toDate) {
                    $legacyVisitsQuery->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                }
            }
            $storeNewTransactions = $newTxQuery->get();
            $storeNewTxAmount = (float) $storeNewTransactions->sum('transaction_amount');
            $legacyVisits = $legacyVisitsQuery->get();
            $legacyNewTxAmount = (float) $legacyVisits->sum('transaction_amount');
            $newTxAmount = $storeNewTxAmount + $legacyNewTxAmount;

            // B. UANG MASUK TRANSAKSI BARU (Pembayaran aktual dari transaksi baru)
            $storeNewTxPaid = 0.0;
            foreach ($storeNewTransactions as $tx) {
                $txPayments = $archiveId ? $tx->payments()->withoutGlobalScope('notArchived')->get() : $tx->payments;
                $initPaid = (float) $txPayments->filter(fn ($p) =>
                    $p->source === 'initial_payment' || str_contains(strtolower($p->notes ?? ''), 'pembayaran awal')
                )->sum('amount');
                $storeNewTxPaid += $initPaid;
            }

            $legacyNewTxPaid = (float) $legacyVisits->sum(fn ($v) =>
                $v->transaction_status === 'paid' ? (float) ($v->cash_received ?: $v->transaction_amount) : 0.0
            );

            $newTxPaid = $storeNewTxPaid + $legacyNewTxPaid;

            // C. PEMBAYARAN PIUTANG LAMA (Pembayaran aktual untuk piutang yang sudah ada sebelumnya)
            if ($archiveId) {
                $oldDebtPayQuery = StoreTransactionPayment::withoutGlobalScope('notArchived')
                    ->whereHas('transaction', fn ($q) => $q->withoutGlobalScope('notArchived')->whereIn('store_id', $salesStoreIds))
                    ->where('data_archive_id', $archiveId)
                    ->where('source', '!=', 'initial_payment')
                    ->where(function ($q) {
                        $q->whereNull('notes')
                          ->orWhere(function ($sq) {
                              $sq->where('notes', 'not like', '%Pembayaran awal%')
                                 ->where('notes', 'not like', '%pembayaran awal%');
                          });
                    });
                $legacyOldDebtVisitsQuery = Visit::withoutGlobalScope('notArchived')
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->where('data_archive_id', $archiveId)
                    ->doesntHave('payments')
                    ->where('cash_received', '>', 0)
                    ->whereIn('transaction_status', ['piutang', 'mixed']);
            } else {
                $oldDebtPayQuery = StoreTransactionPayment::whereHas('transaction', fn ($q) => $q->whereIn('store_id', $salesStoreIds))
                    ->where('source', '!=', 'initial_payment')
                    ->where(function ($q) {
                        $q->whereNull('notes')
                          ->orWhere(function ($sq) {
                              $sq->where('notes', 'not like', '%Pembayaran awal%')
                                 ->where('notes', 'not like', '%pembayaran awal%');
                          });
                    });
                if ($fromDate && $toDate) {
                    $oldDebtPayQuery->whereBetween('payment_date', [$fromDate, $toDate]);
                }
                $legacyOldDebtVisitsQuery = Visit::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->doesntHave('payments')
                    ->where('cash_received', '>', 0)
                    ->whereIn('transaction_status', ['piutang', 'mixed']);
                if ($fromDate && $toDate) {
                    $legacyOldDebtVisitsQuery->whereBetween('check_in_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                }
            }
            $storeOldDebtPaid = (float) $oldDebtPayQuery->sum('amount');
            $legacyOldDebtPaid = (float) $legacyOldDebtVisitsQuery->get()->sum('cash_received');
            $oldDebtPaid = $storeOldDebtPaid + $legacyOldDebtPaid;

            // D. TOTAL UANG MASUK = Uang Masuk Transaksi Baru + Pembayaran Piutang Lama
            $totalCashIn = $newTxPaid + $oldDebtPaid;

            // Accumulate Summary
            $totalAttendance += $attendance;
            $totalRoutes += $routes;
            $totalCompletedRoutes += $completedRoutes;
            $totalVisits += $visits;
            $totalCompletedVisits += $completedVisits;
            $totalNewTxAmount += $newTxAmount;
            $totalNewTxPaid += $newTxPaid;
            $totalOldDebtPaid += $oldDebtPaid;

            $performance[] = [
                'id' => $user->id,
                'name' => $user->name,
                'attendance' => $attendance,
                'routes' => $routes,
                'completed_routes' => $completedRoutes,
                'completion' => $completion,
                'visits' => $visits,
                'completed_visits' => $completedVisits,
                'new_tx_amount' => $newTxAmount,
                'new_tx_paid' => $newTxPaid,
                'old_debt_paid' => $oldDebtPaid,
                'total_cash_in' => $totalCashIn,
                'transaction' => $newTxAmount,
            ];
        }

        $avgCompletion = $totalRoutes > 0 ? round(($totalCompletedRoutes / $totalRoutes) * 100) : 0;
        $totalCashInAll = $totalNewTxPaid + $totalOldDebtPaid;

        $summary = [
            'total_sales' => $totalSales,
            'total_attendance' => $totalAttendance,
            'total_routes' => $totalRoutes,
            'total_completed_routes' => $totalCompletedRoutes,
            'total_visits' => $totalVisits,
            'total_completed_visits' => $totalCompletedVisits,
            'avg_completion' => $avgCompletion,
            'total_new_tx_amount' => $totalNewTxAmount,
            'total_new_tx_paid' => $totalNewTxPaid,
            'total_old_debt_paid' => $totalOldDebtPaid,
            'total_cash_in' => $totalCashInAll,
            'total_transaction' => $totalNewTxAmount, // Backward-compatible alias
        ];

        return [
            'performance' => $performance,
            'summary' => $summary,
        ];
    }
}
