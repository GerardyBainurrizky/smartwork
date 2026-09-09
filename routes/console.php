<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('audit:store-trx-analysis', function () {
    $this->info("=== STORE TRANSACTIONS BY STORE RESPONSIBLE SALES ===");
    $transactions = \App\Models\StoreTransaction::with(['store.salesPenanggungJawab', 'creator'])->get();
    foreach ($transactions as $t) {
        $salesOwner = $t->store->salesPenanggungJawab->name ?? 'None';
        $creatorName = $t->creator->name ?? 'None';
        $this->line("TRX: {$t->transaction_code} | Date: {$t->transaction_date->format('Y-m-d')} | Store: {$t->store->name} | Amount: {$t->transaction_amount} | SalesOwner: {$salesOwner} | Creator: {$creatorName}");
    }
});

Artisan::command('audit:sales-perf', function () {
    $salesUsers = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->orderBy('name')->get();
    $this->info("Sales Users count: " . $salesUsers->count());
    foreach ($salesUsers as $u) {
        $this->line("Sales ID: {$u->id} | Name: {$u->name} | Username: {$u->username} | Roles: " . $u->roles->pluck('name')->implode(','));
    }

    $allRoles = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor', 'driver']))->get();
    $this->line("Users with sales or driver: " . $allRoles->count());

    $this->info("\n--- ROUTES AND VISITS CHECK ---");
    foreach ($salesUsers as $u) {
        $attCount = \App\Models\Attendance::where('user_id', $u->id)->where('status', '!=', 'canceled')->count();
        $routes = \App\Models\Route::with(['stops.visit'])->where('user_id', $u->id)->get();
        $routesCount = $routes->count();
        $completedRoutesCount = $routes->filter(fn ($r) => $r->computed_status === 'completed')->count();
        $visits = \App\Models\Visit::where('user_id', $u->id)->get();
        $visitsCount = $visits->count();
        $completedVisitsCount = $visits->where('status', 'completed')->count();
        
        // Trx via store ownership (store.sales_penanggung_jawab_id) vs visit.user_id
        $trxByStoreOwner = \App\Models\StoreTransaction::whereHas('store', fn ($q) => $q->where('sales_penanggung_jawab_id', $u->id))->sum('transaction_amount');
        $trxByVisit = \App\Models\Visit::where('user_id', $u->id)->where('status', 'completed')->sum('transaction_amount');
        
        $this->line("Sales {$u->name}: Att={$attCount}, Routes={$routesCount}, CompRoutes={$completedRoutesCount}, Visits={$visitsCount}, CompVisits={$completedVisitsCount}, TrxByStoreOwner={$trxByStoreOwner}, TrxByVisit={$trxByVisit}");
    }
});

Artisan::command('audit:initial-payments', function () {
    $transactions = \App\Models\StoreTransaction::with(['payments'])->where('reference_type', 'visit')->get();
    foreach ($transactions as $t) {
        $this->line("TRX: {$t->transaction_code} | Total: {$t->transaction_amount}");
        foreach ($t->payments as $p) {
            $this->line("  Payment: {$p->amount} | src: {$p->source} | src_id: {$p->source_id} | notes: {$p->notes}");
        }
    }
});

