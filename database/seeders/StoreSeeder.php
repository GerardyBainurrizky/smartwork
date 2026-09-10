<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'code' => 'ST-001', 'name' => 'Toko Maju Jaya', 'type' => 'outlet',
                'address' => 'Jl. Raya Pagaden No. 12, Subang', 'city' => 'Subang', 'province' => 'Jawa Barat',
                'latitude' => -6.5697, 'longitude' => 107.7590, 'phone' => '0260-411234',
                'status' => 'active',
            ],
            [
                'code' => 'ST-002', 'name' => 'Toko Sumber Rezeki', 'type' => 'outlet',
                'address' => 'Jl. Otto Iskandardinata No. 45, Subang', 'city' => 'Subang', 'province' => 'Jawa Barat',
                'latitude' => -6.5697, 'longitude' => 107.7590, 'phone' => '0260-412345',
                'status' => 'active',
            ],
            [
                'code' => 'ST-003', 'name' => 'Toko Makmur Sentosa', 'type' => 'outlet',
                'address' => 'Jl. Pangeran Kornel No. 8, Bandung', 'city' => 'Bandung', 'province' => 'Jawa Barat',
                'latitude' => -6.9175, 'longitude' => 107.6191, 'phone' => '022-7001234',
                'status' => 'active',
            ],
            [
                'code' => 'ST-004', 'name' => 'Toko Berkah Abadi', 'type' => 'outlet',
                'address' => 'Jl. Raya Plered No. 21, Purwakarta', 'city' => 'Purwakarta', 'province' => 'Jawa Barat',
                'latitude' => -6.5563, 'longitude' => 107.4433, 'phone' => '0264-201234',
                'status' => 'active',
            ],
            [
                'code' => 'ST-005', 'name' => 'Toko Harapan Jaya', 'type' => 'outlet',
                'address' => 'Jl. Raya Cibogo No. 33, Subang', 'city' => 'Subang', 'province' => 'Jawa Barat',
                'latitude' => -6.5697, 'longitude' => 107.7590, 'phone' => '0260-413456',
                'status' => 'active',
            ],
            [
                'code' => 'ST-006', 'name' => 'Toko Cahaya Baru', 'type' => 'outlet',
                'address' => 'Jl. Riau No. 17, Bandung', 'city' => 'Bandung', 'province' => 'Jawa Barat',
                'latitude' => -6.9214, 'longitude' => 107.6090, 'phone' => '022-7002345',
                'status' => 'active',
            ],
            [
                'code' => 'ST-007', 'name' => 'Toko Mitra Tani', 'type' => 'outlet',
                'address' => 'Jl. Raya Cibinong No. 55, Purwakarta', 'city' => 'Purwakarta', 'province' => 'Jawa Barat',
                'latitude' => -6.5563, 'longitude' => 107.4433, 'phone' => '0264-202345',
                'status' => 'active',
            ],
            [
                'code' => 'ST-008', 'name' => 'Toko Agro Mandiri', 'type' => 'outlet',
                'address' => 'Jl. Raya Kalijati No. 27, Subang', 'city' => 'Subang', 'province' => 'Jawa Barat',
                'latitude' => -6.5697, 'longitude' => 107.7590, 'phone' => '0260-414567',
                'status' => 'active',
            ],
            [
                'code' => 'ST-009', 'name' => 'Toko Nusantara Tani', 'type' => 'outlet',
                'address' => 'Jl. Asia Afrika No. 90, Bandung', 'city' => 'Bandung', 'province' => 'Jawa Barat',
                'latitude' => -6.9175, 'longitude' => 107.6191, 'phone' => '022-7003456',
                'status' => 'active',
            ],
            [
                'code' => 'ST-010', 'name' => 'Toko Padi Sejahtera', 'type' => 'outlet',
                'address' => 'Jl. Raya Sadang No. 64, Purwakarta', 'city' => 'Purwakarta', 'province' => 'Jawa Barat',
                'latitude' => -6.5563, 'longitude' => 107.4433, 'phone' => '0264-203456',
                'status' => 'active',
            ],
        ];

        foreach ($stores as $store) {
            $store['id'] = (string) Str::uuid();
            Store::firstOrCreate(['code' => $store['code']], $store);
        }
    }
}