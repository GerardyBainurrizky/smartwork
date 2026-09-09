<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DriverStoreController extends Controller
{
    /**
     * Tampilkan daftar toko yang merupakan tujuan pengiriman untuk Driver.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Total seluruh toko aktif yang merupakan tujuan pengiriman
        $totalStoresCount = Store::where('status', 'active')
            ->where('is_delivery_destination', true)
            ->count();

        $search = trim((string) $request->input('search', ''));

        $query = Store::query()
            ->where('status', 'active')
            ->where('is_delivery_destination', true)
            ->select([
                'id',
                'code',
                'name',
                'type',
                'address',
                'kecamatan',
                'city',
                'province',
                'latitude',
                'longitude',
                'maps_url',
                'phone',
            ])
            ->orderBy('name', 'asc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('kecamatan', 'like', "%{$search}%")
                  ->orWhere('province', 'like', "%{$search}%");
            });
        }

        $stores = $query->paginate(15)->withQueryString();

        return view('driver.stores.index', compact('stores', 'totalStoresCount', 'search'));
    }
}
