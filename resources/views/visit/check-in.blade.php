<x-app-layout>
    @php
        $isDriver = Auth::user()->hasRole('driver');
    @endphp
    <x-slot name="header">
        <x-page-header icon="store" :title="$isDriver ? 'Check In Pengiriman' : 'Check In Kunjungan'" :subtitle="$isDriver ? 'Ambil Foto Toko / Selfie, catatan awal, dan konfirmasi lokasi' : 'Ambil foto selfie, catatan kunjungan, dan konfirmasi lokasi'"></x-page-header>
    </x-slot>

    <div class="max-w-lg mx-auto" x-data="visitCheckIn()" x-init="init()">
        <x-back-button href="{{ route('route.show', $routeStop->route_id) }}" :label="$isDriver ? 'Kembali ke Rute Pengiriman' : 'Kembali ke Rute'" class="mb-4" />

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/60 overflow-hidden mb-6">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700/60">
                <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">Check In</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-semibold">{{ $routeStop->store->name ?? 'Toko' }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $routeStop->store->address ?? '' }}</p>
            </div>

            <div class="p-5 space-y-6">
                {{-- 1. Lokasi (GPS Status Card) --}}
                <div class="flex items-center gap-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700/60 p-4 cursor-pointer transition active:scale-[0.99]" @click="onGpsClicked()">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                        :class="gpsReady ? 'bg-green-100 dark:bg-green-950/50' : 'bg-red-50 dark:bg-red-950/50'">
                        <svg class="w-5 h-5" :class="gpsReady ? 'text-green-600 dark:text-green-400' : 'text-red-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200" x-text="gpsTitle"></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="gpsHint"></p>
                    </div>
                    <div class="w-2.5 h-2.5 rounded-full animate-pulse shrink-0" :class="gpsReady ? 'bg-green-500' : 'bg-red-400'"></div>
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

                {{-- GPS denied notice --}}
                <div x-show="gpsDenied" x-cloak
                    class="rounded-xl border border-red-200 dark:border-red-800/60 bg-red-50 dark:bg-red-950/40 p-4">
                    <p class="text-sm font-medium text-red-800 dark:text-red-400">Lokasi diperlukan untuk melakukan {{ $isDriver ? 'pengiriman' : 'kunjungan' }}.</p>
                    <button type="button" @click="retryGps()"
                        class="mt-3 w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-bold hover:bg-[#097A99] transition">
                        Coba Lagi
                    </button>
                </div>

                {{-- 2. Foto Toko / Selfie --}}
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-bold text-gray-800 dark:text-gray-200">
                            {{ $isDriver ? 'Foto Toko / Selfie' : 'Foto Selfie' }} <span class="text-red-500">*</span>
                        </label>
                        <span x-show="selfiePhoto" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Sudah Diambil
                        </span>
                    </div>

                    {{-- Kamera Belum Diambil / Sedang Aktif --}}
                    <template x-if="!selfiePhoto">
                        <div class="space-y-3">
                            <div class="relative bg-gray-900 rounded-2xl overflow-hidden aspect-[4/3] w-full shadow-inner border border-gray-200 dark:border-gray-700">
                                {{-- Video Element --}}
                                <video id="selfie-camera" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"
                                    :class="activeCamera === 'selfie' && selfieReady ? '' : 'hidden'"></video>
                                
                                {{-- Placeholder saat kamera belum dibuka --}}
                                <div x-show="activeCamera !== 'selfie' || !selfieReady" @click="openCamera('selfie')"
                                    class="absolute inset-0 flex flex-col items-center justify-center p-5 text-center cursor-pointer bg-gray-900/95 hover:bg-gray-900 transition">
                                    <div class="w-14 h-14 rounded-2xl bg-white/10 text-white flex items-center justify-center mb-3">
                                        <svg class="w-7 h-7 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-white">{{ $isDriver ? 'Ambil Foto Toko / Selfie' : 'Buka Kamera Selfie' }}</p>
                                    <p class="text-xs text-gray-400 mt-1 max-w-xs">{{ $isDriver ? 'Ketuk di sini untuk mengambil foto toko / selfie saat tiba di tujuan' : 'Ketuk di sini untuk mengambil foto selfie kehadiran di toko' }}</p>
                                </div>

                                {{-- Tombol Ganti Kamera di Pojok Atas --}}
                                <button type="button" @click.stop="flipCamera('selfie')" x-show="activeCamera === 'selfie' && selfieReady"
                                    class="absolute top-3.5 right-3.5 z-20 px-3 py-1.5 rounded-full bg-black/60 backdrop-blur-sm text-white border border-white/20 text-xs font-semibold hover:bg-black/80 transition flex items-center gap-1.5 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Ganti Kamera</span>
                                </button>

                                {{-- Tombol Shutter Ambil Foto di Tengah Bawah --}}
                                <div x-show="activeCamera === 'selfie' && selfieReady" class="absolute bottom-4 left-0 right-0 flex justify-center items-center z-20">
                                    <button type="button" @click.stop="captureSelfie()"
                                        class="w-16 h-16 rounded-full bg-white/95 shadow-2xl border-4 border-[#0DA4CE] flex items-center justify-center hover:scale-105 active:scale-95 transition transform">
                                        <div class="w-11 h-11 rounded-full bg-[#0DA4CE]"></div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Hasil Foto Terambil (1 Foto) --}}
                    <template x-if="selfiePhoto">
                        <div class="relative rounded-2xl overflow-hidden aspect-[4/3] w-full shadow-sm border border-gray-200 dark:border-gray-700 bg-black">
                            <img :src="selfiePhoto" class="w-full h-full object-cover" :alt="isDriver ? 'Foto Toko / Selfie' : 'Foto Selfie'">
                            <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 via-black/40 to-transparent flex justify-end">
                                <button type="button" @click="retakeSelfie()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-bold hover:bg-white transition shadow-sm active:scale-95">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Ambil Ulang</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- 3. Catatan Kunjungan --}}
                <div>
                    <label for="initial_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1.5">
                        {{ $isDriver ? 'Catatan Awal Pengiriman' : 'Catatan Kunjungan' }} <span class="text-gray-400 dark:text-gray-500 font-normal">(opsional)</span>
                    </label>
                    <textarea id="initial_notes" x-model="form.initial_notes" rows="3"
                        class="sw-input"
                        placeholder="{{ $isDriver ? 'Contoh: Barang diterima oleh bagian gudang / pengiriman sesuai PO' : 'Tambahkan catatan kunjungan jika diperlukan...' }}"
                        maxlength="500"></textarea>
                </div>

                {{-- Submit Button --}}
                <button type="button" @click="onCheckIn()" :disabled="submitting"
                    class="w-full py-4 rounded-2xl font-bold text-base transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 active:scale-95 shadow-sm"
                    :class="canSubmit ? 'bg-[#0DA4CE] text-white shadow-lg shadow-[#0DA4CE]/25 hover:bg-[#097A99]' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500'">
                    <svg x-show="!submitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="submitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Menyimpan...' : 'Check In'"></span>
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
        function visitCheckIn() {
            return {
                isDriver: {{ $isDriver ? 'true' : 'false' }},
                activeCamera: null, // 'storefront' | 'selfie' | null
                currentStream: null,
                
                storefrontReady: false,
                selfieReady: false,
                storefrontFacing: 'environment',
                selfieFacing: 'user',
                
                storefrontPhoto: null,
                selfiePhoto: null,
                
                gpsReady: false,
                gpsWatchId: null,
                locationLat: null,
                locationLng: null,
                locationAddress: '',
                mapsUrl: '',
                gpsDenied: false,
                secureGps: false,
                
                submitting: false,
                form: { initial_notes: '' },
                toast: { show: false, message: '', type: 'success' },
                secureAvailable: window.isSecureContext === true,

                get canSubmit() {
                    return this.gpsReady && this.selfiePhoto && !this.submitting;
                },

                get gpsTitle() {
                    if (this.gpsReady) return 'Lokasi terdeteksi';
                    if (!this.secureAvailable) return 'Lokasi memerlukan HTTPS';
                    return 'Menunggu izin akses lokasi';
                },

                get gpsHint() {
                    if (this.gpsReady) return this.locationAddress || 'Lokasi terdeteksi';
                    if (!this.secureAvailable) return 'Koneksi aman (HTTPS) diperlukan — ketuk untuk detail';
                    if (this.gpsDenied) return 'Izin lokasi ditolak — ketuk "Coba Lagi"';
                    return 'Ketuk "Izinkan Lokasi" di kotak dialog';
                },

                init() {
                    if (this.secureAvailable) {
                        this.startGPS();
                    }
                    window.addEventListener('beforeunload', () => this.stopAllCameras());
                },

                startGPS() {
                    if (window.isSecureContext !== true) return;
                    if (this.gpsReady) return;
                    if (!navigator.geolocation) {
                        this.showToast('GPS tidak tersedia pada perangkat.', 'error');
                        return;
                    }
                    this.gpsWatchId = navigator.geolocation.watchPosition(
                        (pos) => {
                            this.locationLat = pos.coords.latitude;
                            this.locationLng = pos.coords.longitude;
                            this.gpsReady = true;
                            this.mapsUrl = `https://www.google.com/maps?q=${this.locationLat},${this.locationLng}`;
                            this.reverseGeocode(this.locationLat, this.locationLng);
                        },
                        (err) => {
                            this.gpsDenied = (err && err.code === 1);
                            this.showToast(this.gpsDenied ? 'Izin lokasi diperlukan untuk kunjungan.' : 'Gagal mendeteksi lokasi GPS.', 'error');
                        },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
                    );
                },

                onGpsClicked() {
                    if (window.isSecureContext !== true) { this.secureGps = true; return; }
                    this.startGPS();
                },

                retryGps() {
                    this.gpsDenied = false;
                    if (this.gpsWatchId) { navigator.geolocation.clearWatch(this.gpsWatchId); this.gpsWatchId = null; }
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

                async openCamera(type) {
                    if (window.isSecureContext !== true) {
                        this.showToast('Fitur kamera memerlukan koneksi aman (HTTPS).', 'warning');
                        return;
                    }

                    // Release camera yang sedang menyala sebelumnya
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
                        this.currentStream = stream;

                        this.$nextTick(() => {
                            const video = document.getElementById(videoId);
                            if (video) {
                                video.srcObject = stream;
                                // Mirror preview hanya untuk kamera depan (user)
                                video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'none';
                                
                                video.onloadedmetadata = () => {
                                    video.play().catch(() => {});
                                    if (type === 'storefront') this.storefrontReady = true;
                                    if (type === 'selfie') this.selfieReady = true;
                                };
                            }
                        });
                    } catch (err) {
                        // Fallback ke kamera default jika constraint ideal ditolak
                        try {
                            const fallbackStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                            this.currentStream = fallbackStream;
                            
                            this.$nextTick(() => {
                                const video = document.getElementById(videoId);
                                if (video) {
                                    video.srcObject = fallbackStream;
                                    video.style.transform = facingMode === 'user' ? 'scaleX(-1)' : 'none';
                                    video.onloadedmetadata = () => {
                                        video.play().catch(() => {});
                                        if (type === 'storefront') this.storefrontReady = true;
                                        if (type === 'selfie') this.selfieReady = true;
                                    };
                                }
                            });
                        } catch (err2) {
                            if (type === 'storefront') this.storefrontReady = false;
                            if (type === 'selfie') this.selfieReady = false;
                            
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

                captureStorefront() {
                    const video = document.getElementById('storefront-camera');
                    const canvas = document.getElementById('storefront-canvas');
                    if (!video || !canvas) return;

                    const width = video.videoWidth || 640;
                    const height = video.videoHeight || 480;
                    canvas.width = width;
                    canvas.height = height;
                    
                    const ctx = canvas.getContext('2d');
                    // Pertahankan orientasi visual preview saat capture agar tidak terbalik horizontal
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
                    this.storefrontReady = false;
                    this.activeCamera = null;
                },

                retakeStorefront() {
                    this.storefrontPhoto = null;
                    this.openCamera('storefront');
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
                    // Pertahankan orientasi visual preview saat capture agar tidak terbalik horizontal
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
                    this.selfieReady = false;
                    this.activeCamera = null;
                },

                retakeSelfie() {
                    this.selfiePhoto = null;
                    this.openCamera('selfie');
                },

                stopActiveStream() {
                    if (this.currentStream) {
                        this.currentStream.getTracks().forEach(t => t.stop());
                        this.currentStream = null;
                    }
                    const v1 = document.getElementById('storefront-camera');
                    if (v1) v1.srcObject = null;
                    const v2 = document.getElementById('selfie-camera');
                    if (v2) v2.srcObject = null;
                    this.storefrontReady = false;
                    this.selfieReady = false;
                },

                stopAllCameras() {
                    this.stopActiveStream();
                    if (this.gpsWatchId) {
                        navigator.geolocation.clearWatch(this.gpsWatchId);
                        this.gpsWatchId = null;
                    }
                },

                onCheckIn() {
                    if (this.submitting) return;
                    if (window.isSecureContext !== true) {
                        this.secureGps = true;
                        return;
                    }
                    if (!this.gpsReady) {
                        this.startGPS();
                        this.showToast('Menunggu lokasi GPS terdeteksi...', 'warning');
                        return;
                    }
                    if (!this.selfiePhoto) {
                        this.showToast('Silakan ambil foto selfie kehadiran terlebih dahulu.', 'warning');
                        return;
                    }
                    this.submitCheckIn();
                },

                async submitCheckIn() {
                    if (!this.canSubmit) return;
                    this.submitting = true;
                    try {
                        const res = await fetch('{{ route('visit.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                route_stop_id: '{{ $routeStop->id }}',
                                route_id: '{{ $routeStop->route_id }}',
                                store_id: '{{ $routeStop->store_id }}',
                                latitude: this.locationLat,
                                longitude: this.locationLng,
                                address: this.locationAddress,
                                maps_url: this.mapsUrl,
                                storefront_photo: null,
                                selfie: this.selfiePhoto,
                                initial_notes: this.form.initial_notes || null,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            if (res.status === 401 || res.status === 419) {
                                this.stopAllCameras();
                                this.showToast('Sesi berakhir. Silakan masuk kembali.', 'error');
                                setTimeout(() => window.location.href = '{{ route('login') }}', 1500);
                                return;
                            }
                            throw new Error(data.message || 'Terjadi kesalahan saat menyimpan kunjungan.');
                        }
                        this.showToast(data.message || 'Check In berhasil!', 'success');
                        this.stopAllCameras();
                        setTimeout(() => window.location.href = '{{ route('route.show', $routeStop->route_id) }}', 1000);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => this.toast.show = false, 4000);
                },
            };
        }
    </script>

    <style>[x-cloak] { display: none !important; }</style>
</x-app-layout>