Artisan::command('audit:financial-calc', function () {
    $visits = \App\Models\Visit::with([
        'user.roles',
        'store',
        'route',
        'routeStop',
        'photos',
        'transactions.payments',
        'payments.transaction',
    ])
    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
    ->where('status', 'completed')
    ->orderByDesc('check_in_at')
    ->get();

    $this->info("=== ALL SALES COMPLETED VISITS ===");
    $this->line("Total completed visits: " . $visits->count());

    $totalTransactionsCount = 0;
    $totalNilaiTransaksi = 0.0;
    $totalPembayaranBaru = 0.0;
    $totalPembayaranLama = 0.0;
    $totalSisaPiutang = 0.0;

    foreach ($visits as $v) {
        $hasLedgerTx = $v->transactions->isNotEmpty();
        $hasLedgerPay = $v->payments->where('source', '!=', 'initial_payment')->isNotEmpty();

        $vTxCount = 0;
        $vNilaiTx = 0.0;
        $vBayarBaru = 0.0;
        $vBayarLama = 0.0;
        $vSisa = 0.0;

        if ($hasLedgerTx) {
            foreach ($v->transactions as $tx) {
                $vTxCount++;
                $amt = (float) $tx->transaction_amount;
                $paid = (float) $tx->payments->where('source', 'initial_payment')->sum('amount');
                $rem = max(0.0, round($amt - $paid, 2));

                $vNilaiTx += $amt;
                $vBayarBaru += $paid;
                $vSisa += $rem;
            }
        } elseif ($v->transaction_amount > 0 && in_array($v->transaction_status, ['paid', 'mixed'], true)) {
            $vTxCount++;
            $amt = (float) $v->transaction_amount;
            $paid = $v->transaction_status === 'paid' ? (float) ($v->cash_received ?: $v->transaction_amount) : 0.0;
            $rem = max(0.0, round($amt - $paid, 2));

            $vNilaiTx += $amt;
            $vBayarBaru += $paid;
            $vSisa += $rem;
        }

        if ($hasLedgerPay) {
            $vBayarLama += (float) $v->payments->where('source', '!=', 'initial_payment')->sum('amount');
        } elseif (in_array($v->transaction_status, ['piutang', 'mixed'], true) && $v->cash_received > 0) {
            $vBayarLama += (float) $v->cash_received;
        }

        $totalTransactionsCount += $vTxCount;
        $totalNilaiTransaksi += $vNilaiTx;
        $totalPembayaranBaru += $vBayarBaru;
        $totalPembayaranLama += $vBayarLama;
        $totalSisaPiutang += $vSisa;

        if ($vTxCount > 0 || $vBayarLama > 0) {
            $this->line("Visit {$v->id} ({$v->check_in_at->format('d/m/Y')}) {$v->store->name}: TxCount={$vTxCount}, NilaiTx={$vNilaiTx}, BayarBaru={$vBayarBaru}, BayarLama={$vBayarLama}, Sisa={$vSisa}");
        }
    }

    $this->info("=========================================");
    $this->info("TOTALS across all history:");
    $this->line("Jumlah Transaksi: {$totalTransactionsCount}");
    $this->line("Total Nilai Transaksi: Rp " . number_format($totalNilaiTransaksi, 0, ',', '.'));
    $this->line("Total Pembayaran Transaksi Baru: Rp " . number_format($totalPembayaranBaru, 0, ',', '.'));
    $this->line("Total Pembayaran Piutang Lama: Rp " . number_format($totalPembayaranLama, 0, ',', '.'));
    $this->line("Total Sisa Piutang: Rp " . number_format($totalSisaPiutang, 0, ',', '.'));
});

Artisan::command('audit:visits-detail', function () {
    $visits = \App\Models\Visit::with([
        'user.roles',
        'store',
        'transactions.payments',
        'payments.transaction',
    ])
    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
    ->where('status', 'completed')
    ->where('check_in_at', '>=', '2026-09-01')
    ->orderByDesc('check_in_at')
    ->get();

    foreach ($visits as $v) {
        $this->info("==================================================");
        $this->line("Visit: {$v->id} | {$v->check_in_at->format('d M Y H:i')} | {$v->store->name} ({$v->store->code}) | TxStatus: {$v->transaction_status}");
        $this->line("Transactions created via this visit (count: " . $v->transactions->count() . "):");
        foreach ($v->transactions as $t) {
            $this->line("  -> TRX Code: {$t->transaction_code} | Total: {$t->transaction_amount} | Rem: {$t->remaining_amount} | Status: {$t->status}");
            foreach ($t->payments as $tp) {
                $this->line("      * TRX Payment: {$tp->amount} | src: {$tp->source} | src_id: {$tp->source_id} | notes: {$tp->notes}");
            }
        }
        $this->line("Payments recorded during this visit (count: " . $v->payments->count() . "):");
        foreach ($v->payments as $p) {
            $this->line("  -> Payment: {$p->amount} | Trx: " . ($p->transaction->transaction_code ?? '-') . " | src: {$p->source} | notes: {$p->notes}");
        }
    }
});

Artisan::command('audit:visits-breakdown', function () {
    $visits = \App\Models\Visit::with([
        'user.roles',
        'store',
        'route',
        'routeStop',
        'photos',
        'transactions.payments',
        'payments.transaction',
    ])
    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
    ->where('status', 'completed')
    ->orderByDesc('check_in_at')
    ->get();

    $this->info("Total Visits: " . $visits->count());

    foreach ($visits->take(15) as $v) {
        $hasLedgerTx = $v->transactions->isNotEmpty();
        $hasLedgerPay = $v->payments->where('source', '!=', 'initial_payment')->isNotEmpty();
        
        $newTxTotal = 0.0;
        $newTxPaid = 0.0;
        $oldDebtPaid = 0.0;

        if ($hasLedgerTx) {
            foreach ($v->transactions as $tx) {
                $newTxTotal += (float) $tx->transaction_amount;
                $newTxPaid += (float) $tx->payments->where('source', 'initial_payment')->sum('amount');
            }
        } elseif ($v->transaction_amount > 0 && in_array($v->transaction_status, ['paid', 'mixed'])) {
            $newTxTotal = (float) $v->transaction_amount;
            $newTxPaid = $v->transaction_status === 'paid' ? (float) ($v->cash_received ?: $v->transaction_amount) : 0.0;
        }

        if ($hasLedgerPay) {
            $oldDebtPaid = (float) $v->payments->where('source', '!=', 'initial_payment')->sum('amount');
        } elseif (in_array($v->transaction_status, ['piutang', 'mixed']) && $v->cash_received > 0) {
            $oldDebtPaid = (float) $v->cash_received;
        }

        $rem = max(0.0, round($newTxTotal - $newTxPaid, 2));

        $this->line("Visit {$v->id} | {$v->check_in_at->format('Y-m-d H:i')} | {$v->store->name} | TxStatus: {$v->transaction_status} | NewTx: {$newTxTotal} | NewPaid: {$newTxPaid} | OldPaid: {$oldDebtPaid} | Rem: {$rem} | Photos: " . $v->photos->count());
    }
});

