<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // Business Rule: Jika toko memiliki Sales, is_delivery_destination HARUS selalu true
        if ($this->filled('sales_penanggung_jawab_id')) {
            $this->merge([
                'is_delivery_destination' => true,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'owner' => ['nullable', 'string', 'max:200'],
            'sales_penanggung_jawab_id' => ['nullable', 'exists:users,id'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'maps_url' => ['nullable', 'url', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive'],
            'is_delivery_destination' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama toko wajib diisi.',
            'name.max' => 'Nama toko maksimal 200 karakter.',
            'owner.max' => 'Nama pemilik maksimal 200 karakter.',
            'sales_penanggung_jawab_id.exists' => 'Sales Penanggung Jawab tidak valid.',
            'address.required' => 'Alamat toko wajib diisi.',
            'address.max' => 'Alamat toko maksimal 500 karakter.',
            'city.required' => 'Kota wajib diisi.',
            'city.max' => 'Kota maksimal 100 karakter.',
            'kecamatan.max' => 'Kecamatan maksimal 100 karakter.',
            'province.required' => 'Provinsi wajib diisi.',
            'province.max' => 'Provinsi maksimal 100 karakter.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'maps_url.url' => 'Link Google Maps harus berupa URL yang valid.',
            'maps_url.max' => 'Link Google Maps maksimal 500 karakter.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'is_delivery_destination.required' => 'Status tujuan pengiriman wajib ditentukan.',
            'is_delivery_destination.boolean' => 'Pilihan tujuan pengiriman tidak valid.',
        ];
    }
}