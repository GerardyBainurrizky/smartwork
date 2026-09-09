<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreReceivable;
use App\Models\StoreTransaction;
use App\Models\StoreTransactionPayment;
use App\Models\User;
use App\Services\ActivityNotificationService;
use App\Services\StoreReceivableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $cities = Store::whereNotNull('city')->distinct()->orderBy('city')->pluck('city');

        // Period filter helpers
        $period = $request->get('period', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($period === 'today') {
            $fromDate = now()->toDateString();
            $toDate = now()->toDateString();
        } elseif ($period === '7days') {
            $fromDate = now()->subDays(6)->toDateString();
            $toDate = now()->toDateString();
        } elseif ($period === '30days') {
            $fromDate = now()->subDays(29)->toDateString();
            $toDate = now()->toDateString();
        } elseif ($period === 'this_month') {
            $fromDate = now()->startOfMonth()->toDateString();
            $toDate = now()->endOfMonth()->toDateString();
        } elseif ($period === 'this_year') {
            $fromDate = now()->startOfYear()->toDateString();
            $toDate = now()->endOfYear()->toDateString();
        }

        // Sales filter
        $salesId = $request->get('sales_id');

        // Scope store query for general store list & filter
        $storeQuery = Store::query()
            ->with(['salesPenanggungJawab:id,name', 'transactions.payments'])
            ->where('status', 'active');

        // 1. Filter Sales (berdasarkan sales_penanggung_jawab_id)
        if ($request->filled('sales_id')) {
            $storeQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }

        // Filter specific Store
        if ($request->filled('store_id')) {
            $storeQuery->where('id', $request->store_id);
        }

        // Filter City
        if ($request->filled('city')) {
            $storeQuery->where('city', $request->city);
        }

        // Filter Toko Baru (opsional: toko yang dibuat dalam 30 hari terakhir / bulan ini jika dipilih)
        if ($request->get('is_new_store') === 'yes') {
            $storeQuery->where('created_at', '>=', now()->subDays(30));
        }

        // 5. Search "Cari Toko..." (Nama Toko, Kode Toko, Alamat / Kota)
        if ($request->filled('search')) {
            $s = trim($request->search);
            $storeQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%")
                  ->orWhereHas('transactions', function ($tq) use ($s) {
                      $tq->where('transaction_code', 'like', "%{$s}%");
                  });
            });
        }

        $allStores = $storeQuery->orderBy('name')->get();
        $filteredStoreIds = $allStores->pluck('id');

        // Calculate per-store balance and counts
        foreach ($allStores as $st) {
            $bal = (float) StoreReceivableService::balanceForStore($st->id);
            $st->receivable_balance = $bal;

            $openTrxCount = $st->transactions->filter(function ($trx) {
                return $trx->remaining_amount > 0.005;
            })->count();

            $paidTrxCount = $st->transactions->filter(function ($trx) {
                return $trx->remaining_amount <= 0.005 && (float) $trx->transaction_amount > 0;
            })->count();

            $st->open_transactions_count = $openTrxCount;
            $st->paid_transactions_count = $paidTrxCount;
        }

        // Base query for scoped transactions & payments (restricted to Sales filter if selected)
        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        // =========================================================================
        // 8 FINAL KPI CARDS (Calculated precisely from DB ledgers)
        // =========================================================================

        // 1. TOTAL PIUTANG (Saldo Saat Ini, SUM remaining_amount WHERE remaining > 0)
        $totalReceivable = 0.0;
        $storesWithReceivable = 0;
        foreach ($allStores as $st) {
            if ($st->receivable_balance > 0.005) {
                $storesWithReceivable++;
                $totalReceivable += $st->receivable_balance;
            }
        }
        $storesWithout = max(0, $allStores->count() - $storesWithReceivable);

        // 2. TOKO BERPIUTANG: COUNT(DISTINCT store_id) WHERE remaining_amount > 0
        // (sama dengan $storesWithReceivable yang memiliki saldo aktif > 0)

        // 3. TRANSAKSI TERBUKA: COUNT(store_transactions) WHERE remaining_amount > 0
        $openTransactionsList = StoreTransaction::with('payments')
            ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
            ->get()
            ->filter(fn ($t) => $t->remaining_amount > 0.005);
        $totalOpenTransactions = $openTransactionsList->count();

        // 4. TOTAL PEMBAYARAN: Kumulatif / All Time pembayaran piutang lama
        // Payment piutang lama: source != 'initial_payment' AND notes tidak mengandung pembayaran awal transaksi baru
        $totalPayments = (float) StoreTransactionPayment::whereHas('transaction', function ($q) use ($scopedStoreIds) {
                $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']);
            })
            ->where('source', '!=', 'initial_payment')
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->where(function ($q) {
                $q->where('notes', 'not like', '%Pembayaran awal%')
                  ->orWhereNull('notes');
            })
            ->sum('amount');

        // 5. PEMBAYARAN PIUTANG HARI INI: Uang diterima hari ini untuk pembayaran piutang lama
        $todayDate = now()->toDateString();
        $todayPayments = (float) StoreTransactionPayment::whereHas('transaction', function ($q) use ($scopedStoreIds) {
                $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']);
            })
            ->whereDate('payment_date', $todayDate)
            ->where('source', '!=', 'initial_payment')
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->where(function ($q) {
                $q->where('notes', 'not like', '%Pembayaran awal%')
                  ->orWhereNull('notes');
            })
            ->sum('amount');

        // 6. TRANSAKSI LUNAS: Jumlah transaksi yang saldo = 0
        $paidTransactionsList = StoreTransaction::with('payments')
            ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
            ->get()
            ->filter(fn ($t) => $t->remaining_amount <= 0.005 && (float) $t->transaction_amount > 0);
        $totalPaidTransactions = $paidTransactionsList->count();

        // 7. TOTAL TRANSAKSI BARU BULAN INI: SUM(transaction_amount) dari transaksi baru yang dibuat bulan berjalan
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();
        $newTransactionsThisMonth = (float) StoreTransaction::whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
            ->whereDate('transaction_date', '>=', $startOfMonth)
            ->whereDate('transaction_date', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', 'opening_balance');
            })
            ->sum('transaction_amount');

        // 8. UANG MASUK TRANSAKSI BARU BULAN INI: SUM(payment amount) pembayaran awal transaksi baru bulan berjalan
        $newTransactionsCashInThisMonth = (float) StoreTransactionPayment::whereHas('transaction', function ($q) use ($scopedStoreIds, $startOfMonth, $endOfMonth) {
                $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
                  ->whereDate('transaction_date', '>=', $startOfMonth)
                  ->whereDate('transaction_date', '<=', $endOfMonth)
                  ->where(function ($qq) {
                      $qq->whereNull('reference_type')
                         ->orWhere('reference_type', '!=', 'opening_balance');
                  });
            })
            ->whereDate('payment_date', '>=', $startOfMonth)
            ->whereDate('payment_date', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->where('source', 'initial_payment')
                  ->orWhere('notes', 'like', '%Pembayaran awal%')
                  ->orWhere('notes', 'like', '%pembayaran awal%');
            })
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->sum('amount');

        // View Mode / Card Click handling
        $viewMode = $request->get('view', 'stores'); // 'stores', 'open_transactions', 'paid_transactions', 'debt_payments', 'today_payments', 'new_transactions', 'new_payments'

        // Filter status piutang toko: with (Memiliki Piutang / Belum Lunas), without (Tidak Ada Piutang / Lunas), all
        $statusFilter = $request->get('receivable_status', 'all');
        $filteredCollection = $allStores;
        if ($statusFilter === 'with') {
            $filteredCollection = $allStores->filter(fn ($st) => $st->receivable_balance > 0.005)->values();
        } elseif ($statusFilter === 'without') {
            $filteredCollection = $allStores->filter(fn ($st) => $st->receivable_balance <= 0.005)->values();
        }

        // Paginate stores collection manually (10 per page) with query string preservation
        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $filteredCollection->slice($offset, $perPage)->values();
        
        $stores = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $filteredCollection->count(),
            $perPage,
            $page,
            ['path' => route('admin.receivables.index'), 'query' => $request->query()]
        );

        // Dedicated detail query when a specific card is clicked
        $cardDetails = null;
        $cardTitle = '';
        $cardSubtitle = '';

        if ($viewMode === 'open_transactions') {
            $cardTitle = 'Daftar Transaksi Terbuka';
            $cardSubtitle = 'Menampilkan seluruh transaksi yang masih memiliki sisa saldo tagihan aktif (Sisa > Rp0).';
            $cardDetails = StoreTransaction::with(['store.salesPenanggungJawab', 'creator:id,name', 'payments'])
                ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
                ->orderByDesc('transaction_date')
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn ($t) => $t->remaining_amount > 0.005);
        } elseif ($viewMode === 'paid_transactions') {
            $cardTitle = 'Daftar Transaksi Lunas';
            $cardSubtitle = 'Menampilkan transaksi yang telah terbayar lunas 100% (Sisa = Rp0).';
            $cardDetails = StoreTransaction::with(['store.salesPenanggungJawab', 'creator:id,name', 'payments'])
                ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
                ->orderByDesc('transaction_date')
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn ($t) => $t->remaining_amount <= 0.005 && (float) $t->transaction_amount > 0);
        } elseif ($viewMode === 'debt_payments') {
            $cardTitle = 'Daftar Seluruh Pembayaran Piutang Lama';
            $cardSubtitle = 'Akumulasi seluruh histori pembayaran yang dicatat untuk melunasi saldo piutang lama toko.';
            $cardDetails = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab', 'recorder:id,name'])
                ->whereHas('transaction', fn ($q) => $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']))
                ->where('source', '!=', 'initial_payment')
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->where(function ($q) {
                    $q->where('notes', 'not like', '%Pembayaran awal%')
                      ->orWhereNull('notes');
                })
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        } elseif ($viewMode === 'today_payments') {
            $cardTitle = 'Daftar Pembayaran Piutang Hari Ini';
            $cardSubtitle = 'Pembayaran piutang lama yang benar-benar diterima dan dicatat pada hari ini (' . now()->format('d M Y') . ').';
            $cardDetails = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab', 'recorder:id,name'])
                ->whereHas('transaction', fn ($q) => $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']))
                ->whereDate('payment_date', $todayDate)
                ->where('source', '!=', 'initial_payment')
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->where(function ($q) {
                    $q->where('notes', 'not like', '%Pembayaran awal%')
                      ->orWhereNull('notes');
                })
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        } elseif ($viewMode === 'new_transactions') {
            $cardTitle = 'Daftar Transaksi Baru Bulan Ini';
            $cardSubtitle = 'Seluruh faktur dan transaksi baru yang dibuat pada bulan ' . now()->translatedFormat('F Y') . '.';
            $cardDetails = StoreTransaction::with(['store.salesPenanggungJawab', 'creator:id,name', 'payments'])
                ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
                ->whereDate('transaction_date', '>=', $startOfMonth)
                ->whereDate('transaction_date', '<=', $endOfMonth)
                ->where(function ($q) {
                    $q->whereNull('reference_type')
                      ->orWhere('reference_type', '!=', 'opening_balance');
                })
                ->orderByDesc('transaction_date')
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        } elseif ($viewMode === 'new_payments') {
            $cardTitle = 'Daftar Uang Masuk Transaksi Baru Bulan Ini';
            $cardSubtitle = 'Seluruh penerimaan pembayaran awal untuk transaksi baru yang dibuat pada bulan ' . now()->translatedFormat('F Y') . '.';
            $cardDetails = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab', 'recorder:id,name'])
                ->whereHas('transaction', function ($q) use ($scopedStoreIds, $startOfMonth, $endOfMonth) {
                    $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
                      ->whereDate('transaction_date', '>=', $startOfMonth)
                      ->whereDate('transaction_date', '<=', $endOfMonth)
                      ->where(function ($qq) {
                          $qq->whereNull('reference_type')
                             ->orWhere('reference_type', '!=', 'opening_balance');
                      });
                })
                ->whereDate('payment_date', '>=', $startOfMonth)
                ->whereDate('payment_date', '<=', $endOfMonth)
                ->where(function ($q) {
                    $q->where('source', 'initial_payment')
                      ->orWhere('notes', 'like', '%Pembayaran awal%')
                      ->orWhere('notes', 'like', '%pembayaran awal%');
                })
                ->where(function ($q) {
                    $q->where('source', '!=', 'adjustment')
                      ->orWhereNull('source');
                })
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        }

        // Base query for payments (Riwayat Pembayaran Piutang Lama)
        $paymentsQuery = StoreTransactionPayment::whereHas('transaction', function ($q) use ($scopedStoreIds) {
            $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']);
        })
        ->where('source', '!=', 'initial_payment')
        ->where(function ($q) {
            $q->where('source', '!=', 'adjustment')
              ->orWhereNull('source');
        })
        ->where(function ($q) {
            $q->where('notes', 'not like', '%Pembayaran awal%')
              ->orWhereNull('notes');
        });

        if ($fromDate && $toDate) {
            $paymentsQuery->whereDate('payment_date', '>=', $fromDate)
                ->whereDate('payment_date', '<=', $toDate);
        }

        // --- Pembayaran Piutang Lama: Ringkasan per Tanggal ---
        $chartPayments = (clone $paymentsQuery)
            ->selectRaw("DATE(payment_date) as pdate, SUM(amount) as total_amount, COUNT(id) as total_tx_count")
            ->groupBy('pdate')
            ->orderByDesc('pdate')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'date' => \Carbon\Carbon::parse($r->pdate)->format('d M Y'),
                'raw_date' => $r->pdate,
                'total' => (float) $r->total_amount,
                'count' => (int) $r->total_tx_count,
            ]);
        
        $totalPeriodPayments = (float) (clone $paymentsQuery)->sum('amount');

        // --- Chart 2: Receivable by Sales Person ---
        $salesReceivables = [];
        $salesGroups = $allStores->groupBy(fn ($st) => $st->sales_penanggung_jawab_id ?? 'unassigned');

        foreach ($salesGroups as $salesId => $storeGroup) {
            $groupBal = (float) $storeGroup->sum('receivable_balance');
            $indebtedCount = $storeGroup->filter(fn ($st) => $st->receivable_balance > 0.005)->count();
            if ($salesId === 'unassigned') {
                $salesName = 'Belum Ada Sales';
            } else {
                $salesUser = $sales->firstWhere('id', $salesId) ?? User::find($salesId);
                $salesName = $salesUser?->name ?? 'Sales';
            }

            $salesReceivables[] = [
                'id' => $salesId,
                'name' => $salesName,
                'total' => $groupBal,
                'store_count' => $storeGroup->count(),
                'indebted_count' => $indebtedCount,
            ];
        }

        // Sort descending by total receivable
        $salesReceivablesCollection = collect($salesReceivables)->sortByDesc('total')->values();

        // Paginate Sales Card (5 Sales per page, separate page parameter: sales_page)
        $salesPerPage = 5;
        $salesPage = (int) $request->get('sales_page', 1);
        $salesOffset = ($salesPage - 1) * $salesPerPage;
        $salesPaginatedItems = $salesReceivablesCollection->slice($salesOffset, $salesPerPage)->values();

        $paginatedSalesReceivables = new \Illuminate\Pagination\LengthAwarePaginator(
            $salesPaginatedItems,
            $salesReceivablesCollection->count(),
            $salesPerPage,
            $salesPage,
            ['path' => route('admin.receivables.index'), 'pageName' => 'sales_page', 'query' => $request->query()]
        );

        // --- Top Stores with Largest Receivable ---
        $topStores = $allStores->filter(fn ($st) => $st->receivable_balance > 0.005)
            ->sortByDesc('receivable_balance')
            ->take(5)
            ->values();

        // --- Recent Transactions (Hanya Transaksi Baru, bukan saldo awal) ---
        $recentTransactions = StoreTransaction::with(['store.salesPenanggungJawab:id,name', 'creator:id,name', 'payments'])
            ->whereIn('store_id', $filteredStoreIds->isNotEmpty() ? $filteredStoreIds : ['empty'])
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', 'opening_balance');
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // --- Recent Payments (Seluruh aktivitas pembayaran aktual, diklasifikasikan berdasarkan jenis) ---
        $recentPayments = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab:id,name', 'recorder:id,name'])
            ->whereHas('transaction', function ($q) use ($filteredStoreIds) {
                if ($filteredStoreIds->isNotEmpty()) {
                    $q->whereIn('store_id', $filteredStoreIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'summary' => [
                    'total_receivable' => $totalReceivable,
                    'stores_with' => $storesWithReceivable,
                    'stores_without' => $storesWithout,
                    'total_open_transactions' => $totalOpenTransactions,
                    'total_paid_transactions' => $totalPaidTransactions,
                    'total_payments' => $totalPayments,
                    'today_payments' => $todayPayments,
                    'new_transactions_this_month' => $newTransactionsThisMonth,
                    'new_transactions_cash_in_this_month' => $newTransactionsCashInThisMonth,
                ],
                'chart_payments' => $chartPayments,
                'sales_receivables' => $salesReceivables,
                'top_stores' => $topStores,
                'recent_transactions' => $recentTransactions,
                'recent_payments' => $recentPayments,
                'stores' => $filteredCollection,
            ]);
        }

        return view('admin.receivables.index', compact(
            'sales', 'cities', 'stores', 'allStores',
            'totalReceivable', 'storesWithReceivable', 'storesWithout',
            'totalOpenTransactions', 'totalPaidTransactions',
            'totalPayments', 'todayPayments',
            'newTransactionsThisMonth', 'newTransactionsCashInThisMonth',
            'chartPayments', 'salesReceivables', 'paginatedSalesReceivables', 'topStores',
            'recentTransactions', 'recentPayments',
            'period', 'fromDate', 'toDate', 'totalPeriodPayments'
        ));
    }

    public function summaryTotal(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        
        $storeQuery = Store::query()
            ->with(['salesPenanggungJawab:id,name', 'transactions.payments'])
            ->where('status', 'active');

        if ($request->filled('sales_id')) {
            $storeQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $storeQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        $allStores = $storeQuery->orderBy('name')->get();
        $indebtedStores = collect();
        $totalReceivable = 0.0;
        $totalOpenTransactions = 0;

        foreach ($allStores as $st) {
            $bal = (float) StoreReceivableService::balanceForStore($st->id);
            $openTrxCount = $st->transactions->filter(fn ($trx) => $trx->remaining_amount > 0.005)->count();
            $st->receivable_balance = $bal;
            $st->open_transactions_count = $openTrxCount;

            if ($bal > 0.005) {
                $totalReceivable += $bal;
                $totalOpenTransactions += $openTrxCount;
                $indebtedStores->push($st);
            }
        }

        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $indebtedStores->slice($offset, $perPage)->values();

        $stores = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $indebtedStores->count(),
            $perPage,
            $page,
            ['path' => route('admin.receivables.summary.total'), 'query' => $request->query()]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'total_receivable' => $totalReceivable,
                'total_stores' => $indebtedStores->count(),
                'total_open_transactions' => $totalOpenTransactions,
                'stores' => $stores,
            ]);
        }

        return view('admin.receivables.summary.total', compact('sales', 'stores', 'totalReceivable', 'indebtedStores', 'totalOpenTransactions'));
    }

    public function summaryIndebtedStores(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        
        $storeQuery = Store::query()
            ->with(['salesPenanggungJawab:id,name', 'transactions.payments'])
            ->where('status', 'active');

        if ($request->filled('sales_id')) {
            $storeQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $storeQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        $allStores = $storeQuery->orderBy('name')->get();
        $indebtedStores = collect();
        $totalReceivable = 0.0;

        foreach ($allStores as $st) {
            $bal = (float) StoreReceivableService::balanceForStore($st->id);
            $openTrxCount = $st->transactions->filter(fn ($trx) => $trx->remaining_amount > 0.005)->count();
            $st->receivable_balance = $bal;
            $st->open_transactions_count = $openTrxCount;

            if ($bal > 0.005) {
                $totalReceivable += $bal;
                $indebtedStores->push($st);
            }
        }

        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $indebtedStores->slice($offset, $perPage)->values();

        $stores = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $indebtedStores->count(),
            $perPage,
            $page,
            ['path' => route('admin.receivables.summary.indebted-stores'), 'query' => $request->query()]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'total_receivable' => $totalReceivable,
                'total_stores' => $indebtedStores->count(),
                'stores' => $stores,
            ]);
        }

        return view('admin.receivables.summary.indebted-stores', compact('sales', 'stores', 'totalReceivable', 'indebtedStores'));
    }

    public function summaryOpenTransactions(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        
        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        $trxQuery = StoreTransaction::with(['store.salesPenanggungJawab:id,name', 'creator:id,name', 'payments'])
            ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']);

        if ($request->filled('store_id')) {
            $trxQuery->where('store_id', $request->store_id);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $trxQuery->where(function ($q) use ($s) {
                $q->where('transaction_code', 'like', "%{$s}%")
                  ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        $allMatching = $trxQuery->orderByDesc('transaction_date')->orderByDesc('created_at')->get()
            ->filter(fn ($t) => $t->remaining_amount > 0.005)->values();

        $totalRemaining = $allMatching->sum('remaining_amount');
        $totalTrxAmount = $allMatching->sum('transaction_amount');
        $totalPaidAmount = $allMatching->sum('total_paid');
        $totalCount = $allMatching->count();

        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $allMatching->slice($offset, $perPage)->values();

        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $totalCount,
            $perPage,
            $page,
            ['path' => route('admin.receivables.summary.open-transactions'), 'query' => $request->query()]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'total_count' => $totalCount,
                'total_remaining' => $totalRemaining,
                'transactions' => $transactions,
            ]);
        }

        return view('admin.receivables.summary.open-transactions', compact('sales', 'transactions', 'totalCount', 'totalRemaining', 'totalTrxAmount', 'totalPaidAmount'));
    }

    public function summaryPayments(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        
        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        $payQuery = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab:id,name', 'recorder:id,name'])
            ->whereHas('transaction', fn ($q) => $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']))
            ->where('source', '!=', 'initial_payment')
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->where(function ($q) {
                $q->where('notes', 'not like', '%Pembayaran awal%')
                  ->orWhereNull('notes');
            });

        if ($request->filled('payment_method')) {
            $payQuery->where('payment_method', $request->payment_method);
        }

        if ($request->filled('from_date')) {
            $payQuery->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $payQuery->whereDate('payment_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $payQuery->where(function ($q) use ($s) {
                $q->whereHas('transaction', function ($tq) use ($s) {
                    $tq->where('transaction_code', 'like', "%{$s}%")
                       ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
                });
            });
        }

        $totalAmount = (float) (clone $payQuery)->sum('amount');
        $payments = $payQuery->orderByDesc('payment_date')->orderByDesc('created_at')->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'total_amount' => $totalAmount,
                'payments' => $payments,
            ]);
        }

        return view('admin.receivables.summary.payments', compact('sales', 'payments', 'totalAmount'));
    }

    public function summaryTodayPayments(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        $todayDate = now()->toDateString();
        
        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        $payQuery = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab:id,name', 'recorder:id,name'])
            ->whereHas('transaction', fn ($q) => $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']))
            ->whereDate('payment_date', $todayDate)
            ->where('source', '!=', 'initial_payment')
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->where(function ($q) {
                $q->where('notes', 'not like', '%Pembayaran awal%')
                  ->orWhereNull('notes');
            });

        if ($request->filled('payment_method')) {
            $payQuery->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $payQuery->where(function ($q) use ($s) {
                $q->whereHas('transaction', function ($tq) use ($s) {
                    $tq->where('transaction_code', 'like', "%{$s}%")
                       ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
                });
            });
        }

        $totalAmount = (float) (clone $payQuery)->sum('amount');
        $payments = $payQuery->orderByDesc('created_at')->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'today_date' => $todayDate,
                'total_amount' => $totalAmount,
                'payments' => $payments,
            ]);
        }

        return view('admin.receivables.summary.today-payments', compact('sales', 'payments', 'totalAmount', 'todayDate'));
    }

    public function summaryPaidTransactions(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        
        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        $trxQuery = StoreTransaction::with(['store.salesPenanggungJawab:id,name', 'creator:id,name', 'payments'])
            ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $trxQuery->where(function ($q) use ($s) {
                $q->where('transaction_code', 'like', "%{$s}%")
                  ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        $allMatching = $trxQuery->orderByDesc('transaction_date')->orderByDesc('created_at')->get()
            ->filter(fn ($t) => $t->remaining_amount <= 0.005 && (float) $t->transaction_amount > 0)->values();

        $totalTrxAmount = $allMatching->sum('transaction_amount');
        $totalPaidAmount = $allMatching->sum('total_paid');
        $totalCount = $allMatching->count();

        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $allMatching->slice($offset, $perPage)->values();

        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $totalCount,
            $perPage,
            $page,
            ['path' => route('admin.receivables.summary.paid-transactions'), 'query' => $request->query()]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'total_count' => $totalCount,
                'total_paid_amount' => $totalPaidAmount,
                'transactions' => $transactions,
            ]);
        }

        return view('admin.receivables.summary.paid-transactions', compact('sales', 'transactions', 'totalCount', 'totalTrxAmount', 'totalPaidAmount'));
    }

    public function summaryNewTransactions(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        $trxQuery = StoreTransaction::with(['store.salesPenanggungJawab:id,name', 'creator:id,name', 'payments'])
            ->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
            ->whereDate('transaction_date', '>=', $startOfMonth)
            ->whereDate('transaction_date', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->whereNull('reference_type')
                  ->orWhere('reference_type', '!=', 'opening_balance');
            });

        if ($request->filled('search')) {
            $s = trim($request->search);
            $trxQuery->where(function ($q) use ($s) {
                $q->where('transaction_code', 'like', "%{$s}%")
                  ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        $totalTransactionAmount = (float) (clone $trxQuery)->sum('transaction_amount');
        $transactions = $trxQuery->orderByDesc('transaction_date')->orderByDesc('created_at')->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'month_label' => now()->translatedFormat('F Y'),
                'total_transaction_amount' => $totalTransactionAmount,
                'transactions' => $transactions,
            ]);
        }

        return view('admin.receivables.summary.new-transactions', compact('sales', 'transactions', 'totalTransactionAmount', 'startOfMonth', 'endOfMonth'));
    }

    public function summaryNewTransactionPayments(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        $payQuery = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab:id,name', 'recorder:id,name'])
            ->whereHas('transaction', function ($q) use ($scopedStoreIds, $startOfMonth, $endOfMonth) {
                $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty'])
                  ->whereDate('transaction_date', '>=', $startOfMonth)
                  ->whereDate('transaction_date', '<=', $endOfMonth)
                  ->where(function ($qq) {
                      $qq->whereNull('reference_type')
                         ->orWhere('reference_type', '!=', 'opening_balance');
                  });
            })
            ->whereDate('payment_date', '>=', $startOfMonth)
            ->whereDate('payment_date', '<=', $endOfMonth)
            ->where(function ($q) {
                $q->where('source', 'initial_payment')
                  ->orWhere('notes', 'like', '%Pembayaran awal%')
                  ->orWhere('notes', 'like', '%pembayaran awal%');
            })
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            });

        if ($request->filled('payment_method')) {
            $payQuery->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $payQuery->where(function ($q) use ($s) {
                $q->whereHas('transaction', function ($tq) use ($s) {
                    $tq->where('transaction_code', 'like', "%{$s}%")
                       ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
                });
            });
        }

        $totalAmount = (float) (clone $payQuery)->sum('amount');
        $payments = $payQuery->orderByDesc('payment_date')->orderByDesc('created_at')->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'month_label' => now()->translatedFormat('F Y'),
                'total_amount' => $totalAmount,
                'payments' => $payments,
            ]);
        }

        return view('admin.receivables.summary.new-transaction-payments', compact('sales', 'payments', 'totalAmount', 'startOfMonth', 'endOfMonth'));
    }

    public function summaryTrends(Request $request)
    {
        $sales = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get(['id', 'name']);
        
        $period = $request->get('period', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if ($period === 'today') {
            $fromDate = now()->toDateString();
            $toDate = now()->toDateString();
        } elseif ($period === '7days') {
            $fromDate = now()->subDays(6)->toDateString();
            $toDate = now()->toDateString();
        } elseif ($period === '30days') {
            $fromDate = now()->subDays(29)->toDateString();
            $toDate = now()->toDateString();
        } elseif ($period === 'this_month') {
            $fromDate = now()->startOfMonth()->toDateString();
            $toDate = now()->endOfMonth()->toDateString();
        } elseif ($period === 'this_year') {
            $fromDate = now()->startOfYear()->toDateString();
            $toDate = now()->endOfYear()->toDateString();
        }

        $scopedStoreIdsQuery = Store::where('status', 'active');
        if ($request->filled('sales_id')) {
            $scopedStoreIdsQuery->where('sales_penanggung_jawab_id', $request->sales_id);
        }
        $scopedStoreIds = $scopedStoreIdsQuery->pluck('id');

        $paymentsQuery = StoreTransactionPayment::with(['transaction.store.salesPenanggungJawab:id,name', 'recorder:id,name'])
            ->whereHas('transaction', fn ($q) => $q->whereIn('store_id', $scopedStoreIds->isNotEmpty() ? $scopedStoreIds : ['empty']))
            ->where('source', '!=', 'initial_payment')
            ->where(function ($q) {
                $q->where('source', '!=', 'adjustment')
                  ->orWhereNull('source');
            })
            ->where(function ($q) {
                $q->where('notes', 'not like', '%Pembayaran awal%')
                  ->orWhereNull('notes');
            });

        if ($fromDate && $toDate) {
            $paymentsQuery->whereDate('payment_date', '>=', $fromDate)
                ->whereDate('payment_date', '<=', $toDate);
        }

        $datesCollection = (clone $paymentsQuery)
            ->selectRaw("DATE(payment_date) as pdate, SUM(amount) as total_amount, COUNT(id) as total_tx_count")
            ->groupBy('pdate')
            ->orderByDesc('pdate')
            ->get()
            ->map(fn ($r) => [
                'date' => \Carbon\Carbon::parse($r->pdate)->format('d M Y'),
                'raw_date' => $r->pdate,
                'total' => (float) $r->total_amount,
                'count' => (int) $r->total_tx_count,
            ]);

        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedDates = $datesCollection->slice($offset, $perPage)->values();

        $dateSummaries = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedDates,
            $datesCollection->count(),
            $perPage,
            $page,
            ['path' => route('admin.receivables.summary.trends'), 'query' => $request->query()]
        );

        $totalAmount = (float) (clone $paymentsQuery)->sum('amount');
        $totalPaymentsCount = (clone $paymentsQuery)->count();
        $payments = (clone $paymentsQuery)->orderByDesc('payment_date')->orderByDesc('created_at')->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'total_amount' => $totalAmount,
                'total_payments_count' => $totalPaymentsCount,
                'date_summaries' => $dateSummaries,
                'payments' => $payments,
            ]);
        }

        return view('admin.receivables.summary.trends', compact('sales', 'dateSummaries', 'payments', 'totalAmount', 'totalPaymentsCount', 'period', 'fromDate', 'toDate'));
    }

    public function summaryBySales(Request $request)
    {
        $salesList = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))->orderBy('name')->get();
        
        $storeQuery = Store::query()
            ->with(['salesPenanggungJawab:id,name', 'transactions.payments'])
            ->where('status', 'active');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $storeQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            });
        }

        $allStores = $storeQuery->orderBy('name')->get();
        
        foreach ($allStores as $st) {
            $st->receivable_balance = (float) StoreReceivableService::balanceForStore($st->id);
            $st->open_transactions_count = $st->transactions->filter(fn ($trx) => $trx->remaining_amount > 0.005)->count();
        }

        $salesBreakdown = collect();
        $salesGroups = $allStores->groupBy(fn ($st) => $st->sales_penanggung_jawab_id ?? 'unassigned');
        $totalOverallReceivable = 0.0;

        foreach ($salesGroups as $salesId => $storeGroup) {
            $groupBal = (float) $storeGroup->sum('receivable_balance');
            $totalOverallReceivable += $groupBal;
            
            if ($salesId === 'unassigned') {
                $salesName = 'Belum Ada Sales';
                $salesEmail = '-';
                $salesPhone = '-';
            } else {
                $salesUser = $salesList->firstWhere('id', $salesId) ?? User::find($salesId);
                $salesName = $salesUser?->name ?? 'Sales';
                $salesEmail = $salesUser?->email ?? '-';
                $salesPhone = $salesUser?->phone ?? '-';
            }

            $indebtedCount = $storeGroup->filter(fn ($st) => $st->receivable_balance > 0.005)->count();
            $openTrxCount = $storeGroup->sum('open_transactions_count');

            $salesBreakdown->push([
                'id' => $salesId,
                'name' => $salesName,
                'email' => $salesEmail,
                'phone' => $salesPhone,
                'total_receivable' => $groupBal,
                'total_stores' => $storeGroup->count(),
                'indebted_stores_count' => $indebtedCount,
                'open_transactions_count' => $openTrxCount,
                'stores' => $storeGroup,
            ]);
        }

        // Sort descending by total receivable
        $salesBreakdown = $salesBreakdown->sortByDesc('total_receivable')->values();

        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $salesBreakdown->slice($offset, $perPage)->values();

        $salesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $salesBreakdown->count(),
            $perPage,
            $page,
            ['path' => route('admin.receivables.summary.by-sales'), 'query' => $request->query()]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'total_receivable' => $totalOverallReceivable,
                'sales_breakdown' => $salesPaginated,
            ]);
        }

        return view('admin.receivables.summary.by-sales', compact('salesPaginated', 'totalOverallReceivable'));
    }

    public function show(Request $request, string $storeId)
    {
        $store = Store::with('salesPenanggungJawab:id,name')->withTrashed()->findOrFail($storeId);
        $balance = (float) StoreReceivableService::balanceForStore($store->id);

        $trxQuery = StoreTransaction::with(['creator:id,name', 'payments.recorder:id,name'])
            ->where('store_id', $store->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at');

        $allTransactions = (clone $trxQuery)->get();

        $openTrxCount = $allTransactions->filter(function ($trx) {
            return $trx->remaining_amount > 0.005;
        })->count();

        $paidTrxCount = $allTransactions->filter(function ($trx) {
            return $trx->remaining_amount <= 0.005 && (float) $trx->transaction_amount > 0;
        })->count();

        $totalStorePayments = (float) StoreTransactionPayment::whereHas('transaction', fn ($q) => $q->where('store_id', $store->id))->sum('amount');

        // Paginate transactions 10 per page
        $transactions = $trxQuery->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'store' => $store,
                'balance' => $balance,
                'open_transactions_count' => $openTrxCount,
                'paid_transactions_count' => $paidTrxCount,
                'total_payments' => $totalStorePayments,
                'transactions' => $transactions,
            ]);
        }

        return view('admin.receivables.show', compact('store', 'balance', 'openTrxCount', 'paidTrxCount', 'totalStorePayments', 'transactions'));
    }

    public function showTransaction(Request $request, string $transactionId)
    {
        $transaction = StoreTransaction::with([
            'store.salesPenanggungJawab:id,name',
            'creator:id,name',
            'payments.recorder:id,name',
        ])->findOrFail($transactionId);

        if ($request->wantsJson()) {
            return response()->json([
                'transaction' => $transaction,
                'payments' => $transaction->payments,
            ]);
        }

        return view('admin.receivables.transaction-show', compact('transaction'));
    }

    public function storeTransaction(Request $request)
    {
        $rawAmount = $request->input('transaction_amount');
        if (is_string($rawAmount)) {
            $cleaned = preg_replace('/[^\d]/', '', $rawAmount);
            $request->merge(['transaction_amount' => $cleaned === '' ? null : (int) $cleaned]);
        }

        $rawPaid = $request->input('paid_amount');
        if (is_string($rawPaid)) {
            $cleanedPaid = preg_replace('/[^\d]/', '', $rawPaid);
            $request->merge(['paid_amount' => $cleanedPaid === '' ? 0 : (int) $cleanedPaid]);
        }

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'transaction_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:tunai,transfer,qris'],
        ]);

        $actor = auth()->user();

        $transaction = StoreReceivableService::createTransaction([
            'store_id' => $validated['store_id'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'] ?? 'Transaksi baru oleh ' . $actor->name,
            'transaction_amount' => $validated['transaction_amount'],
            'paid_amount' => $validated['paid_amount'] ?? 0,
            'payment_method' => $validated['payment_method'] ?? 'tunai',
            'reference_type' => 'admin_transaction',
            'reference_id' => null,
        ], $actor);

        $store = $transaction->store;

        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'receivable_new_transaction',
            'title' => "{$actor->name} membuat transaksi baru {$transaction->transaction_code} untuk {$store->name} sebesar Rp" . number_format($transaction->transaction_amount, 0, ',', '.') . ".",
            'message' => "Transaksi {$transaction->transaction_code} dibuat untuk {$store->name} sebesar Rp" . number_format($transaction->transaction_amount, 0, ',', '.') . ($transaction->total_paid > 0 ? " dengan DP Rp" . number_format($transaction->total_paid, 0, ',', '.') : '') . ".",
            'actor_id' => (string) $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role_label,
            'store_name' => $store->name,
            'info' => 'TRX ' . $transaction->transaction_code,
            'url' => route('admin.receivables.transactions.show', $transaction->id),
            'related_id' => (string) $transaction->id,
            'related_type' => 'StoreTransaction',
            'activity_time' => now()->toIso8601String(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi baru berhasil dibuat.',
                'data' => $transaction,
            ], 201);
        }

        return redirect()->route('admin.receivables.show', $store->id)
            ->with('success', "Transaksi {$transaction->transaction_code} berhasil dibuat.");
    }

    public function storeTransactionPayment(Request $request, string $transactionId)
    {
        $rawAmount = $request->input('amount');
        if (is_string($rawAmount)) {
            $cleaned = preg_replace('/[^\d]/', '', $rawAmount);
            $request->merge(['amount' => $cleaned === '' ? null : (int) $cleaned]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:tunai,transfer,qris'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $actor = auth()->user();

        $payment = StoreReceivableService::recordTransactionPayment([
            'store_transaction_id' => $transactionId,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date' => $validated['payment_date'],
            'source' => 'admin',
            'source_id' => null,
            'notes' => $validated['notes'] ?? 'Pembayaran dicatat oleh Admin ' . $actor->name,
        ], $actor);

        $transaction = $payment->transaction;
        $store = $transaction->store;

        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'receivable_payment_recorded',
            'title' => "{$actor->name} mencatat pembayaran Rp" . number_format($payment->amount, 0, ',', '.') . " untuk {$transaction->transaction_code}.",
            'message' => "Pembayaran sebesar Rp" . number_format($payment->amount, 0, ',', '.') . " dicatat untuk transaksi {$transaction->transaction_code} di {$store->name}.",
            'actor_id' => (string) $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role_label,
            'store_name' => $store->name,
            'info' => 'Bayar Rp' . number_format($payment->amount, 0, ',', '.'),
            'url' => route('admin.receivables.transactions.show', $transaction->id),
            'related_id' => (string) $payment->id,
            'related_type' => 'StoreTransactionPayment',
            'activity_time' => now()->toIso8601String(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran berhasil dicatat.',
                'data' => $payment,
            ], 201);
        }

        return redirect()->route('admin.receivables.transactions.show', $transaction->id)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function storeOpeningBalance(Request $request)
    {
        $rawAmount = $request->input('amount');
        if (is_string($rawAmount)) {
            $cleaned = preg_replace('/[^\d]/', '', $rawAmount);
            $request->merge(['amount' => $cleaned === '' ? null : (int) $cleaned]);
        }

        $validated = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $store = Store::findOrFail($validated['store_id']);
        $actor = auth()->user();

        $row = StoreReceivableService::addOpeningBalance($validated, $actor);

        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'receivable_opening_balance',
            'title' => "{$actor->name} menambahkan saldo piutang awal untuk {$store->name} sebesar Rp" . number_format($row->amount, 0, ',', '.') . ".",
            'message' => "Saldo piutang awal untuk {$store->name} dicatat sebesar Rp" . number_format($row->amount, 0, ',', '.') . ".",
            'actor_id' => (string) $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role_label,
            'store_name' => $store->name,
            'info' => 'Saldo Awal Rp' . number_format($row->amount, 0, ',', '.'),
            'url' => route('admin.receivables.show', $store->id),
            'related_id' => (string) $row->id,
            'related_type' => 'StoreReceivable',
            'activity_time' => now()->toIso8601String(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Saldo piutang awal berhasil disimpan.', 'data' => $row], 201);
        }

        return back()->with('success', 'Saldo piutang awal berhasil disimpan.');
    }

    public function adjust(Request $request, string $storeId)
    {
        $store = Store::withTrashed()->findOrFail($storeId);

        $rawAmount = $request->input('amount');
        if (is_numeric($rawAmount) && (float) $rawAmount < 0) {
            $request->merge([
                'adjustment_type' => 'subtract',
                'amount' => abs((float) $rawAmount),
            ]);
        } elseif (is_string($rawAmount)) {
            $isNeg = str_contains($rawAmount, '-');
            $cleaned = preg_replace('/[^\d]/', '', $rawAmount);
            $num = $cleaned === '' ? null : (int) $cleaned;
            if ($isNeg && $num) {
                $request->merge([
                    'adjustment_type' => 'subtract',
                    'amount' => $num,
                ]);
            } else {
                $request->merge(['amount' => $num]);
            }
        }

        $validated = $request->validate([
            'adjustment_type' => ['nullable', 'string', 'in:add,subtract'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['required', 'string', 'min:5', 'max:500'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        $adjType = $validated['adjustment_type'] ?? 'add';
        $numAmount = (float) $validated['amount'];

        if ($numAmount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Nominal penyesuaian harus lebih besar dari 0.']);
        }

        $currentBalance = (float) StoreReceivableService::balanceForStore($store->id);

        if ($adjType === 'subtract' && $numAmount > $currentBalance) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pengurangan melebihi saldo piutang toko.',
            ]);
        }

        $signedAmount = $adjType === 'subtract' ? -$numAmount : $numAmount;

        $row = StoreReceivable::create([
            'store_id' => $store->id,
            'type' => StoreReceivableService::TYPE_ADJUSTMENT,
            'amount' => $signedAmount,
            'reference_type' => 'adjustment',
            'reference_id' => null,
            'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
            'payment_method' => null,
            'allocation' => null,
            'notes' => $validated['notes'],
            'created_by' => auth()->id(),
        ]);

        if ($adjType === 'add') {
            StoreReceivableService::createTransaction([
                'store_id' => $store->id,
                'transaction_amount' => $numAmount,
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                'description' => "Penyesuaian Tambah Piutang: {$validated['notes']}",
                'reference_type' => 'adjustment',
                'reference_id' => $row->id,
            ], auth()->user());
        } else {
            StoreReceivableService::recordPayment([
                'store_id' => $store->id,
                'amount' => $numAmount,
                'payment_method' => StoreReceivableService::METHOD_CASH,
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                'reference_type' => 'adjustment',
                'reference_id' => $row->id,
                'notes' => "Penyesuaian Kurangi Piutang: {$validated['notes']}",
            ], auth()->user());
        }

        $actor = auth()->user();
        ActivityNotificationService::notifyAdmins([
            'activity_type' => 'receivable_adjustment',
            'title' => "{$actor->name} melakukan penyesuaian piutang {$store->name} (" . ($adjType === 'add' ? '+Rp' : '-Rp') . number_format($numAmount, 0, ',', '.') . ").",
            'message' => "Penyesuaian piutang {$store->name} sebesar " . ($adjType === 'add' ? '+Rp' : '-Rp') . number_format($numAmount, 0, ',', '.') . ". Alasan: {$validated['notes']}",
            'actor_id' => (string) $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role_label,
            'store_name' => $store->name,
            'info' => 'Penyesuaian Rp' . number_format($numAmount, 0, ',', '.'),
            'url' => route('admin.receivables.show', $store->id),
            'related_id' => (string) $row->id,
            'related_type' => 'StoreReceivable',
            'activity_time' => now()->toIso8601String(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Penyesuaian berhasil disimpan.', 'data' => $row], 201);
        }

        return back()->with('success', 'Penyesuaian piutang berhasil disimpan.');
    }

    public function export(Request $request)
    {
        $request->validate([
            'sales_id' => ['nullable', 'exists:users,id'],
            'city' => ['nullable', 'string'],
            'receivable_status' => ['nullable', 'in:all,with,without'],
            'format' => ['nullable', 'in:pdf,excel'],
        ]);

        $stores = Store::where('status', 'active')
            ->when($request->sales_id, fn ($q) => $q->where('sales_penanggung_jawab_id', $request->sales_id))
            ->when($request->city, fn ($q) => $q->where('city', $request->city))
            ->orderBy('name')->get();

        foreach ($stores as $st) {
            $st->receivable_balance = (float) StoreReceivableService::balanceForStore($st->id);
        }

        if ($request->receivable_status === 'with') {
            $stores = $stores->filter(fn ($s) => $s->receivable_balance > 0.005)->values();
        } elseif ($request->receivable_status === 'without') {
            $stores = $stores->filter(fn ($s) => $s->receivable_balance <= 0.005)->values();
        }

        $format = $request->get('format', 'excel');
        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.receivables.pdf', compact('stores'));
            return $pdf->download('piutang-' . now()->format('Ymd_His') . '.pdf');
        }

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ReceivablesExport($stores), 'piutang-' . now()->format('Ymd_His') . '.xlsx');
    }
}