Artisan::command('audit:transactions-analysis', function () {
    $this->info("=== SALES VISITS WITH FINANCIAL ACTIVITY ===");
    $visits = \App\Models\Visit::with([
        'user.roles',
        'store',
        'route',
        'routeStop',
        'photos',
        'transactions.payments',
        'payments.transaction',
    ])
    ->whereHas('user.roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))
    ->where('status', 'completed')
    ->get();

    $this->line("Total Sales Completed Visits: " . $visits->count());
    
    $withTrxOrPay = $visits->filter(function ($v) {
        $hasNewTx = $v->transactions->isNotEmpty() || ($v->transaction_amount > 0 && in_array($v->transaction_status, ['paid', 'mixed']));
        $hasOldPay = $v->payments->where('source', '!=', 'initial_payment')->isNotEmpty() || (in_array($v->transaction_status, ['piutang', 'mixed']) && $v->cash_received > 0);
        return $hasNewTx || $hasOldPay;
    });

    $this->line("Visits with Financial Activity: " . $withTrxOrPay->count());
    foreach ($withTrxOrPay as $v) {
        $newTx = $v->transactions;
        $oldPay = $v->payments->where('source', '!=', 'initial_payment');
        $this->info("--------------------------------------------------");
        $this->line("Visit ID: {$v->id} | Date: {$v->check_in_at} | Sales: {$v->user->name} | Store: {$v->store->name} ({$v->store->code}) | TxStatus: {$v->transaction_status}");
        $this->line("   New Transactions: " . $newTx->map(fn($t) => "{$t->transaction_code} (Amount: {$t->transaction_amount}, Paid: {$t->total_paid}, Rem: {$t->remaining_amount})")->implode(', '));
        $this->line("   Old Debt Payments: " . $oldPay->map(fn($p) => "Pay: {$p->amount} for Trx: " . ($p->transaction->transaction_code ?? '-'))->implode(', '));
        $this->line("   Visit fields: TrxAmount: {$v->transaction_amount}, CashReceived: {$v->cash_received}");
    }
});

Artisan::command('audit:transactions-db', function () {
    $this->info("=== AUDIT STORE TRANSACTIONS ===");
    $transactions = \App\Models\StoreTransaction::with(['store', 'creator', 'payments'])->get();
    $this->line("Total Store Transactions: " . $transactions->count());
    foreach ($transactions as $tx) {
        $this->line("ID: {$tx->id} | Code: {$tx->transaction_code} | Date: {$tx->transaction_date} | Amount: {$tx->transaction_amount} | Status: {$tx->status} | RefType: {$tx->reference_type} | RefID: {$tx->reference_id} | CreatedBy: {$tx->created_by} | Store: " . ($tx->store->name ?? '-'));
        foreach ($tx->payments as $p) {
            $this->line("   -> Payment ID: {$p->id} | Amount: {$p->amount} | Method: {$p->payment_method} | Date: {$p->payment_date} | Source: {$p->source} | SourceID: {$p->source_id} | Notes: {$p->notes}");
        }
    }

    $this->info("\n=== AUDIT VISITS WITH TRANSACTIONS OR PAYMENTS ===");
    $visits = \App\Models\Visit::with(['user', 'store', 'transactions.payments', 'payments'])->where('status', 'completed')->get();
    $this->line("Total Completed Visits: " . $visits->count());
    foreach ($visits as $v) {
        $this->line("Visit ID: {$v->id} | User: " . ($v->user->name ?? '-') . " | Store: " . ($v->store->name ?? '-') . " | CheckIn: {$v->check_in_at} | TrxAmt: {$v->transaction_amount} | CashRec: {$v->cash_received} | TrxStatus: {$v->transaction_status} | Method: {$v->payment_method}");
        $this->line("   Transactions count via rel: " . $v->transactions->count() . " | Payments count via rel: " . $v->payments->count());
    }
});
