<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->get('status');
        $salesFilter = $request->get('sales_id');
        $deliveryFilter = $request->get('delivery');

        $query = Store::withTrashed()
            ->with('salesPenanggungJawab')
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('owner', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%")
                    ->orWhere('city', 'like', "%{$request->search}%")
                    ->orWhere('kecamatan', 'like', "%{$request->search}%")
                    ->orWhere('province', 'like', "%{$request->search}%");
            }));

        if ($statusFilter === 'active') {
            $query->where('status', 'active')->whereNull('deleted_at');
        } elseif ($statusFilter === 'inactive') {
            $query->where(function ($q) {
                $q->where('status', 'inactive')->orWhereNotNull('deleted_at');
            });
        }

        if ($salesFilter === 'unassigned') {
            $query->whereNull('sales_penanggung_jawab_id');
        } elseif ($salesFilter) {
            $query->where('sales_penanggung_jawab_id', $salesFilter);
        }

        if ($deliveryFilter === 'yes') {
            $query->where('is_delivery_destination', true);
        } elseif ($deliveryFilter === 'no') {
            $query->where('is_delivery_destination', false);
        }

        $stores = $query->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $salesUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Store::withTrashed()->count(),
            'active' => Store::where('status', 'active')->count(),
            'inactive' => Store::withTrashed()->where(function ($q) {
                $q->where('status', 'inactive')->orWhereNotNull('deleted_at');
            })->count(),
            'unassigned_sales' => Store::where('status', 'active')->whereNull('sales_penanggung_jawab_id')->count(),
            'delivery_enabled' => Store::where('status', 'active')->where('is_delivery_destination', true)->count(),
        ];

        // Ringkasan Toko per Sales dari Master Toko
        $storeCountsBySales = Store::where('status', 'active')
            ->selectRaw('sales_penanggung_jawab_id, count(*) as store_count')
            ->groupBy('sales_penanggung_jawab_id')
            ->pluck('store_count', 'sales_penanggung_jawab_id');

        $storesPerSales = $salesUsers->map(function ($sales) use ($storeCountsBySales) {
            return [
                'id' => $sales->id,
                'name' => $sales->name,
                'count' => $storeCountsBySales[$sales->id] ?? 0,
            ];
        })->filter(fn ($item) => $item['count'] > 0)->sortByDesc('count')->values();

        $unassignedStoresCount = $stats['unassigned_sales'];

        // Monitoring Toko Per Sales jika filter sales_id dipilih (Tahap 1, 2, 3)
        $salesMonitoring = null;
        $selectedSalesUser = null;
        if ($salesFilter && $salesFilter !== 'unassigned') {
            $selectedSalesUser = User::find($salesFilter);
            if ($selectedSalesUser) {
                $assignedStores = Store::where('sales_penanggung_jawab_id', $selectedSalesUser->id)->get();
                $assignedStoreIds = $assignedStores->pluck('id');

                $visitsByStore = Visit::whereIn('store_id', $assignedStoreIds)
                    ->where('user_id', $selectedSalesUser->id)
                    ->where('status', 'completed')
                    ->selectRaw('store_id, count(*) as count')
                    ->groupBy('store_id')
                    ->pluck('count', 'store_id')
                    ->toArray();

                $visitedCount = count($visitsByStore);
                $unvisitedCount = max(0, $assignedStores->count() - $visitedCount);
                $totalVisits = array_sum($visitsByStore);

                // Top visited stores (sering dikunjungi)
                $topVisited = $assignedStores->map(function ($s) use ($visitsByStore) {
                    $s->visit_count = $visitsByStore[$s->id] ?? 0;
                    return $s;
                })->filter(fn ($s) => $s->visit_count > 0)->sortByDesc('visit_count')->take(5);

                // Unvisited stores (belum pernah dikunjungi)
                $unvisitedStores = $assignedStores->filter(function ($s) use ($visitsByStore) {
                    return !isset($visitsByStore[$s->id]) || $visitsByStore[$s->id] === 0;
                })->take(5);

                $salesMonitoring = [
                    'total_assigned' => $assignedStores->count(),
                    'visited_count' => $visitedCount,
                    'unvisited_count' => $unvisitedCount,
                    'total_visits' => $totalVisits,
                    'top_visited' => $topVisited,
                    'unvisited_stores' => $unvisitedStores,
                ];
            }
        }

        return view('admin.stores.index', compact('stores', 'stats', 'salesUsers', 'storesPerSales', 'unassignedStoresCount', 'salesMonitoring', 'selectedSalesUser'));
    }

    public function bySales(Request $request): View
    {
        $salesUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->where('status', 'active')
            ->orderBy('name')
            ->with(['assignedStores' => fn ($q) => $q->where('status', 'active')->orderBy('name')])
            ->paginate(10, ['*'], 'sales_page')
            ->withQueryString();

        $unassignedStores = Store::where('status', 'active')
            ->whereNull('sales_penanggung_jawab_id')
            ->orderBy('name')
            ->get();

        $totalActiveStores = Store::where('status', 'active')->count();

        return view('admin.stores.by-sales', compact('salesUsers', 'unassignedStores', 'totalActiveStores'));
    }

    public function create(): View
    {
        $salesUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.stores.create', compact('salesUsers'));
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = $this->generateCode();
        $data['status'] = $request->input('status', 'active');

        // Business Rule: Jika toko memiliki Sales, otomatis tujuan pengiriman = true
        if (! empty($data['sales_penanggung_jawab_id'])) {
            $data['is_delivery_destination'] = true;
        } else {
            $data['is_delivery_destination'] = (bool) $request->input('is_delivery_destination', true);
        }

        Store::create($data);

        return redirect()->route('admin.stores.index')
            ->with('success', 'Toko berhasil ditambahkan.');
    }

    public function show(string $id): View
    {
        $store = Store::withTrashed()->with('salesPenanggungJawab')->findOrFail($id);

        $salesUserIds = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'field-supervisor']))->pluck('id');
        $driverUserIds = User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))->pluck('id');

        // Riwayat Kunjungan Sales (TERPISAH)
        $salesVisits = Visit::with(['user', 'route'])
            ->where('store_id', $store->id)
            ->whereIn('user_id', $salesUserIds)
            ->orderBy('check_in_at', 'desc')
            ->paginate(10, ['*'], 'sales_page')
            ->withQueryString();

        // Riwayat Pengiriman Driver (TERPISAH)
        $driverVisits = Visit::with(['user', 'route'])
            ->where('store_id', $store->id)
            ->whereIn('user_id', $driverUserIds)
            ->orderBy('check_in_at', 'desc')
            ->paginate(10, ['*'], 'driver_page')
            ->withQueryString();

        $stats = [
            'total_sales_visits' => Visit::where('store_id', $store->id)->whereIn('user_id', $salesUserIds)->where('status', 'completed')->count(),
            'total_driver_deliveries' => Visit::where('store_id', $store->id)->whereIn('user_id', $driverUserIds)->where('status', 'completed')->count(),
            'last_sales_visit' => Visit::where('store_id', $store->id)->whereIn('user_id', $salesUserIds)->where('status', 'completed')->latest('check_in_at')->first(),
            'last_driver_delivery' => Visit::where('store_id', $store->id)->whereIn('user_id', $driverUserIds)->where('status', 'completed')->latest('check_in_at')->first(),
        ];

        return view('admin.stores.show', compact('store', 'salesVisits', 'driverVisits', 'stats'));
    }

    public function edit(Store $store): View
    {
        $salesUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'sales'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.stores.edit', compact('store', 'salesUsers'));
    }

    public function update(StoreRequest $request, Store $store): RedirectResponse
    {
        $data = $request->validated();

        // Business Rule: Jika toko memiliki Sales, otomatis tujuan pengiriman = true.
        // Hanya jika Sales Penanggung Jawab NULL, Admin dapat menentukan is_delivery_destination.
        if (! empty($data['sales_penanggung_jawab_id'])) {
            $data['is_delivery_destination'] = true;
        } elseif ($request->has('is_delivery_destination')) {
            $data['is_delivery_destination'] = (bool) $request->input('is_delivery_destination');
        }

        $store->update($data);

        return redirect()->route('admin.stores.index')
            ->with('success', 'Toko berhasil diperbarui.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $store->update(['status' => 'inactive']);
        $store->delete();

        return redirect()->route('admin.stores.index')
            ->with('success', 'Toko berhasil dinonaktifkan.');
    }

    public function restore(string $id): RedirectResponse
    {
        $store = Store::withTrashed()->findOrFail($id);
        $store->restore();
        $store->update(['status' => 'active']);

        return redirect()->route('admin.stores.index')
            ->with('success', 'Toko berhasil diaktifkan kembali.');
    }

    private function generateCode(): string
    {
        $last = Store::withTrashed()
            ->where('code', 'like', 'ST-%')
            ->orderByDesc('code')
            ->value('code');

        $next = $last ? ((int) substr($last, 3)) + 1 : 1;

        return 'ST-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}

