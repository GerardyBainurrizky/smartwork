<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="plus" title="Tambah Toko" subtitle="Tambahkan data master toko baru dan atur jangkauan Sales"></x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center mb-4 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-400 dark:hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-[#0DA4CE]/40 active:scale-[0.98] transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Master Toko
        </a>

        <div class="sw-card bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden"
             x-data="{
                 salesId: '{{ old('sales_penanggung_jawab_id', '') }}',
                 delivery: '{{ old('is_delivery_destination', '1') }}',
                 get hasSales() {
                     return this.salesId && this.salesId !== '';
                 }
             }">
            <div class="p-6">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Tambah Toko</h1>

                <form action="{{ route('admin.stores.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label class="sw-label" for="name">Nama Toko</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            class="sw-input {{ $errors->has('name') ? 'sw-input-error' : '' }}" required placeholder="Contoh: Toko Sumber Tani">
                        @error('name')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@else<div class="sw-input-helper">Nama resmi toko yang tercatat pada master.</div>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="sw-label" for="owner">Pemilik</label>
                            <input type="text" id="owner" name="owner" value="{{ old('owner') }}"
                                class="sw-input {{ $errors->has('owner') ? 'sw-input-error' : '' }}" placeholder="Contoh: H. Suryadi">
                            @error('owner')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="sw-label" for="phone">No. Telepon</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                class="sw-input {{ $errors->has('phone') ? 'sw-input-error' : '' }}" placeholder="Contoh: 0812-3456-7890">
                            @error('phone')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Sales Penanggung Jawab --}}
                    <div>
                        <label class="sw-label" for="sales_penanggung_jawab_id">Sales Penanggung Jawab</label>
                        <select id="sales_penanggung_jawab_id" name="sales_penanggung_jawab_id" x-model="salesId"
                            class="sw-input {{ $errors->has('sales_penanggung_jawab_id') ? 'sw-input-error' : '' }}">
                            <option value="">-- Belum Memiliki Sales (Toko Umum) --</option>
                            @foreach($salesUsers as $sales)
                                <option value="{{ $sales->id }}">
                                    {{ $sales->name }} ({{ $sales->username }})
                                </option>
                            @endforeach
                        </select>
                        @error('sales_penanggung_jawab_id')
                            <div class="sw-input-helper sw-helper-error">{{ $message }}</div>
                        @else
                            <div class="sw-input-helper text-gray-500 dark:text-gray-400">Menentukan jangkauan kunjungan Sales terhadap toko ini. Boleh dikosongkan.</div>
                        @enderror
                    </div>

                    {{-- Tujuan Pengiriman Driver (Diperbaiki sesuai Business Rule Final) --}}
                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700/60 bg-gray-50/75 dark:bg-gray-800/40 space-y-2">
                        <label class="sw-label">Tujuan Pengiriman Driver</label>

                        {{-- Mode Otomatis (Jika Memiliki Sales) --}}
                        <div x-show="hasSales" x-cloak>
                            <input type="hidden" name="is_delivery_destination" value="1" :disabled="!hasSales">
                            <div class="flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-3.5 py-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800/60 shadow-xs">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Ya — Otomatis</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                                Toko dengan Sales Penanggung Jawab otomatis menjadi tujuan pengiriman Driver.
                            </p>
                        </div>

                        {{-- Mode Manual (Jika Toko Umum / Belum Memiliki Sales) --}}
                        <div x-show="!hasSales" x-cloak>
                            <div class="flex gap-6 mt-1">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="is_delivery_destination" value="1" x-model="delivery" :disabled="hasSales"
                                        class="text-[#0DA4CE] focus:ring-[#0DA4CE] bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-medium">Ya</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="is_delivery_destination" value="0" x-model="delivery" :disabled="hasSales"
                                        class="text-[#0DA4CE] focus:ring-[#0DA4CE] bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tidak</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                                Untuk Toko Umum, Admin dapat menentukan apakah toko dapat menjadi tujuan pengiriman Driver.
                            </p>
                        </div>

                        @error('is_delivery_destination')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="sw-label" for="address">Alamat Toko</label>
                        <textarea id="address" name="address" rows="3"
                            class="sw-input {{ $errors->has('address') ? 'sw-input-error' : '' }}"
                            placeholder="Alamat lengkap toko" required>{{ old('address') }}</textarea>
                        @error('address')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <label class="sw-label" for="city">Kota</label>
                            <input type="text" id="city" name="city" value="{{ old('city') }}"
                                class="sw-input {{ $errors->has('city') ? 'sw-input-error' : '' }}" required placeholder="Jakarta Timur">
                            @error('city')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="sw-label" for="kecamatan">Kecamatan</label>
                            <input type="text" id="kecamatan" name="kecamatan" value="{{ old('kecamatan') }}"
                                class="sw-input {{ $errors->has('kecamatan') ? 'sw-input-error' : '' }}" placeholder="Kramat Jati">
                            @error('kecamatan')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="sw-label" for="province">Provinsi</label>
                            <input type="text" id="province" name="province" value="{{ old('province') }}"
                                class="sw-input {{ $errors->has('province') ? 'sw-input-error' : '' }}" required placeholder="DKI Jakarta">
                            @error('province')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="sw-label" for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" value="{{ old('latitude') }}" inputmode="decimal"
                                class="sw-input {{ $errors->has('latitude') ? 'sw-input-error' : '' }}" placeholder="cth. -6.2114">
                            @error('latitude')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@else<div class="sw-input-helper">Koordinat titik lokasi toko (opsional).</div>@enderror
                        </div>
                        <div>
                            <label class="sw-label" for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" value="{{ old('longitude') }}" inputmode="decimal"
                                class="sw-input {{ $errors->has('longitude') ? 'sw-input-error' : '' }}" placeholder="cth. 106.8456">
                            @error('longitude')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="sw-label" for="maps_url">Link Google Maps</label>
                        <input type="url" id="maps_url" name="maps_url" value="{{ old('maps_url') }}"
                            class="sw-input {{ $errors->has('maps_url') ? 'sw-input-error' : '' }}" placeholder="https://maps.app.goo.gl/...">
                        @error('maps_url')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@else<div class="sw-input-helper">Opsional. Dipergunakan untuk membuka navigasi menuju toko.</div>@enderror
                    </div>

                    <div>
                        <span class="sw-label">Status Toko</span>
                        <div class="mt-2 flex gap-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="status" value="active" class="text-[#0DA4CE] focus:ring-[#0DA4CE]" {{ old('status', 'active') === 'active' ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-medium">Aktif</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="status" value="inactive" class="text-[#0DA4CE] focus:ring-[#0DA4CE]" {{ old('status') === 'inactive' ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nonaktif</span>
                            </label>
                        </div>
                        @error('status')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 bg-[#0DA4CE] text-white rounded-xl font-semibold text-sm hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20">
                            Simpan
                        </button>
                        <a href="{{ route('admin.stores.index') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-semibold text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
