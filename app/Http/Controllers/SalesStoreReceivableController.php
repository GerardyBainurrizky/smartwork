<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreTransaction;
use App\Services\StoreReceivableService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesStoreReceivableController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        if (! $user->hasRole('sales')) {
            abort(403, 'Hanya role Sales yang berhak mengakses menu ini.');
        }

        $query = Store::query()
            ->where('sales_penanggung_jawab_id', $user->id)
            ->where('status', 'active')
            ->with(['transactions.payments']);

        // Search: Nama toko, kode toko, alamat
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%");
            });
        }

        $allStores = $query->orderBy('name')->get();

        $totalReceivable = 0;
        $storesWithReceivable = 0;
        $totalOpenTransactions = 0;

        foreach ($allStores as $st) {
            $bal = (float) StoreReceivableService::balanceForStore($st->id);
            $st->receivable_balance = $bal;

            $openTrxCount = $st->transactions->filter(function ($trx) {
                return $trx->remaining_amount > 0.005;
            })->count();

            $st->open_transactions_count = $openTrxCount;
            $totalOpenTransactions += $openTrxCount;

            if ($bal > 0.005) {
                $storesWithReceivable++;
            }
            $totalReceivable += max(0, $bal);
        }

        $totalStores = $allStores->count();

        // Filter status: with (Memiliki Piutang), without (Tidak Ada Piutang), all
        $statusFilter = $request->get('status', 'all');
        $filteredStores = $allStores;
        if ($statusFilter === 'with') {
            $filteredStores = $allStores->filter(fn ($st) => $st->receivable_balance > 0.005)->values();
        } elseif ($statusFilter === 'without') {
            $filteredStores = $allStores->filter(fn ($st) => $st->receivable_balance <= 0.005)->values();
        }

        // Pagination: 10 per page
        $perPage = 10;
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $filteredStores->slice($offset, $perPage)->values();

        $stores = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $filteredStores->count(),
            $perPage,
            $page,
            ['path' => route('sales.stores.index'), 'query' => $request->query()]
        );

        return view('sales.stores.index', compact(
            'stores', 'totalStores', 'storesWithReceivable',
            'totalReceivable', 'totalOpenTransactions'
        ));
    }

    public function show(string $storeId): View
    {
        $user = auth()->user();

        if (! $user->hasRole('sales')) {
            abort(403, 'Akses ditolak.');
        }

        $store = Store::where('sales_penanggung_jawab_id', $user->id)
            ->findOrFail($storeId);

        $balance = (float) StoreReceivableService::balanceForStore($store->id);

        $transactions = StoreTransaction::with(['creator:id,name', 'payments.recorder:id,name'])
            ->where('store_id', $store->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $openTrxCount = StoreTransaction::with('payments')
            ->where('store_id', $store->id)
            ->get()
            ->filter(fn ($t) => $t->remaining_amount > 0.005)
            ->count();

        return view('sales.stores.show', compact('store', 'balance', 'openTrxCount', 'transactions'));
    }
}
