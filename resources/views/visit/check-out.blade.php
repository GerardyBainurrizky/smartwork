<x-app-layout>
    @php
        $isDriver = Auth::user()->hasRole('driver');
        $routeId = $visit->route_id ?? $visit->routeStop?->route_id;
        $defaultRedirectUrl = $routeId ? route('route.show', $routeId) : ($isDriver ? route('route.index') : route('visit.history'));
    @endphp
    <x-slot name="header">
        <x-page-header icon="store" :title="$isDriver ? 'Check Out Pengiriman' : 'Check Out Kunjungan'" :subtitle="$isDriver ? 'Lengkapi foto dokumentasi barang, hasil pengiriman, dan konfirmasi lokasi' : 'Lengkapi hasil kunjungan dan konfirmasi lokasi'"></x-page-header>
    </x-slot>

    <div class="max-w-lg mx-auto" x-data="visitCheckOut()" x-init="init()">
        <x-back-button href="{{ route('visit.show', $visit->id) }}" :label="$isDriver ? 'Kembali ke Detail Pengiriman' : 'Kembali ke Detail'" class="mb-4" />

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60">
                <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">Check Out {{ $isDriver ? 'Pengiriman' : 'Kunjungan' }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-semibold">{{ $visit->store->name ?? 'Toko' }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $visit->store->address ?? '' }}</p>
            </div>

            <div class="p-5 space-y-5">
                {{-- 1. LOKASI --}}
                <div class="rounded-2xl border p-4.5 transition-all duration-200"
                    :class="gpsReady ? 'bg-green-50/70 dark:bg-green-950/20 border-green-200 dark:border-green-800/60' : (gpsDenied ? 'bg-red-50/70 dark:bg-red-950/20 border-red-200 dark:border-red-800/60' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700/60')">
                    
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-2xs mt-0.5"
                            :class="gpsReady ? 'bg-green-600 text-white' : (gpsDenied ? 'bg-red-500 text-white' : 'bg-amber-500 text-white')">
                            {{-- Icon Searching / Success / Denied --}}
                            <template x-if="!gpsReady && !gpsDenied">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="gpsReady">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <template x-if="gpsDenied">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </template>
                        </div>

                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="gpsTitle"></p>
                                <span x-show="gpsReady && gpsAccuracy" class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                    Akurasi ±<span x-text="Math.round(gpsAccuracy)"></span>m
                                </span>
                            </div>

                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed break-words" x-text="gpsHint"></p>

                            {{-- Info Detail Koordinat & Jarak Saat GPS Berhasil --}}
                            <div x-show="gpsReady" class="pt-2 border-t border-green-200/60 dark:border-green-800/40 grid grid-cols-2 gap-2 text-[11px]">
                                <div class="bg-white/80 dark:bg-gray-800/80 rounded-lg p-2 border border-green-100 dark:border-green-900/50">
                                    <span class="text-gray-400 dark:text-gray-500 block">Koordinat GPS:</span>
                                    <span class="font-mono font-bold text-gray-800 dark:text-gray-200 truncate block" x-text="(locationLat ? locationLat.toFixed(6) : '-') + ', ' + (locationLng ? locationLng.toFixed(6) : '-')"></span>
                                </div>
                                <div class="bg-white/80 dark:bg-gray-800/80 rounded-lg p-2 border border-green-100 dark:border-green-900/50">
                                    <span class="text-gray-400 dark:text-gray-500 block">Jarak dari Toko:</span>
                                    <span class="font-mono font-bold text-gray-800 dark:text-gray-200 block" x-text="storeDistanceText"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Coba Lagi / Deteksi Ulang --}}
                    <div x-show="gpsDenied || (!gpsReady && !isDetectingGps)" class="mt-3 pt-3 border-t border-red-200/60 dark:border-red-800/40">
                        <button type="button" @click="retryGps()"
                            class="w-full py-2 px-3 bg-[#0DA4CE] hover:bg-[#097A99] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Coba Ambil Lokasi Ulang</span>
                        </button>
                    </div>
                </div>

                {{-- Local HTTPS notice for GPS --}}
                <div x-show="secureGps" x-cloak
                    class="rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Fitur ini memerlukan koneksi HTTPS pada perangkat.</p>
                    </div>
                    <button type="button" @click="secureGps = false"
                        class="mt-3 w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-bold hover:bg-[#097A99] transition">
                        Tutup
                    </button>
                </div>

                @if($isDriver)
                {{-- KHUSUS DRIVER: STRUKTUR WORKFLOW PENGIRIMAN DRIVER --}}
                
                {{-- 2. TRANSAKSI PENGIRIMAN (OPSIONAL) --}}
                <div class="space-y-3 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-bold text-gray-800 dark:text-gray-200">
                            Apakah Ada Transaksi? <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <label class="cursor-pointer relative rounded-xl border p-3.5 flex items-start gap-3 transition active:scale-95"
                            :class="driverHasTransaction === false ? 'ring-2 ring-[#0DA4CE] bg-[#0DA4CE]/5 border-[#0DA4CE]' : 'ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800'">
                            <input type="radio" name="driver_has_tx" :value="false" x-model="driverHasTransaction" @change="onDriverTxToggle(false)" class="sr-only">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 mt-0.5" :class="driverHasTransaction === false ? 'bg-[#0DA4CE] text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">Tidak Ada Transaksi</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 leading-relaxed">Tidak ada transaksi pada pengiriman ini</p>
                            </div>
                        </label>

                        <label class="cursor-pointer relative rounded-xl border p-3.5 flex items-start gap-3 transition active:scale-95"
                            :class="driverHasTransaction === true ? 'ring-2 ring-emerald-500 bg-emerald-50/20 border-emerald-500' : 'ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800'">
                            <input type="radio" name="driver_has_tx" :value="true" x-model="driverHasTransaction" @change="onDriverTxToggle(true)" class="sr-only">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 mt-0.5" :class="driverHasTransaction === true ? 'bg-emerald-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">Ada Transaksi</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 leading-relaxed">Terdapat transaksi pembayaran</p>
                            </div>
                        </label>
                    </div>

                    {{-- Form Nominal & Metode Pembayaran jika ADA TRANSAKSI --}}
                    <div x-show="driverHasTransaction === true"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="pt-3 border-t border-gray-200 dark:border-gray-700/60 space-y-3.5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Nominal Transaksi <span class="text-red-500">*</span>
                            </label>
                            <div class="flex rounded-xl shadow-2xs border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition overflow-hidden">
                                <span class="inline-flex items-center px-3.5 text-sm font-bold font-mono text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border-r border-gray-300 dark:border-gray-600 select-none">
                                    Rp
                                </span>
                                <input type="text" inputmode="numeric" :value="formattedDriverTxAmount" @input="onDriverTxAmountInput($event)"
                                    class="w-full bg-transparent text-sm py-3 px-3 font-mono text-gray-900 dark:text-white outline-none focus:outline-none focus:ring-0 border-0" placeholder="0">
                            </div>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">Masukkan nominal transaksi dalam Rupiah</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Metode Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition active:scale-95 bg-white dark:bg-gray-800"
                                    :class="driverPaymentMethod === 'tunai' ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/20' : 'ring-1 border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="driver_payment_method" value="tunai" x-model="driverPaymentMethod" class="sr-only">
                                    <svg class="w-4 h-4" :class="driverPaymentMethod === 'tunai' ? 'text-emerald-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <span class="text-xs font-bold">Tunai</span>
                                </label>
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition active:scale-95 bg-white dark:bg-gray-800"
                                    :class="driverPaymentMethod === 'transfer' ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/20' : 'ring-1 border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="driver_payment_method" value="transfer" x-model="driverPaymentMethod" class="sr-only">
                                    <svg class="w-4 h-4" :class="driverPaymentMethod === 'transfer' ? 'text-emerald-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span class="text-xs font-bold">Transfer</span>
                                </label>
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition active:scale-95 bg-white dark:bg-gray-800"
                                    :class="driverPaymentMethod === 'qris' ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/20' : 'ring-1 border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="driver_payment_method" value="qris" x-model="driverPaymentMethod" class="sr-only">
                                    <svg class="w-4 h-4" :class="driverPaymentMethod === 'qris' ? 'text-emerald-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 4h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                    <span class="text-xs font-bold">QRIS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. HASIL PENGIRIMAN & CATATAN TAMBAHAN --}}
                <div>
                    <label for="visit_result" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
                        Hasil Pengiriman <span class="text-red-500">*</span>
                    </label>
                    <textarea id="visit_result" x-model="form.visit_result" rows="2"
                        class="sw-input"
                        placeholder="Contoh: Barang diterima lengkap oleh pemilik toko"
                        maxlength="500"></textarea>
                </div>

                <div>
                    <label for="final_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
                        Catatan Tambahan <span class="text-gray-400 dark:text-gray-500 font-normal">(opsional)</span>
                    </label>
                    <textarea id="final_notes" x-model="form.final_notes" rows="2"
                        class="sw-input"
                        placeholder="Contoh: Barang diterima oleh bagian gudang / serah terima selesai..."
                        maxlength="500"></textarea>
                </div>

                {{-- 4. FOTO BARANG / DOKUMEN PENGIRIMAN (DINAMIS, MAKS 6) --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-bold text-gray-800 dark:text-gray-200">
                            Foto Barang/Dokumen Pengiriman <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Pengambilan maksimal 6 foto</p>
                    </div>

                    {{-- Indikator Status Jumlah Foto (Informational Indicator - Non-Clickable) --}}
                    <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700/60 select-none">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full" :class="driverPhotos.length >= 6 ? 'bg-amber-500' : (driverPhotos.length > 0 ? 'bg-[#0DA4CE]' : 'bg-gray-400')"></div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Foto Dokumentasi</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-bold font-mono text-gray-800 dark:text-gray-200" x-text="driverPhotos.length"></span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">/ 6</span>
                        </div>
                    </div>

                    {{-- Peringatan sudah penuh --}}
                    <div x-show="driverPhotos.length >= 6" x-cloak
                        class="flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-700 dark:text-amber-400 text-xs font-medium">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Batas maksimal 6 foto telah tercapai. Hapus salah satu foto untuk mengambil foto baru.</span>
                    </div>

                    {{-- List Foto Terambil --}}
                    <div x-show="driverPhotos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <template x-for="(photo, index) in driverPhotos" :key="index">
                            <div class="relative rounded-xl overflow-hidden aspect-[4/3] bg-black border border-gray-200 dark:border-gray-700 group shadow-xs">
                                <img :src="photo" class="w-full h-full object-cover" :alt="'Foto ' + (index + 1)">
                                <div class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-md bg-black/60 text-white text-[10px] font-bold font-mono">
                                    <span x-text="'#' + (index + 1)"></span>
                                </div>
                                <button type="button" @click="removeDriverPhoto(index)"
                                    class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center shadow-sm hover:bg-red-700 transition active:scale-95"
                                    title="Hapus Foto">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- Area Kamera Driver (Saat Aktif) --}}
                    <div x-show="cameraActive" class="space-y-3">
                        <div class="relative bg-gray-900 rounded-2xl overflow-hidden aspect-[4/3] w-full shadow-inner border border-gray-200 dark:border-gray-700">
                            <video id="selfie-camera" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"
                                :class="selfieCameraReady ? '' : 'hidden'"></video>
                            
                            <div x-show="!selfieCameraReady" class="absolute inset-0 flex items-center justify-center bg-gray-900 text-white text-xs">
                                Memuat kamera...
                            </div>

                            <button type="button" @click.stop="flipSelfieCamera()" x-show="selfieCameraReady"
                                class="absolute top-3.5 right-3.5 z-20 px-3 py-1.5 rounded-full bg-black/60 backdrop-blur-sm text-white border border-white/20 text-xs font-semibold hover:bg-black/80 transition flex items-center gap-1.5 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Balik Kamera</span>
                            </button>

                            <div x-show="selfieCameraReady" class="absolute bottom-4 left-0 right-0 flex justify-center items-center gap-4 z-20">
                                <button type="button" @click="cancelCamera()" class="px-4 py-2 rounded-full bg-black/60 text-white text-xs font-medium hover:bg-black/80 transition">
                                    Batal
                                </button>
                                <button type="button" @click.stop="captureDriverPhoto()"
                                    class="w-16 h-16 rounded-full bg-white/95 shadow-2xl border-4 border-[#0DA4CE] flex items-center justify-center hover:scale-105 active:scale-95 transition transform">
                                    <div class="w-11 h-11 rounded-full bg-[#0DA4CE]"></div>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Tambah Foto (hanya tampil jika < 6 dan kamera tidak aktif) --}}
                    <div x-show="!cameraActive && driverPhotos.length < 6">
                        <button type="button" @click="openDriverCamera()"
                            class="w-full py-3.5 px-4 rounded-xl border-2 border-dashed border-[#0DA4CE]/40 dark:border-[#0DA4CE]/30 bg-[#0DA4CE]/5 dark:bg-[#0DA4CE]/10 hover:bg-[#0DA4CE]/15 text-[#0DA4CE] dark:text-[#5BD8F7] font-semibold text-sm flex items-center justify-center gap-2 transition active:scale-95">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span x-text="driverPhotos.length === 0 ? 'Ambil Foto Barang/Dokumen Pengiriman' : 'Tambah Foto Lainnya'"></span>
                        </button>
                    </div>
                </div>
                @else
                {{-- KHUSUS SALES: URUTAN FIELD SESUAI SPESIFIKASI --}}

                {{-- 2. PIUTANG TOKO SAAT INI (INFORMASI ONLY) --}}
                <div class="bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 p-4 rounded-xl flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Piutang Toko Saat Ini</p>
                        @php
                            $stReceivable = \App\Services\StoreReceivableService::balanceForStore($visit->store_id);
                        @endphp
                        @if((float)$stReceivable > 0.005)
                            <p class="text-xl font-extrabold text-red-600 dark:text-red-400 mt-0.5">Rp {{ number_format($stReceivable, 0, ',', '.') }}</p>
                        @else
                            <p class="text-base font-bold text-green-600 dark:text-green-400 mt-0.5">Tidak Ada Piutang</p>
                        @endif
                    </div>
                </div>

                {{-- 3. STATUS TRANSAKSI / PEMBAYARAN * --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
                        Status Transaksi / Pembayaran <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <label class="cursor-pointer relative rounded-xl border p-3 flex items-center gap-3 transition"
                            :class="form.transaction_status === 'none' ? 'ring-2 ring-[#0DA4CE] bg-[#0DA4CE]/5' : 'ring-1 ring-gray-200 bg-gray-50 dark:bg-gray-800/40'">
                            <input type="radio" name="transaction_status" value="none" x-model="form.transaction_status" @change="resetTransaction()" class="sr-only">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" :class="form.transaction_status === 'none' ? 'bg-[#0DA4CE] text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">Tidak Ada Pembayaran</p>
                            </div>
                        </label>

                        <label class="cursor-pointer relative rounded-xl border p-3 flex items-center gap-3 transition"
                            :class="form.transaction_status === 'piutang' ? 'ring-2 ring-red-500 bg-red-50 dark:bg-red-950/20' : 'ring-1 ring-gray-200 bg-gray-50 dark:bg-gray-800/40'">
                            <input type="radio" name="transaction_status" value="piutang" x-model="form.transaction_status" class="sr-only">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" :class="form.transaction_status === 'piutang' ? 'bg-red-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M3 5h18a2 2 0 012 2v10a2 2 0 01-2 2H3a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">Bayar Piutang Lama</p>
                            </div>
                        </label>

                        <label class="cursor-pointer relative rounded-xl border p-3 flex items-center gap-3 transition"
                            :class="form.transaction_status === 'paid' ? 'ring-2 ring-emerald-500 bg-emerald-50 dark:bg-emerald-950/20' : 'ring-1 ring-gray-200 bg-gray-50 dark:bg-gray-800/40'">
                            <input type="radio" name="transaction_status" value="paid" x-model="form.transaction_status" class="sr-only">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" :class="form.transaction_status === 'paid' ? 'bg-emerald-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">Bayar Transaksi Baru</p>
                            </div>
                        </label>

                        <label class="cursor-pointer relative rounded-xl border p-3 flex items-center gap-3 transition"
                            :class="form.transaction_status === 'mixed' ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-950/20' : 'ring-1 ring-gray-200 bg-gray-50 dark:bg-gray-800/40'">
                            <input type="radio" name="transaction_status" value="mixed" x-model="form.transaction_status" class="sr-only">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" :class="form.transaction_status === 'mixed' ? 'bg-indigo-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-100">Piutang Lama + Transaksi Baru</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- SECTION 1: BAYAR PIUTANG LAMA --}}
                <template x-if="form.transaction_status === 'piutang' || form.transaction_status === 'mixed'">
                    <div class="space-y-4 p-4 rounded-xl border border-red-200 bg-red-50/30 dark:border-red-900/30">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 pb-2 border-b border-red-200/60 dark:border-red-900/40">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-red-800 dark:text-red-300 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M3 5h18a2 2 0 012 2v10a2 2 0 01-2 2H3a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                <span>Bayar Piutang Lama</span>
                            </h3>
                            <span class="text-xs font-bold text-red-700 dark:text-red-400">Total Piutang: Rp {{ number_format($stReceivable, 0, ',', '.') }}</span>
                        </div>

                        {{-- Instruksi Pemilihan Transaksi --}}
                        <template x-if="openTransactions.length > 0">
                            <div class="space-y-3">
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium">
                                    Silakan pilih transaksi piutang yang akan dibayar.
                                </p>

                                {{-- Daftar Seluruh Transaksi Piutang Terbuka dengan Checkbox & Input Muncul Tepat di Bawah Item yang Dipilih --}}
                                <div class="space-y-3">
                                    <template x-for="(trx, idx) in openTransactions" :key="trx.id">
                                        <div class="p-3.5 bg-white dark:bg-gray-800 rounded-xl border transition-all duration-150 shadow-2xs space-y-3"
                                            :class="isTrxSelected(trx.id) ? 'border-red-400 dark:border-red-700 ring-2 ring-red-400/20 bg-red-50/10' : 'border-gray-200 dark:border-gray-700'">
                                            
                                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2.5">
                                                <div class="flex items-start gap-2.5 min-w-0">
                                                    <input type="checkbox" :id="'trx_chk_' + trx.id" :checked="isTrxSelected(trx.id)" @change="toggleTrxSelection(trx.id)"
                                                        class="mt-1 w-4 h-4 rounded text-red-600 focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 cursor-pointer shrink-0">
                                                    <label :for="'trx_chk_' + trx.id" class="cursor-pointer select-none min-w-0">
                                                        <p class="text-xs font-bold text-gray-900 dark:text-white font-mono break-all" x-text="trx.transaction_code"></p>
                                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 break-words" x-text="trx.transaction_date + (trx.description ? ' • ' + trx.description : '')"></p>
                                                    </label>
                                                </div>
                                                <div class="sm:text-right pl-6 sm:pl-0 space-y-0.5">
                                                    <span class="text-xs font-bold font-mono text-red-600 dark:text-red-400 block" x-text="'Sisa Piutang: Rp ' + Number(trx.remaining_amount).toLocaleString('id-ID')"></span>
                                                    <span class="text-[10px] text-gray-400 block" x-text="'Total Transaksi: Rp ' + Number(trx.transaction_amount).toLocaleString('id-ID')"></span>
                                                    <span class="text-[10px] text-gray-400 block" x-text="'Sudah Dibayar: Rp ' + Number(trx.total_paid).toLocaleString('id-ID')"></span>
                                                </div>
                                            </div>

                                            {{-- Input Nominal Pembayaran & Sisa Setelah Pembayaran Muncul Tepat di Bawah Transaksi yang Dipilih --}}
                                            <div x-show="isTrxSelected(trx.id)" x-transition class="pt-3 border-t border-red-200/80 dark:border-red-900/50 space-y-2.5">
                                                <div>
                                                    <div class="flex items-center justify-between mb-1.5">
                                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300">
                                                             Nominal Pembayaran <span class="text-red-500">*</span>
                                                        </label>
                                                        <button type="button" @click="setFullTrxPayment(trx.id, trx.remaining_amount)" class="text-[10px] text-red-600 dark:text-red-400 font-bold hover:underline">
                                                            Bayar Penuh
                                                        </button>
                                                    </div>
                                                    <div class="flex rounded-xl shadow-2xs border transition overflow-hidden"
                                                        :class="(Number(trxAllocations[trx.id]) || 0) <= 0 ? 'border-amber-400 dark:border-amber-600 focus-within:ring-2 focus-within:ring-amber-400/20' : 'border-gray-300 dark:border-gray-600 focus-within:ring-2 focus-within:ring-[#0DA4CE]/20 focus-within:border-[#0DA4CE]'">
                                                        <span class="inline-flex items-center px-3 text-xs font-bold font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700/60 border-r border-gray-300 dark:border-gray-600 select-none">
                                                            Rp
                                                        </span>
                                                        <input type="text" inputmode="numeric" :value="formatTrxAllocDisplay(trx.id)" @input="onTrxAllocInput(trx.id, $event, trx.remaining_amount)"
                                                            class="w-full bg-transparent text-xs py-2 px-3 font-mono text-gray-900 dark:text-white outline-none focus:outline-none focus:ring-0 border-0"
                                                            placeholder="0">
                                                    </div>
                                                    <template x-if="(Number(trxAllocations[trx.id]) || 0) <= 0">
                                                        <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-1 font-medium">
                                                            * Wajib diisi > Rp0 atau batalkan pilihan (uncheck) jika tidak ingin membayar transaksi ini.
                                                        </p>
                                                    </template>
                                                </div>

                                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-[11px] px-3 py-2 bg-gray-50 dark:bg-gray-800/60 rounded-lg border border-gray-100 dark:border-gray-700/50">
                                                    <span class="text-gray-600 dark:text-gray-400 font-medium">Sisa Setelah Pembayaran</span>
                                                    <span class="font-bold font-mono" :class="getTrxRemainingAfterPay(trx.id, trx.remaining_amount) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                                        x-text="'Rp ' + Number(getTrxRemainingAfterPay(trx.id, trx.remaining_amount)).toLocaleString('id-ID')"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Fallback jika tidak ada open StoreTransaction tapi saldo legacy > 0 --}}
                        <template x-if="openTransactions.length === 0">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Nominal Pembayaran Piutang <span class="text-red-500">*</span></label>
                                <div class="flex rounded-xl shadow-2xs border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus-within:ring-2 focus-within:ring-[#0DA4CE]/20 focus-within:border-[#0DA4CE] transition overflow-hidden">
                                    <span class="inline-flex items-center px-3 text-xs font-bold font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700/60 border-r border-gray-300 dark:border-gray-600 select-none">
                                        Rp
                                    </span>
                                    <input type="text" inputmode="numeric" :value="formattedAmount" @input="onAmountInput($event)"
                                        class="w-full bg-transparent text-xs py-2 px-3 font-mono text-gray-900 dark:text-white outline-none focus:outline-none focus:ring-0 border-0" placeholder="0">
                                </div>
                            </div>
                        </template>

                        {{-- Ringkasan Pembayaran Piutang Lama --}}
                        <div x-show="openTransactions.length === 0 ? Number(form.transaction_amount) > 0 : selectedTrxIds.length > 0" class="p-3.5 bg-red-100/70 dark:bg-red-950/40 rounded-xl space-y-2 text-xs font-bold text-red-900 dark:text-red-200 border border-red-200 dark:border-red-900/50">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 pb-1.5 border-b border-red-200/80 dark:border-red-900/60">
                                <span class="font-bold uppercase tracking-wide text-[11px]">Ringkasan Pembayaran Piutang Lama</span>
                                <span class="text-[11px] font-medium" x-text="selectedTrxIds.length + ' transaksi dipilih'"></span>
                            </div>

                            <template x-if="openTransactions.length > 0">
                                <div class="space-y-1 font-normal">
                                    <template x-for="trx in selectedTransactions" :key="'summary_' + trx.id">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-0.5 text-[11px]">
                                            <span class="font-mono text-gray-700 dark:text-gray-300" x-text="trx.transaction_code"></span>
                                            <span class="font-mono font-semibold" x-text="'Rp ' + Number(trxAllocations[trx.id] || 0).toLocaleString('id-ID')"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 pt-1.5 border-t border-red-200 dark:border-red-900/60">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Total Pembayaran Piutang Lama:</span>
                                <span class="font-mono font-bold text-green-700 dark:text-green-400" x-text="'Rp ' + Number(totalOldDebtPayment).toLocaleString('id-ID')"></span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Sisa Piutang Toko Setelah Pembayaran:</span>
                                <span class="font-mono font-bold text-red-700 dark:text-red-300" x-text="'Rp ' + Math.max(0, {{ (float)$stReceivable }} - totalOldDebtPayment).toLocaleString('id-ID')"></span>
                            </div>
                        </div>

                        {{-- Metode Pembayaran Piutang Lama --}}
                        <div x-show="openTransactions.length === 0 ? Number(form.transaction_amount) > 0 : selectedTrxIds.length > 0">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Metode Pembayaran Piutang <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition bg-white dark:bg-gray-800"
                                    :class="form.old_payment_method === 'tunai' ? 'ring-2 ring-red-500' : 'ring-1 border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="old_payment_method" value="tunai" x-model="form.old_payment_method" class="sr-only">
                                    <span class="text-xs font-bold">Tunai</span>
                                </label>
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition bg-white dark:bg-gray-800"
                                    :class="form.old_payment_method === 'transfer' ? 'ring-2 ring-red-500' : 'ring-1 border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="old_payment_method" value="transfer" x-model="form.old_payment_method" class="sr-only">
                                    <span class="text-xs font-bold">Transfer</span>
                                </label>
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition bg-white dark:bg-gray-800"
                                    :class="form.old_payment_method === 'qris' ? 'ring-2 ring-red-500' : 'ring-1 border-gray-200 dark:border-gray-700'">
                                    <input type="radio" name="old_payment_method" value="qris" x-model="form.old_payment_method" class="sr-only">
                                    <span class="text-xs font-bold">QRIS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- SECTION 2: TRANSAKSI BARU --}}
                <template x-if="form.transaction_status === 'paid' || form.transaction_status === 'mixed'">
                    <div class="space-y-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50/30 dark:border-emerald-900/30">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 pb-2 border-b border-emerald-200/60 dark:border-emerald-900/40">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>Transaksi Baru</span>
                            </h3>
                            <span class="text-[11px] font-mono text-gray-500 dark:text-gray-400">Kode: Otomatis</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Total Nilai Transaksi Baru <span class="text-red-500">*</span></label>
                            <div class="flex rounded-xl shadow-2xs border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition overflow-hidden">
                                <span class="inline-flex items-center px-3 text-xs font-bold font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700/60 border-r border-gray-300 dark:border-gray-600 select-none">
                                    Rp
                                </span>
                                <input type="text" inputmode="numeric" :value="formattedNewTxTotal" @input="onNewTxTotalInput($event)"
                                    class="w-full bg-transparent text-xs py-2 px-3 font-mono text-gray-900 dark:text-white outline-none focus:outline-none focus:ring-0 border-0" placeholder="0">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Pembayaran Saat Ini <span class="text-gray-400 font-normal">(0 jika tempo)</span></label>
                            <div class="flex rounded-xl shadow-2xs border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition overflow-hidden">
                                <span class="inline-flex items-center px-3 text-xs font-bold font-mono text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700/60 border-r border-gray-300 dark:border-gray-600 select-none">
                                    Rp
                                </span>
                                <input type="text" inputmode="numeric" :value="formattedNewTxPaid" @input="onNewTxPaidInput($event)"
                                    class="w-full bg-transparent text-xs py-2 px-3 font-mono text-gray-900 dark:text-white outline-none focus:outline-none focus:ring-0 border-0" placeholder="0">
                            </div>
                        </div>

                        <div class="p-3 bg-emerald-100/60 dark:bg-emerald-950/40 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs font-bold">
                            <span class="text-emerald-900 dark:text-emerald-200">Sisa Piutang Baru:</span>
                            <span :class="newTxRemaining > 0 ? 'text-red-600 font-bold' : 'text-emerald-700 font-bold'" x-text="'Rp ' + Number(newTxRemaining).toLocaleString('id-ID')"></span>
                        </div>

                        {{-- Metode Pembayaran Transaksi Baru --}}
                        <div x-show="form.new_tx_paid > 0">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Metode Pembayaran Transaksi Baru <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition bg-white dark:bg-gray-800"
                                    :class="form.new_payment_method === 'tunai' ? 'ring-2 ring-emerald-500' : 'ring-1 ring-gray-200'">
                                    <input type="radio" name="new_payment_method" value="tunai" x-model="form.new_payment_method" class="sr-only">
                                    <span class="text-xs font-bold">Tunai</span>
                                </label>
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition bg-white dark:bg-gray-800"
                                    :class="form.new_payment_method === 'transfer' ? 'ring-2 ring-emerald-500' : 'ring-1 ring-gray-200'">
                                    <input type="radio" name="new_payment_method" value="transfer" x-model="form.new_payment_method" class="sr-only">
                                    <span class="text-xs font-bold">Transfer</span>
                                </label>
                                <label class="cursor-pointer relative rounded-xl border p-2.5 flex flex-col items-center gap-1.5 text-center transition bg-white dark:bg-gray-800"
                                    :class="form.new_payment_method === 'qris' ? 'ring-2 ring-emerald-500' : 'ring-1 ring-gray-200'">
                                    <input type="radio" name="new_payment_method" value="qris" x-model="form.new_payment_method" class="sr-only">
                                    <span class="text-xs font-bold">QRIS</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- 4. HASIL KUNJUNGAN * --}}
                <div>
                    <label for="visit_result" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
                        Hasil Kunjungan <span class="text-red-500">*</span>
                    </label>
                    <textarea id="visit_result" x-model="form.visit_result" rows="3"
                        class="sw-input"
                        placeholder="Hasil dari kunjungan ke toko ini"
                        maxlength="500"></textarea>
                </div>

                {{-- 5. CATATAN TAMBAHAN (OPSIONAL) --}}
                <div>
                    <label for="final_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
                        Catatan Tambahan <span class="text-gray-400 dark:text-gray-500 font-normal">(opsional)</span>
                    </label>
                    <textarea id="final_notes" x-model="form.final_notes" rows="2"
                        class="sw-input"
                        placeholder="Tambahkan catatan jika diperlukan..."
                        maxlength="500"></textarea>
                </div>

                {{-- 6. FOTO ETALASE --}}
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-bold text-gray-800 dark:text-gray-200">
                            Foto Etalase <span class="text-red-500">*</span>
                        </label>
                        <span x-show="storefrontPhoto" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Sudah Diambil
                        </span>
                    </div>

                    <template x-if="!storefrontPhoto">
                        <div class="space-y-3">
                            <div class="relative bg-gray-900 rounded-2xl overflow-hidden aspect-[4/3] w-full shadow-inner border border-gray-200 dark:border-gray-700">
                                {{-- Video Element --}}
                                <video id="storefront-camera" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"
                                    :class="activeCamera === 'storefront' && storefrontCameraReady ? '' : 'hidden'"></video>
                                
                                {{-- Placeholder saat kamera belum dibuka --}}
                                <div x-show="activeCamera !== 'storefront' || !storefrontCameraReady" @click="openCamera('storefront')"
                                    class="absolute inset-0 flex flex-col items-center justify-center p-5 text-center cursor-pointer bg-gray-900/95 hover:bg-gray-900 transition">
                                    <div class="w-14 h-14 rounded-2xl bg-white/10 text-white flex items-center justify-center mb-3">
                                        <svg class="w-7 h-7 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-white">Buka Kamera Foto Etalase</p>
                                    <p class="text-xs text-gray-400 mt-1 max-w-xs">Ketuk di sini untuk mengambil foto etalase toko</p>
                                </div>

                                {{-- Tombol Balik Kamera di Pojok Atas --}}
                                <button type="button" @click.stop="flipCamera('storefront')" x-show="activeCamera === 'storefront' && storefrontCameraReady"
                                    class="absolute top-3.5 right-3.5 z-20 px-3 py-1.5 rounded-full bg-black/60 backdrop-blur-sm text-white border border-white/20 text-xs font-semibold hover:bg-black/80 transition flex items-center gap-1.5 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span x-text="storefrontFacing === 'user' ? 'Kamera Belakang' : 'Kamera Depan'"></span>
                                </button>

                                {{-- Tombol Kontrol Batal & Ambil Foto di Bawah --}}
                                <div x-show="activeCamera === 'storefront' && storefrontCameraReady" class="absolute bottom-4 left-0 right-0 flex justify-center items-center gap-4 z-20 px-4">
                                    <button type="button" @click.stop="cancelStorefrontCamera()" class="px-3.5 py-2 rounded-xl bg-black/60 hover:bg-black/80 text-white text-xs font-semibold backdrop-blur-xs transition">
                                        Batal
                                    </button>
                                    <button type="button" @click.stop="captureStorefront()"
                                        class="px-5 py-2.5 rounded-xl bg-[#0DA4CE] hover:bg-[#097A99] text-white text-xs font-bold shadow-lg flex items-center gap-2 active:scale-95 transition">
                                        <div class="w-3.5 h-3.5 rounded-full bg-white animate-pulse"></div>
                                        <span>Ambil Foto</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="storefrontPhoto">
                        <div class="relative rounded-2xl overflow-hidden aspect-[4/3] w-full shadow-sm border border-gray-200 dark:border-gray-700 bg-black">
                            <img :src="storefrontPhoto" class="w-full h-full object-cover" alt="Foto Etalase">
                            <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent flex justify-between items-center">
                                <span class="text-[11px] font-semibold text-emerald-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Foto Etalase Terpasang
                                </span>
                                <button type="button" @click="retakeStorefront()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-bold hover:bg-white transition shadow-sm active:scale-95">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Ambil Ulang</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- 7. FOTO SELFIE --}}
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-bold text-gray-800 dark:text-gray-200">
                            Foto Selfie <span class="text-red-500">*</span>
                        </label>
                        <span x-show="selfiePhoto" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Sudah Diambil
                        </span>
                    </div>

                    <template x-if="!selfiePhoto">
                        <div class="space-y-3">
                            <div class="relative bg-gray-900 rounded-2xl overflow-hidden aspect-[4/3] w-full shadow-inner border border-gray-200 dark:border-gray-700">
                                <video id="selfie-camera" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"
                                    :class="activeCamera === 'selfie' && selfieCameraReady ? '' : 'hidden'"></video>
                                
                                <div x-show="activeCamera !== 'selfie' || !selfieCameraReady" @click="openCamera('selfie')"
                                    class="absolute inset-0 flex flex-col items-center justify-center p-5 text-center cursor-pointer bg-gray-900/95 hover:bg-gray-900 transition">
                                    <div class="w-14 h-14 rounded-2xl bg-white/10 text-white flex items-center justify-center mb-3">
                                        <svg class="w-7 h-7 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-white">Buka Kamera Selfie</p>
                                    <p class="text-xs text-gray-400 mt-1 max-w-xs">Ketuk di sini untuk mengambil foto selfie sebelum check out</p>
                                </div>

                                <button type="button" @click.stop="flipCamera('selfie')" x-show="activeCamera === 'selfie' && selfieCameraReady"
                                    class="absolute top-3.5 right-3.5 z-20 px-3 py-1.5 rounded-full bg-black/60 backdrop-blur-sm text-white border border-white/20 text-xs font-semibold hover:bg-black/80 transition flex items-center gap-1.5 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span x-text="selfieFacing === 'user' ? 'Kamera Belakang' : 'Kamera Depan'"></span>
                                </button>

                                <div x-show="activeCamera === 'selfie' && selfieCameraReady" class="absolute bottom-4 left-0 right-0 flex justify-center items-center gap-4 z-20 px-4">
                                    <button type="button" @click.stop="cancelSelfieCamera()" class="px-3.5 py-2 rounded-xl bg-black/60 hover:bg-black/80 text-white text-xs font-semibold backdrop-blur-xs transition">
                                        Batal
                                    </button>
                                    <button type="button" @click.stop="captureSelfie()"
                                        class="px-5 py-2.5 rounded-xl bg-[#0DA4CE] hover:bg-[#097A99] text-white text-xs font-bold shadow-lg flex items-center gap-2 active:scale-95 transition">
                                        <div class="w-3.5 h-3.5 rounded-full bg-white animate-pulse"></div>
                                        <span>Ambil Foto</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="selfiePhoto">
                        <div class="relative rounded-2xl overflow-hidden aspect-[4/3] w-full shadow-sm border border-gray-200 dark:border-gray-700 bg-black">
                            <img :src="selfiePhoto" class="w-full h-full object-cover" alt="Foto Selfie">
                            <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent flex justify-between items-center">
                                <span class="text-[11px] font-semibold text-emerald-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Foto Selfie Terpasang
                                </span>
                                <button type="button" @click="retakeSelfie()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-bold hover:bg-white transition shadow-sm active:scale-95">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Ambil Ulang</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                @endif

                {{-- Submit Button --}}
                <button type="button" @click="onCheckOut()" :disabled="submitting"
                    class="w-full py-4 rounded-2xl font-bold text-base transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 active:scale-95 shadow-sm"
                    :class="canSubmit ? 'bg-[#90F022] text-gray-900 dark:text-gray-100 shadow-lg shadow-[#90F022]/25 hover:bg-[#7ed81e]' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500'">
                    <svg x-show="!submitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="submitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Menyimpan...' : 'Check Out Sekarang'"></span>
                </button>
            </div>
        </div>

                <canvas id="storefront-canvas" class="hidden"></canvas>
                <canvas id="selfie-canvas" class="hidden"></canvas>

        {{-- Toast --}}
        <div x-show="toast.show" x-cloak x-transition class="fixed bottom-6 left-4 right-4 z-50 max-w-lg mx-auto pointer-events-none">
            <div class="rounded-2xl px-6 py-4 shadow-xl border pointer-events-auto"
                :class="toast.type === 'success' ? 'bg-green-50 dark:bg-green-950/80 border-green-200 dark:border-green-800 text-green-800 dark:text-green-400' : (toast.type === 'warning' ? 'bg-amber-50 dark:bg-amber-950/80 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-400' : 'bg-red-50 dark:bg-red-950/80 border-red-200 dark:border-red-800 text-red-800 dark:text-red-400')">
                <div class="flex items-center gap-3">
                    <svg x-show="toast.type === 'success'" class="w-6 h-6 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="toast.type === 'warning'" class="w-6 h-6 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="toast.type === 'error'" class="w-6 h-6 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium text-sm" x-text="toast.message"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function visitCheckOut() {
            return {
                isDriver: {{ $isDriver ? 'true' : 'false' }},
                storeLat: {{ $visit->store?->latitude ? (float) $visit->store->latitude : 'null' }},
                storeLng: {{ $visit->store?->longitude ? (float) $visit->store->longitude : 'null' }},
                
                selfieCameraReady: false,
                selfiePhoto: null,
                selfieStream: null,
                selfieFacing: '{{ $isDriver ? "environment" : "user" }}',

                // Khusus Sales: Kamera Etalase
                storefrontCameraReady: false,
                storefrontPhoto: null,
                storefrontStream: null,
                storefrontFacing: 'environment',
                activeCamera: null, // 'storefront' | 'selfie' | null
                
                // Khusus Driver: Multiple photos & Optional Transaction
                driverPhotos: [],
                cameraActive: false,
                driverHasTransaction: false,
                driverTxAmount: '',
                formattedDriverTxAmount: '',
                driverPaymentMethod: 'tunai',
                
                gpsReady: false,
                isDetectingGps: false,
                gpsAccuracy: null,
                gpsWatchId: null,
                locationLat: null,
                locationLng: null,
                locationAddress: '',
                mapsUrl: '',
                gpsDenied: false,
                gpsErrorMsg: '',
                secureGps: false,
                
                submitting: false,
                openTransactions: @js($openTransactions ?? []),
                selectedTrxIds: [],
                trxAllocations: {}, // { trxId: amount }
                
                    form: {
                        visit_result: '',
                        transaction_status: 'none',
                        transaction_amount: '',
                        payment_method: '',
                        old_payment_method: 'tunai',
                        new_tx_total: '',
                        new_tx_paid: '',
                        new_payment_method: 'tunai',
                        final_notes: '',
                    },
                formattedAmount: '',
                formattedNewTxTotal: '',
                formattedNewTxPaid: '',
                toast: { show: false, message: '', type: 'success' },
                secureAvailable: window.isSecureContext === true,

                get gpsTitle() {
                    if (this.gpsReady) return 'Lokasi Berhasil Diperoleh';
                    if (this.gpsDenied) return 'Izin Lokasi Ditolak';
                    if (!this.secureAvailable) return 'Lokasi Memerlukan HTTPS';
                    if (this.isDetectingGps) return 'Mengambil lokasi Anda...';
                    return 'Lokasi Belum Diperoleh';
                },

                get gpsHint() {
                    if (this.gpsReady) return this.locationAddress || 'Koordinat berhasil dikonfirmasi dari perangkat.';
                    if (this.gpsDenied) return 'Lokasi belum berhasil diperoleh. Pastikan GPS/lokasi perangkat aktif dan izin lokasi diberikan, lalu coba lagi.';
                    if (!this.secureAvailable) return 'Koneksi aman (HTTPS) diperlukan untuk mengakses sensor GPS perangkat.';
                    if (this.isDetectingGps) return 'Menghubungkan ke satelit GPS perangkat Anda...';
                    return this.gpsErrorMsg || 'Ketuk tombol untuk mengambil koordinat lokasi perangkat.';
                },

                get storeDistanceText() {
                    if (!this.locationLat || !this.locationLng || !this.storeLat || !this.storeLng) {
                        return '-';
                    }
                    const dMeters = this.calculateHaversineDistance(this.locationLat, this.locationLng, this.storeLat, this.storeLng);
                    if (dMeters < 1000) {
                        return Math.round(dMeters) + ' meter';
                    }
                    return (dMeters / 1000).toFixed(1) + ' km';
                },

                calculateHaversineDistance(lat1, lon1, lat2, lon2) {
                    const R = 6371e3; // metres
                    const φ1 = lat1 * Math.PI / 180;
                    const φ2 = lat2 * Math.PI / 180;
                    const Δφ = (lat2 - lat1) * Math.PI / 180;
                    const Δλ = (lon2 - lon1) * Math.PI / 180;

                    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                              Math.cos(φ1) * Math.cos(φ2) *
                              Math.sin(Δλ/2) * Math.sin(Δλ/2);
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                    return R * c;
                },

                get selectedTransactions() {
                    return this.openTransactions.filter(trx => this.selectedTrxIds.includes(trx.id));
                },

                isTrxSelected(trxId) {
                    return this.selectedTrxIds.includes(trxId);
                },

                toggleTrxSelection(trxId) {
                    const idx = this.selectedTrxIds.indexOf(trxId);
                    if (idx > -1) {
                        this.selectedTrxIds.splice(idx, 1);
                        delete this.trxAllocations[trxId];
                    } else {
                        this.selectedTrxIds.push(trxId);
                        this.trxAllocations[trxId] = 0;
                    }
                },

                setFullTrxPayment(trxId, maxRemaining) {
                    if (!this.selectedTrxIds.includes(trxId)) {
                        this.selectedTrxIds.push(trxId);
                    }
                    this.trxAllocations[trxId] = maxRemaining;
                },

                getTrxRemainingAfterPay(trxId, maxRemaining) {
                    const paid = Number(this.trxAllocations[trxId]) || 0;
                    return Math.max(0, maxRemaining - paid);
                },

                get totalSelectedPiutang() {
                    if (this.openTransactions.length > 0) {
                        return this.openTransactions
                            .filter(trx => this.selectedTrxIds.includes(trx.id))
                            .reduce((acc, trx) => acc + (Number(trx.remaining_amount) || 0), 0);
                    }
                    return Number(this.form.transaction_amount) || 0;
                },

                get totalOldDebtPayment() {
                    if (this.openTransactions.length > 0) {
                        return this.selectedTrxIds.reduce((acc, id) => acc + (Number(this.trxAllocations[id]) || 0), 0);
                    }
                    return Number(this.form.transaction_amount) || 0;
                },

                get newTxRemaining() {
                    const total = Number(this.form.new_tx_total) || 0;
                    const paid = Number(this.form.new_tx_paid) || 0;
                    return Math.max(0, total - paid);
                },

                get canSubmit() {
                    if (this.isDriver) {
                        const baseValid = this.gpsReady && this.driverPhotos.length > 0 && this.form.visit_result && !this.submitting;
                        if (!baseValid) return false;
                        if (this.driverHasTransaction) {
                            const amount = Number(this.driverTxAmount) || 0;
                            return amount > 0 && !!this.driverPaymentMethod;
                        }
                        return true;
                    }

                    if (!this.gpsReady || !this.storefrontPhoto || !this.selfiePhoto || !this.form.visit_result || this.submitting) {
                        return false;
                    }

                    if (this.form.transaction_status === 'none') {
                        return true;
                    }

                    if (this.form.transaction_status === 'piutang') {
                        if (this.openTransactions.length > 0) {
                            if (this.selectedTrxIds.length === 0) return false;
                            const hasZeroSelected = this.selectedTrxIds.some(id => (Number(this.trxAllocations[id]) || 0) <= 0);
                            if (hasZeroSelected) return false;
                        }
                        return this.totalOldDebtPayment > 0 && this.form.old_payment_method;
                    }

                    if (this.form.transaction_status === 'paid') {
                        const hasTotal = (Number(this.form.new_tx_total) || 0) > 0;
                        const paid = Number(this.form.new_tx_paid) || 0;
                        if (!hasTotal) return false;
                        if (paid > 0 && !this.form.new_payment_method) return false;
                        return true;
                    }

                    if (this.form.transaction_status === 'mixed') {
                        if (this.openTransactions.length > 0) {
                            if (this.selectedTrxIds.length === 0) return false;
                            const hasZeroSelected = this.selectedTrxIds.some(id => (Number(this.trxAllocations[id]) || 0) <= 0);
                            if (hasZeroSelected) return false;
                        }
                        const oldOk = this.totalOldDebtPayment > 0 && this.form.old_payment_method;
                        const newTotal = Number(this.form.new_tx_total) || 0;
                        const newPaid = Number(this.form.new_tx_paid) || 0;
                        const newOk = newTotal > 0 && (newPaid === 0 || this.form.new_payment_method);
                        return oldOk && newOk;
                    }

                    return false;
                },

                onDriverTxToggle(hasTx) {
                    this.driverHasTransaction = hasTx;
                    if (!hasTx) {
                        this.driverTxAmount = '';
                        this.formattedDriverTxAmount = '';
                    }
                },

                onDriverTxAmountInput(event) {
                    const digits = (event.target.value || '').replace(/\D/g, '');
                    this.driverTxAmount = digits;
                    this.formattedDriverTxAmount = digits ? Number(digits).toLocaleString('id-ID') : '';
                },

                formatTrxAllocDisplay(trxId) {
                    const val = this.trxAllocations[trxId];
                    if (val === undefined || val === null || val === '') {
                        return '';
                    }
                    return Number(val).toLocaleString('id-ID');
                },

                onTrxAllocInput(trxId, event, maxRemaining) {
                    if (!this.selectedTrxIds.includes(trxId)) {
                        this.selectedTrxIds.push(trxId);
                    }
                    let digits = (event.target.value || '').replace(/\D/g, '');
                    let num = Number(digits) || 0;
                    if (num > maxRemaining) {
                        num = maxRemaining;
                        digits = String(maxRemaining);
                        this.showToast('Nominal pembayaran melebihi sisa piutang transaksi.', 'warning');
                    }
                    if (num < 0) {
                        num = 0;
                    }
                    this.trxAllocations[trxId] = num;
                    event.target.value = num > 0 ? num.toLocaleString('id-ID') : (digits === '' ? '' : '0');
                },

                onNewTxTotalInput(event) {
                    const digits = (event.target.value || '').replace(/\D/g, '');
                    this.form.new_tx_total = digits;
                    this.formattedNewTxTotal = digits ? Number(digits).toLocaleString('id-ID') : '';
                },

                onNewTxPaidInput(event) {
                    let digits = (event.target.value || '').replace(/\D/g, '');
                    let num = Number(digits) || 0;
                    const total = Number(this.form.new_tx_total) || 0;
                    if (total > 0 && num > total) {
                        num = total;
                        digits = String(total);
                    }
                    this.form.new_tx_paid = digits;
                    this.formattedNewTxPaid = digits ? Number(digits).toLocaleString('id-ID') : '';
                },

                resetTransaction() {
                    this.form.transaction_amount = '';
                    this.formattedAmount = '';
                    this.selectedTrxIds = [];
                    this.trxAllocations = {};
                    this.form.new_tx_total = '';
                    this.form.new_tx_paid = '';
                    this.formattedNewTxTotal = '';
                    this.formattedNewTxPaid = '';
                },

                formatAmountDisplay(digits) {
                    if (!digits) return '';
                    return Number(digits).toLocaleString('id-ID');
                },

                onAmountInput(event) {
                    const digits = (event.target.value || '').replace(/\D/g, '');
                    this.form.transaction_amount = digits;
                    this.formattedAmount = this.formatAmountDisplay(digits);
                },

                init() {
                    if (this.secureAvailable) {
                        this.startGPS();
                    }
                    window.addEventListener('beforeunload', () => this.stopCameras());
                },

                startGPS() {
                    if (window.isSecureContext !== true) {
                        this.secureGps = true;
                        return;
                    }
                    if (this.gpsReady) return;
                    if (!navigator.geolocation) {
                        this.gpsErrorMsg = 'Sensor GPS tidak didukung pada browser ini.';
                        this.showToast('GPS tidak tersedia pada perangkat.', 'error');
                        return;
                    }

                    this.isDetectingGps = true;
                    this.gpsDenied = false;
                    this.gpsErrorMsg = '';

                    const successCallback = (pos) => {
                        this.isDetectingGps = false;
                        this.locationLat = pos.coords.latitude;
                        this.locationLng = pos.coords.longitude;
                        this.gpsAccuracy = pos.coords.accuracy || null;
                        this.gpsReady = true;
                        this.mapsUrl = `https://www.google.com/maps?q=${this.locationLat},${this.locationLng}`;
                        this.reverseGeocode(this.locationLat, this.locationLng);
                    };

                    const errorCallback = (err) => {
                        this.isDetectingGps = false;
                        if (err && err.code === 1) {
                            this.gpsDenied = true;
                            this.gpsErrorMsg = 'Lokasi belum berhasil diperoleh. Pastikan GPS/lokasi perangkat aktif dan izin lokasi diberikan, lalu coba lagi.';
                            this.showToast('Lokasi belum berhasil diperoleh. Pastikan GPS/lokasi perangkat aktif dan izin lokasi diberikan, lalu coba lagi.', 'error');
                        } else if (err && err.code === 2) {
                            this.gpsErrorMsg = 'Lokasi belum berhasil diperoleh. Pastikan GPS/lokasi perangkat aktif dan izin lokasi diberikan, lalu coba lagi.';
                            this.showToast('Lokasi belum berhasil diperoleh. Pastikan GPS/lokasi perangkat aktif dan izin lokasi diberikan, lalu coba lagi.', 'error');
                        } else if (err && err.code === 3) {
                            this.gpsErrorMsg = 'Waktu permintaan lokasi habis (timeout). Silakan ketuk Coba Lagi.';
                            this.showToast('Deteksi GPS timeout. Ketuk Coba Lagi.', 'error');
                        } else {
                            this.gpsErrorMsg = 'Lokasi belum berhasil diperoleh. Pastikan GPS/lokasi perangkat aktif dan izin lokasi diberikan, lalu coba lagi.';
                            this.showToast('Lokasi belum berhasil diperoleh. Pastikan GPS/lokasi perangkat aktif dan izin lokasi diberikan, lalu coba lagi.', 'error');
                        }
                    };

                    const options = { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 };

                    // Menggunakan watchPosition untuk continuous lock + initial fallback
                    if (this.gpsWatchId) {
                        navigator.geolocation.clearWatch(this.gpsWatchId);
                    }
                    this.gpsWatchId = navigator.geolocation.watchPosition(successCallback, errorCallback, options);
                    
                    // Fallback getCurrentPosition sekali untuk fast initial fetch
                    navigator.geolocation.getCurrentPosition(successCallback, () => {}, options);
                },

                onGpsClicked() {
                    if (window.isSecureContext !== true) { this.secureGps = true; return; }
                    this.retryGps();
                },

                retryGps() {
                    this.gpsDenied = false;
                    this.gpsReady = false;
                    if (this.gpsWatchId) {
                        navigator.geolocation.clearWatch(this.gpsWatchId);
                        this.gpsWatchId = null;
                    }
                    this.startGPS();
                },

                async reverseGeocode(lat, lng) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`);
                        const data = await res.json();
                        this.locationAddress = data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    } catch {
                        this.locationAddress = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    }
                },

                // Khusus Driver Multiple Photos
                openDriverCamera() {
                    if (this.driverPhotos.length >= 6) {
                        this.showToast('Batas maksimal 6 foto telah tercapai.', 'warning');
                        return;
                    }
                    this.cameraActive = true;
                    this.openSelfieCamera();
                },

                cancelCamera() {
                    this.stopActiveStream();
                    this.cameraActive = false;
                    this.selfieCameraReady = false;
                },

                captureDriverPhoto() {
                    if (this.driverPhotos.length >= 6) {
                        this.showToast('Batas maksimal 6 foto telah tercapai. Hapus salah satu foto untuk menambah foto baru.', 'warning');
                        this.cancelCamera();
                        return;
                    }

                    const video = document.getElementById('selfie-camera');
                    const canvas = document.getElementById('selfie-canvas');
                    if (!video || !canvas) return;

                    const width = video.videoWidth || 640;
                    const height = video.videoHeight || 480;
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    if (this.selfieFacing === 'user') {
                        ctx.translate(width, 0);
                        ctx.scale(-1, 1);
                    } else {
                        ctx.setTransform(1, 0, 0, 1, 0, 0);
                    }
                    ctx.drawImage(video, 0, 0, width, height);
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    
                    const photoData = canvas.toDataURL('image/jpeg', 0.85);
                    this.driverPhotos.push(photoData);
                    this.cancelCamera();
                },

                removeDriverPhoto(index) {
                    this.driverPhotos.splice(index, 1);
                },

                // Khusus Sales Kamera Etalase & Selfie
                async openCamera(type) {
                    if (window.isSecureContext !== true) {
                        this.showToast('Fitur kamera memerlukan koneksi aman (HTTPS).', 'warning');
                        return;
                    }

                    this.stopActiveStream();
                    this.activeCamera = type;
                    const facing = type === 'storefront' ? this.storefrontFacing : this.selfieFacing;
                    await this.startStream(type, facing);
                },

                async startStream(type, facingMode) {
                    const videoId = type === 'storefront' ? 'storefront-camera' : 'selfie-camera';
                    const constraints = {
                        video: {
                            facingMode: { ideal: facingMode },
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    };

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia(constraints);
                        if (type === 'storefront') this.storefrontStream = stream;
                        else this.selfieStream = stream;

                        this.$nextTick(() => {
                            const video = document.getElementById(videoId);
                            if (video) {
                                video.srcObject = stream;
                                // UX Preview mirror HANYA saat menghadap user (kamera depan)
                                video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'none';
                                
                                video.onloadedmetadata = () => {
                                    video.play().catch(() => {});
                                    if (type === 'storefront') this.storefrontCameraReady = true;
                                    if (type === 'selfie') this.selfieCameraReady = true;
                                };
                            }
                        });
                    } catch (err) {
                        try {
                            const fallbackStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                            if (type === 'storefront') this.storefrontStream = fallbackStream;
                            else this.selfieStream = fallbackStream;
                            
                            this.$nextTick(() => {
                                const video = document.getElementById(videoId);
                                if (video) {
                                    video.srcObject = fallbackStream;
                                    video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'none';
                                    video.onloadedmetadata = () => {
                                        video.play().catch(() => {});
                                        if (type === 'storefront') this.storefrontCameraReady = true;
                                        if (type === 'selfie') this.selfieCameraReady = true;
                                    };
                                }
                            });
                        } catch (err2) {
                            if (type === 'storefront') this.storefrontCameraReady = false;
                            if (type === 'selfie') this.selfieCameraReady = false;
                            const msg = (err2.name === 'NotAllowedError' || err2.name === 'PermissionDeniedError')
                                ? 'Izin kamera ditolak. Silakan izinkan akses kamera di browser Anda.'
                                : 'Tidak dapat mengakses kamera. Silakan coba lagi.';
                            this.showToast(msg, 'error');
                        }
                    }
                },

                async flipCamera(type) {
                    if (type === 'storefront') {
                        this.storefrontFacing = this.storefrontFacing === 'environment' ? 'user' : 'environment';
                        await this.openCamera('storefront');
                    } else {
                        this.selfieFacing = this.selfieFacing === 'user' ? 'environment' : 'user';
                        await this.openCamera('selfie');
                    }
                },

                cancelStorefrontCamera() {
                    this.stopActiveStream();
                    this.activeCamera = null;
                    this.storefrontCameraReady = false;
                },

                cancelSelfieCamera() {
                    this.stopActiveStream();
                    this.activeCamera = null;
                    this.selfieCameraReady = false;
                },

                captureStorefront() {
                    const video = document.getElementById('storefront-camera');
                    const canvas = document.getElementById('storefront-canvas') || document.getElementById('selfie-canvas');
                    if (!video || !canvas) return;

                    const width = video.videoWidth || 640;
                    const height = video.videoHeight || 480;
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    if (this.storefrontFacing === 'user') {
                        ctx.translate(width, 0);
                        ctx.scale(-1, 1);
                    } else {
                        ctx.setTransform(1, 0, 0, 1, 0, 0);
                    }
                    ctx.drawImage(video, 0, 0, width, height);
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    
                    this.storefrontPhoto = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopActiveStream();
                    this.storefrontCameraReady = false;
                    this.activeCamera = null;
                },

                retakeStorefront() {
                    this.storefrontPhoto = null;
                    this.openCamera('storefront');
                },

                async openSelfieCamera() {
                    await this.openCamera('selfie');
                },

                async flipSelfieCamera() {
                    await this.flipCamera('selfie');
                },

                captureSelfie() {
                    const video = document.getElementById('selfie-camera');
                    const canvas = document.getElementById('selfie-canvas');
                    if (!video || !canvas) return;

                    const width = video.videoWidth || 640;
                    const height = video.videoHeight || 480;
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    if (this.selfieFacing === 'user') {
                        ctx.translate(width, 0);
                        ctx.scale(-1, 1);
                    } else {
                        ctx.setTransform(1, 0, 0, 1, 0, 0);
                    }
                    ctx.drawImage(video, 0, 0, width, height);
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    
                    this.selfiePhoto = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopActiveStream();
                    this.selfieCameraReady = false;
                    this.activeCamera = null;
                },

                retakeSelfie() {
                    this.selfiePhoto = null;
                    this.openCamera('selfie');
                },

                stopActiveStream() {
                    if (this.selfieStream) {
                        this.selfieStream.getTracks().forEach(track => track.stop());
                        this.selfieStream = null;
                    }
                    if (this.storefrontStream) {
                        this.storefrontStream.getTracks().forEach(track => track.stop());
                        this.storefrontStream = null;
                    }
                    const v1 = document.getElementById('selfie-camera');
                    if (v1) v1.srcObject = null;
                    const v2 = document.getElementById('storefront-camera');
                    if (v2) v2.srcObject = null;
                    this.selfieCameraReady = false;
                    this.storefrontCameraReady = false;
                },

                stopCameras() {
                    this.stopActiveStream();
                    if (this.gpsWatchId) {
                        navigator.geolocation.clearWatch(this.gpsWatchId);
                        this.gpsWatchId = null;
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => { this.toast.show = false; }, 4000);
                },

                async onCheckOut() {
                    if (!this.gpsReady) {
                        this.showToast('Lokasi GPS belum terdeteksi. Silakan coba lagi.', 'error');
                        return;
                    }

                    if (this.isDriver && this.driverPhotos.length === 0) {
                        this.showToast('Foto barang atau dokumentasi pengiriman wajib diambil minimal 1 foto.', 'error');
                        return;
                    }

                    if (!this.isDriver && !this.storefrontPhoto) {
                        this.showToast('Foto Etalase wajib diambil sebelum menyelesaikan Check Out.', 'error');
                        return;
                    }

                    if (!this.isDriver && !this.selfiePhoto) {
                        this.showToast('Foto selfie wajib diambil sebelum check out.', 'error');
                        return;
                    }

                    if (!this.form.visit_result || this.form.visit_result.trim() === '') {
                        this.showToast('Hasil ' + (this.isDriver ? 'pengiriman' : 'kunjungan') + ' wajib diisi.', 'error');
                        return;
                    }

                    // Frontend validation for old receivable allocations:
                    if (!this.isDriver && (this.form.transaction_status === 'piutang' || this.form.transaction_status === 'mixed')) {
                        if (this.openTransactions.length > 0) {
                            if (this.selectedTrxIds.length === 0) {
                                this.showToast('Silakan pilih minimal satu transaksi piutang yang akan dibayar.', 'error');
                                return;
                            }

                            const zeroTrxs = this.selectedTransactions.filter(trx => (Number(this.trxAllocations[trx.id]) || 0) <= 0);
                            if (zeroTrxs.length > 0) {
                                if (zeroTrxs.length === 1) {
                                    this.showToast('Transaksi ' + zeroTrxs[0].transaction_code + ' dipilih tetapi nominal pembayarannya masih Rp0. Isi nominal pembayaran atau batalkan pilihan transaksi tersebut.', 'error');
                                } else {
                                    const codeList = zeroTrxs.map(t => t.transaction_code).join(', ');
                                    this.showToast('Beberapa transaksi yang dipilih belum memiliki nominal pembayaran (' + codeList + '). Isi nominal pembayaran atau batalkan pilihan transaksi tersebut.', 'error');
                                }
                                return;
                            }
                        } else {
                            if ((Number(this.form.transaction_amount) || 0) <= 0) {
                                this.showToast('Nominal pembayaran piutang wajib diisi lebih besar dari Rp0.', 'error');
                                return;
                            }
                        }
                    }

                    this.submitting = true;

                    try {
                        const payload = {
                            latitude: this.locationLat,
                            longitude: this.locationLng,
                            address: this.locationAddress,
                            maps_url: this.mapsUrl,
                            visit_result: this.form.visit_result,
                            transaction_status: this.isDriver ? 'none' : this.form.transaction_status,
                        };

                        if (!this.isDriver) {
                            if (this.form.transaction_status === 'piutang') {
                                payload.old_payment_amount = this.totalOldDebtPayment;
                                payload.old_payment_allocations = this.selectedTrxIds
                                    .map(id => ({ store_transaction_id: id, amount: Number(this.trxAllocations[id]) || 0 }));
                                payload.old_payment_method = this.form.old_payment_method;
                                payload.transaction_amount = this.totalOldDebtPayment;
                                payload.payment_method = this.form.old_payment_method;
                            } else if (this.form.transaction_status === 'paid') {
                                payload.new_tx_total = Number(this.form.new_tx_total) || 0;
                                payload.new_tx_paid = Number(this.form.new_tx_paid) || 0;
                                payload.new_payment_method = this.form.new_payment_method;
                                payload.transaction_amount = Number(this.form.new_tx_total) || 0;
                                payload.payment_method = this.form.new_payment_method;
                            } else if (this.form.transaction_status === 'mixed') {
                                payload.old_payment_amount = this.totalOldDebtPayment;
                                payload.old_payment_allocations = this.selectedTrxIds
                                    .map(id => ({ store_transaction_id: id, amount: Number(this.trxAllocations[id]) || 0 }));
                                payload.old_payment_method = this.form.old_payment_method;
                                payload.new_tx_total = Number(this.form.new_tx_total) || 0;
                                payload.new_tx_paid = Number(this.form.new_tx_paid) || 0;
                                payload.new_payment_method = this.form.new_payment_method;
                                payload.transaction_amount = (Number(this.form.new_tx_total) || 0) + this.totalOldDebtPayment;
                                payload.payment_method = this.form.old_payment_method || this.form.new_payment_method;
                            }
                        }

                        if (this.isDriver) {
                            if (this.driverHasTransaction) {
                                const amount = Number(this.driverTxAmount) || 0;
                                payload.transaction_status = 'paid';
                                payload.transaction_amount = amount;
                                payload.payment_method = this.driverPaymentMethod;
                            } else {
                                payload.transaction_status = 'none';
                                payload.transaction_amount = null;
                                payload.payment_method = null;
                            }
                            payload.photos = this.driverPhotos;
                            if (this.form.final_notes) {
                                payload.final_notes = this.form.final_notes;
                            }
                        } else {
                            payload.selfie = this.selfiePhoto;
                            if (this.storefrontPhoto) {
                                payload.final_store_photo = this.storefrontPhoto;
                            }
                            if (this.form.final_notes) {
                                payload.final_notes = this.form.final_notes;
                            }
                        }

                        const res = await fetch('{{ route('visit.check-out', $visit->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            if (res.status === 401 || res.status === 419) {
                                this.showToast('Sesi Anda telah berakhir. Mengalihkan ke login...', 'error');
                                setTimeout(() => { window.location.href = '{{ route('login') }}'; }, 1500);
                                return;
                            }
                            throw new Error(data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Gagal melakukan check out.'));
                        }

                        this.showToast(data.message || 'Check out berhasil!', 'success');
                        this.stopCameras();

                        setTimeout(() => {
                            window.location.href = data.data?.redirect_url || '{{ $defaultRedirectUrl }}';
                        }, 1000);
                    } catch (e) {
                        this.showToast(e.message || 'Terjadi kesalahan saat menyimpan.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
