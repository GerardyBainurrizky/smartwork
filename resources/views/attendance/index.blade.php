<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="clock" title="Presensi" subtitle="Pilih status presensi Anda hari ini"></x-page-header>
    </x-slot>

    @php
        $initialIsAbsence = $today && ($today->status === 'izin' || $today->status === 'sakit');
        $initialCheckedOut = $today && $today->clock_out !== null;
        $initialCheckedIn = $today && $today->clock_in !== null;

        if ($initialIsAbsence) {
            $initialPhase = 'absence_done';
        } elseif ($initialCheckedOut) {
            $initialPhase = 'done';
        } elseif ($initialCheckedIn) {
            $initialPhase = 'checkout';
        } else {
            $initialPhase = 'select';
        }

        $now = now();
        $serverDate = $now->translatedFormat('l, d F Y');
        $serverTime = $now->format('H:i:s');
    @endphp

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <div class="max-w-lg mx-auto" x-data="attendanceApp()" x-init="init()">
        {{-- Real-time Clock dengan SSR initial content untuk mencegah layout shift --}}
        <div class="mb-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5 text-center">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400" x-text="currentDate">{{ $serverDate }}</p>
            <p class="text-4xl font-bold text-gray-900 dark:text-white tabular-nums tracking-tight mt-1" x-text="currentTime">{{ $serverTime }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Waktu &amp; tanggal perangkat Anda</p>
        </div>

        {{-- Status Presensi Selesai (Izin/Sakit) --}}
        @if($initialPhase === 'absence_done')
        <div x-show="phase === 'absence_done'" class="mb-5 rounded-2xl p-6 shadow-sm border bg-green-50 dark:bg-green-950/40 border-green-200 dark:border-green-800 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-lg font-semibold text-green-700 dark:text-green-300" x-text="absenceDoneLabel">
                {{ $today->status === 'izin' ? 'Presensi Izin Terkirim' : 'Presensi Sakit Terkirim' }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Presensi Anda hari ini telah tercatat.</p>
        </div>
        @else
        <div x-show="phase === 'absence_done'" x-cloak class="mb-5 rounded-2xl p-6 shadow-sm border bg-green-50 dark:bg-green-950/40 border-green-200 dark:border-green-800 text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-lg font-semibold text-green-700 dark:text-green-300" x-text="absenceDoneLabel"></p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Presensi Anda hari ini telah tercatat.</p>
        </div>
        @endif

        {{-- Status Banner --}}
        <div class="mb-5 rounded-2xl p-6 shadow-sm border {{ $initialPhase === 'done' ? 'bg-green-50 dark:bg-green-950/40 border-green-200 dark:border-green-800' : ($initialPhase === 'checkout' ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800' : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-800') }}"
            x-show="phase !== 'absence_done'"
            @if($initialPhase === 'absence_done') x-cloak @endif
            :class="phase === 'done' ? 'bg-green-50 dark:bg-green-950/40 border-green-200 dark:border-green-800'
                : phase === 'checkout' ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800'
                : 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-800'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium"
                        :class="phase === 'done' ? 'text-green-700 dark:text-green-300' : phase === 'checkout' ? 'text-amber-700 dark:text-amber-300' : 'text-gray-500 dark:text-gray-400'">
                        Status Presensi
                    </p>
                    <h1 class="text-2xl font-bold mt-1"
                        :class="phase === 'done' ? 'text-green-800 dark:text-green-200' : phase === 'checkout' ? 'text-amber-800 dark:text-amber-200' : 'text-gray-700 dark:text-gray-200'">
                        <span x-show="phase === 'select'" @if($initialPhase !== 'select') x-cloak @endif>Pilih Status</span>
                        <span x-show="phase === 'checkin'" x-cloak>Belum Check In</span>
                        <span x-show="phase === 'checkout'" @if($initialPhase !== 'checkout') x-cloak @endif>Sudah Check In</span>
                        <span x-show="phase === 'done'" @if($initialPhase !== 'done') x-cloak @endif>Selesai</span>
                        <span x-show="phase === 'izin_form'" x-cloak>Presensi Izin</span>
                        <span x-show="phase === 'sakit_form'" x-cloak>Presensi Sakit</span>
                    </h1>
                    <p class="text-xs mt-1"
                        :class="phase === 'done' ? 'text-green-600 dark:text-green-300' : phase === 'checkout' ? 'text-amber-600 dark:text-amber-300' : 'text-gray-400 dark:text-gray-500'">
                        <span x-show="phase === 'select'" @if($initialPhase !== 'select') x-cloak @endif>Silakan pilih status presensi Anda hari ini.</span>
                        <span x-show="phase === 'checkin'" x-cloak>Silakan lakukan check in untuk memulai.</span>
                        <span x-show="phase === 'checkout'" @if($initialPhase !== 'checkout') x-cloak @endif>Lakukan check out setelah selesai bekerja.</span>
                        <span x-show="phase === 'done'" @if($initialPhase !== 'done') x-cloak @endif>Attendance hari ini telah selesai.</span>
                        <span x-show="phase === 'izin_form'" x-cloak>Isi catatan, foto dokumentasi, dan lokasi izin.</span>
                        <span x-show="phase === 'sakit_form'" x-cloak>Isi catatan, foto dokumentasi, dan lokasi sakit.</span>
                    </p>
                </div>
                <div class="w-14 h-14 rounded-full flex items-center justify-center shrink-0 {{ $initialPhase === 'done' ? 'bg-green-100 dark:bg-green-900/40' : ($initialPhase === 'checkout' ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-gray-200 dark:bg-gray-700') }}"
                    :class="phase === 'done' ? 'bg-green-100 dark:bg-green-900/40' : phase === 'checkout' ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-gray-200 dark:bg-gray-700'">
                    <svg class="w-7 h-7 {{ $initialPhase === 'done' ? 'text-green-600 dark:text-green-300' : ($initialPhase === 'checkout' ? 'text-amber-600 dark:text-amber-300' : 'text-gray-400 dark:text-gray-500') }}"
                        :class="phase === 'done' ? 'text-green-600 dark:text-green-300' : phase === 'checkout' ? 'text-amber-600 dark:text-amber-300' : 'text-gray-400 dark:text-gray-500'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="phase === 'select' || phase === 'checkin' || phase === 'izin_form' || phase === 'sakit_form'" @if($initialPhase === 'checkout' || $initialPhase === 'done') x-cloak @endif stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path x-show="phase === 'checkout'" @if($initialPhase !== 'checkout') x-cloak @endif stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path x-show="phase === 'done'" @if($initialPhase !== 'done') x-cloak @endif stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm" x-show="phase === 'checkout' || phase === 'done'" @if($initialPhase !== 'checkout' && $initialPhase !== 'done') x-cloak @endif>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-3 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Check In</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100" x-text="clockInTime || '--:--'">{{ $today?->clock_in?->format('H:i:s') ?? '--:--' }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-3 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Check Out</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100" x-text="clockOutTime || '--:--'">{{ $today?->clock_out?->format('H:i:s') ?? '--:--' }}</p>
                </div>
            </div>
        </div>

        {{-- ===== PILIH STATUS PRESENSI ===== --}}
        <div x-show="phase === 'select'" @if($initialPhase !== 'select') x-cloak @endif class="mb-5">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Pilih Status Presensi</p>
            <div class="grid grid-cols-3 gap-3">
                <button @click="selectStatus('hadir')"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl border-2 border-[#0DA4CE] bg-[#0DA4CE]/5 dark:bg-[#0DA4CE]/10 text-[#0DA4CE] font-bold text-sm hover:bg-[#0DA4CE]/15 active:scale-95 transition">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Hadir
                </button>
                <button @click="selectStatus('izin')"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl border-2 border-amber-400 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 font-bold text-sm hover:bg-amber-100 dark:hover:bg-amber-900/30 active:scale-95 transition">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Izin
                </button>
                <button @click="selectStatus('sakit')"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl border-2 border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 font-bold text-sm hover:bg-red-100 dark:hover:bg-red-900/30 active:scale-95 transition">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    Sakit
                </button>
            </div>
        </div>

        {{-- ===== GPS Status (Aktif untuk SEMUA status: Hadir, Izin, Sakit) ===== --}}
        <div x-show="phase === 'checkin' || phase === 'checkout' || phase === 'izin_form' || phase === 'sakit_form'"
            @if($initialPhase !== 'checkout' && $initialPhase !== 'checkin') x-cloak @endif
            class="mb-5 flex items-center gap-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 shadow-sm cursor-pointer transition active:scale-[0.99]"
            @click="onGpsBlocked()">
            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                :class="gpsReady ? 'bg-green-100 dark:bg-green-900/40' : 'bg-red-50 dark:bg-red-900/30'">
                <svg class="w-5 h-5" :class="gpsReady ? 'text-green-600 dark:text-green-400' : 'text-red-400 dark:text-red-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200" x-text="gpsTitle">Menunggu izin akses lokasi</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="gpsHint">Ketuk "Izinkan Lokasi" di kotak dialog</p>
            </div>
            <div class="w-2.5 h-2.5 rounded-full animate-pulse" :class="gpsReady ? 'bg-green-500' : 'bg-red-400'"></div>
        </div>

        {{-- GPS denied notice --}}
        <div x-show="gpsDenied && (phase === 'checkin' || phase === 'checkout' || phase === 'izin_form' || phase === 'sakit_form')" x-cloak
            class="mb-5 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 p-4">
            <p class="text-sm font-medium text-red-800 dark:text-red-300">Lokasi diperlukan untuk melakukan presensi.</p>
            <button @click="retryGps()"
                class="mt-3 w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-bold hover:bg-[#097A99] transition">
                Coba Lagi
            </button>
        </div>

        {{-- Local HTTPS notice for GPS --}}
        <div x-show="secureGps" x-cloak
            class="mb-5 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 p-4">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Fitur ini memerlukan koneksi HTTPS pada perangkat.</p>
            </div>
            <button @click="secureGps = false"
                class="mt-3 w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-bold hover:bg-[#097A99] transition">
                Tutup
            </button>
        </div>

        {{-- ===== FORM IZIN ===== --}}
        <div x-show="phase === 'izin_form'" x-cloak class="mb-5 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Foto Dokumentasi <span class="text-red-500">*</span>
                </label>
                <p x-show="absencePhotoError" x-cloak class="mb-1 text-xs text-red-500" x-text="absencePhotoError"></p>
                <div class="relative bg-black rounded-2xl overflow-hidden shadow aspect-[4/3] max-h-[320px] mx-auto">
                    <video id="camera-preview-izin" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"
                        style="transform: none;" :class="cameraReady ? '' : 'hidden'"></video>
                    <canvas id="camera-canvas-izin" class="hidden"></canvas>
                    <div x-show="!cameraReady" @click="onCameraBlocked()" class="absolute inset-0 flex items-center justify-center bg-gray-900 cursor-pointer">
                        <div class="text-center">
                            <svg class="w-14 h-14 text-gray-600 mx-auto mb-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="text-gray-400 text-sm" x-text="cameraHint"></p>
                        </div>
                    </div>
                    <div x-show="selfieCaptured" x-cloak class="absolute inset-0">
                        <img :src="selfieData" class="w-full h-full object-cover" alt="Foto Dokumentasi">
                        <button @click="retakeSelfie()" class="absolute bottom-3 right-3 bg-black/60 text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-black/80 transition">
                            Ulang
                        </button>
                    </div>
                    <div x-show="flashActive" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-100" x-transition:enter-end="opacity-0"
                        class="absolute inset-0 bg-white pointer-events-none"></div>
                </div>

                <div class="flex justify-center -mt-7 relative z-10" x-show="!selfieCaptured && cameraReady" x-cloak>
                    <button @click="captureSelfieAbsence()"
                        class="w-16 h-16 rounded-full bg-white shadow-xl border-4 border-amber-400 flex items-center justify-center hover:scale-105 active:scale-95 transition transform">
                        <div class="w-12 h-12 rounded-full bg-amber-400"></div>
                    </button>
                </div>

                <div class="flex justify-center mt-3" x-show="!selfieCaptured && (cameraReady || cameraSwitching)" x-cloak>
                    <button @click="flipCamera()" :disabled="cameraSwitching"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white/90 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 shadow-sm text-sm font-semibold transition active:scale-95 disabled:opacity-60 disabled:cursor-wait">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span x-text="cameraSwitching ? 'Memuat Kamera...' : 'Balik Kamera'"></span>
                    </button>
                </div>

                <div x-show="camDenied" x-cloak class="mt-3 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 p-4">
                    <p class="text-sm font-medium text-red-800 dark:text-red-300" x-text="camDeniedMsg"></p>
                    <button @click="retryCamera()" class="mt-2 w-full px-4 py-2 bg-amber-400 text-white rounded-xl text-sm font-bold hover:bg-amber-500 transition">Coba Lagi</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Keterangan <span class="text-red-500">*</span>
                </label>
                <textarea x-model="absenceNote" rows="3" maxlength="1000"
                    placeholder="Jelaskan jika ada kendala atau informasi tambahan..."
                    class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-xl px-3 py-2.5 text-base sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 transition focus:outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 resize-none"
                    :class="absenceNoteError ? 'border-red-400 dark:border-red-500' : ''"></textarea>
                <p x-show="absenceNoteError" x-cloak class="mt-1 text-xs text-red-500" x-text="absenceNoteError"></p>
            </div>

            <div class="flex gap-3 pt-2">
                <button @click="cancelAbsence()" class="flex-1 py-3 rounded-2xl font-semibold text-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    Batal
                </button>
                <button @click="submitAbsence('izin')" :disabled="submitting"
                    class="flex-1 py-3 rounded-2xl font-bold text-sm transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed bg-amber-400 text-white shadow-lg shadow-amber-400/25 hover:bg-amber-500 active:scale-[0.98]">
                    <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Menyimpan...' : 'Kirim Presensi Izin'"></span>
                </button>
            </div>
        </div>

        {{-- ===== FORM SAKIT ===== --}}
        <div x-show="phase === 'sakit_form'" x-cloak class="mb-5 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Foto Dokumentasi <span class="text-red-500">*</span>
                </label>
                <p x-show="absencePhotoError" x-cloak class="mb-1 text-xs text-red-500" x-text="absencePhotoError"></p>
                <div class="relative bg-black rounded-2xl overflow-hidden shadow aspect-[4/3] max-h-[320px] mx-auto">
                    <video id="camera-preview-sakit" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"
                        style="transform: none;" :class="cameraReady ? '' : 'hidden'"></video>
                    <canvas id="camera-canvas-sakit" class="hidden"></canvas>
                    <div x-show="!cameraReady" @click="onCameraBlocked()" class="absolute inset-0 flex items-center justify-center bg-gray-900 cursor-pointer">
                        <div class="text-center">
                            <svg class="w-14 h-14 text-gray-600 mx-auto mb-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p class="text-gray-400 text-sm" x-text="cameraHint"></p>
                        </div>
                    </div>
                    <div x-show="selfieCaptured" x-cloak class="absolute inset-0">
                        <img :src="selfieData" class="w-full h-full object-cover" alt="Foto Dokumentasi">
                        <button @click="retakeSelfie()" class="absolute bottom-3 right-3 bg-black/60 text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-black/80 transition">
                            Ulang
                        </button>
                    </div>
                    <div x-show="flashActive" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-100" x-transition:enter-end="opacity-0"
                        class="absolute inset-0 bg-white pointer-events-none"></div>
                </div>

                <div class="flex justify-center -mt-7 relative z-10" x-show="!selfieCaptured && cameraReady" x-cloak>
                    <button @click="captureSelfieAbsence()"
                        class="w-16 h-16 rounded-full bg-white shadow-xl border-4 border-red-400 flex items-center justify-center hover:scale-105 active:scale-95 transition transform">
                        <div class="w-12 h-12 rounded-full bg-red-400"></div>
                    </button>
                </div>

                <div class="flex justify-center mt-3" x-show="!selfieCaptured && (cameraReady || cameraSwitching)" x-cloak>
                    <button @click="flipCamera()" :disabled="cameraSwitching"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white/90 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 shadow-sm text-sm font-semibold transition active:scale-95 disabled:opacity-60 disabled:cursor-wait">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span x-text="cameraSwitching ? 'Memuat Kamera...' : 'Balik Kamera'"></span>
                    </button>
                </div>

                <div x-show="camDenied" x-cloak class="mt-3 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 p-4">
                    <p class="text-sm font-medium text-red-800 dark:text-red-300" x-text="camDeniedMsg"></p>
                    <button @click="retryCamera()" class="mt-2 w-full px-4 py-2 bg-red-500 text-white rounded-xl text-sm font-bold hover:bg-red-600 transition">Coba Lagi</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Keterangan <span class="text-red-500">*</span>
                </label>
                <textarea x-model="absenceNote" rows="3" maxlength="1000"
                    placeholder="Jelaskan jika ada kendala atau informasi tambahan..."
                    class="w-full border-2 border-gray-300 dark:border-gray-600 rounded-xl px-3 py-2.5 text-base sm:text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 transition focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-400/30 resize-none"
                    :class="absenceNoteError ? 'border-red-400 dark:border-red-500' : ''"></textarea>
                <p x-show="absenceNoteError" x-cloak class="mt-1 text-xs text-red-500" x-text="absenceNoteError"></p>
            </div>

            <div class="flex gap-3 pt-2">
                <button @click="cancelAbsence()" class="flex-1 py-3 rounded-2xl font-semibold text-sm bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    Batal
                </button>
                <button @click="submitAbsence('sakit')" :disabled="submitting"
                    class="flex-1 py-3 rounded-2xl font-bold text-sm transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed bg-red-500 text-white shadow-lg shadow-red-500/25 hover:bg-red-600 active:scale-[0.98]">
                    <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Menyimpan...' : 'Kirim Presensi Sakit'"></span>
                </button>
            </div>
        </div>

        {{-- Camera + Selfie (Hadir Check-In/Out) --}}
        <div class="mb-5" x-show="phase === 'checkin' || phase === 'checkout'" @if($initialPhase !== 'checkout' && $initialPhase !== 'checkin') x-cloak @endif>
            <div class="relative bg-black rounded-3xl overflow-hidden shadow-lg aspect-[3/4] max-h-[500px] mx-auto">
                <video id="camera-preview" autoplay playsinline class="absolute inset-0 w-full h-full object-cover" style="transform: none;"
                    :class="cameraReady ? '' : 'hidden'"></video>
                <canvas id="camera-canvas" class="hidden"></canvas>
                <div x-show="!cameraReady" @click="onCameraBlocked()" class="absolute inset-0 flex items-center justify-center bg-gray-900 cursor-pointer">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-gray-600 mx-auto mb-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-gray-400 text-sm" x-text="cameraHint">Ketuk "Izinkan Kamera" untuk memulai</p>
                    </div>
                </div>
                <div x-show="selfieCaptured" x-cloak class="absolute inset-0">
                    <img :src="selfieData" class="w-full h-full object-cover" alt="Selfie">
                    <button @click="retakeSelfie()" class="absolute bottom-4 right-4 bg-black/60 text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-black/80 transition">
                        Ulang
                    </button>
                </div>

                {{-- Flash overlay on capture --}}
                <div x-show="flashActive" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-100" x-transition:enter-end="opacity-0"
                    class="absolute inset-0 bg-white pointer-events-none"></div>
            </div>

            {{-- Capture button --}}
            <div class="flex justify-center -mt-8 relative z-10" x-show="!selfieCaptured && cameraReady" x-cloak>
                <button @click="captureSelfie()"
                    class="w-20 h-20 rounded-full bg-white shadow-xl border-4 border-[#0DA4CE] flex items-center justify-center hover:scale-105 active:scale-95 transition transform">
                    <div class="w-16 h-16 rounded-full bg-[#0DA4CE]"></div>
                </button>
            </div>

            <div class="flex justify-center mt-3" x-show="!selfieCaptured && (cameraReady || cameraSwitching)" x-cloak>
                <button @click="flipCamera()" :disabled="cameraSwitching"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-white/90 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 shadow-sm text-sm font-semibold transition active:scale-95 disabled:opacity-60 disabled:cursor-wait">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span x-text="cameraSwitching ? 'Memuat Kamera...' : 'Balik Kamera'"></span>
                </button>
            </div>
            <p x-show="cameraNotice && !selfieCaptured" x-cloak class="mt-2 text-center text-xs font-medium text-amber-600 dark:text-amber-400" x-text="cameraNotice"></p>

            {{-- Camera denied notice --}}
            <div x-show="camDenied" x-cloak
                class="mt-4 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 p-4">
                <p class="text-sm font-medium text-red-800 dark:text-red-300" x-text="camDeniedMsg"></p>
                <button @click="retryCamera()"
                    class="mt-3 w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-bold hover:bg-[#097A99] transition">
                    Coba Lagi
                </button>
            </div>

            {{-- Local HTTPS notice for camera --}}
            <div x-show="secureCam" x-cloak
                class="mt-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Fitur ini memerlukan koneksi HTTPS pada perangkat.</p>
                </div>
                <button @click="secureCam = false"
                    class="mt-3 w-full px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-bold hover:bg-[#097A99] transition">
                    Tutup
                </button>
            </div>
            {{-- Input Keterangan (Opsional) --}}
            <div class="mt-4" x-show="phase === 'checkin' || phase === 'checkout'">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Keterangan
                </label>
                <textarea x-model="attendanceNotes" rows="3" maxlength="1000"
                    placeholder="Jelaskan jika ada kendala atau informasi tambahan..."
                    class="w-full text-base sm:text-xs rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-3 outline-none focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE] transition resize-none font-normal leading-relaxed"></textarea>
            </div>
        </div>

        {{-- Action Area (Hadir) --}}
        <div class="flex flex-col gap-3">
            {{-- Check In --}}
            <template x-if="phase === 'checkin'">
                <button @click="onCheckIn('check-in')"
                    :disabled="submitting"
                    class="w-full py-4 rounded-2xl font-bold text-lg transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="(gpsReady && selfieCaptured && !submitting) ? 'bg-[#0DA4CE] text-white shadow-lg shadow-[#0DA4CE]/25 hover:bg-[#097A99] active:scale-[0.98]' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500'">
                    <svg x-show="!submitting" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="submitting" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Menyimpan...' : 'Check In'"></span>
                </button>
            </template>

            {{-- Check Out --}}
            <template x-if="phase === 'checkout'">
                <button @click="onCheckIn('check-out')"
                    :disabled="submitting"
                    class="w-full py-4 rounded-2xl font-bold text-lg transition-all duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="(gpsReady && selfieCaptured && !submitting) ? 'bg-[#90F022] text-gray-900 shadow-lg shadow-[#90F022]/25 hover:bg-[#7ed81e] active:scale-[0.98]' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500'">
                    <svg x-show="!submitting" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="submitting" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Menyimpan...' : 'Check Out'"></span>
                </button>
            </template>

            {{-- Done --}}
            <div x-show="phase === 'done'" @if($initialPhase !== 'done') x-cloak @endif class="text-center py-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-lg font-semibold text-green-700 dark:text-green-300">Attendance selesai</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Terima kasih, sampai jumpa besok!</p>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="toast.show" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            class="fixed bottom-6 left-4 right-4 z-50 max-w-lg mx-auto">
            <div class="rounded-2xl px-6 py-4 shadow-xl border"
                :class="toast.type === 'success' ? 'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : (toast.type === 'warning' ? 'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200' : 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200')">
                <div class="flex items-center gap-3">
                    <svg x-show="toast.type === 'success'" class="w-6 h-6 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="toast.type === 'warning'" class="w-6 h-6 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg x-show="toast.type === 'error'" class="w-6 h-6 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium text-sm" x-text="toast.message"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function attendanceApp() {
            return {
                checkedIn: {{ $today && $today->clock_in ? 'true' : 'false' }},
                checkedOut: {{ $today && $today->clock_out ? 'true' : 'false' }},
                isAbsence: {{ ($today && ($today->status === 'izin' || $today->status === 'sakit')) ? 'true' : 'false' }},
                absenceStatus: '{{ $today?->status ?? '' }}',
                clockInTime: '{{ $today?->clock_in?->format('H:i:s') }}',
                clockOutTime: '{{ $today?->clock_out?->format('H:i:s') }}',

                currentDate: '{{ $serverDate }}',
                currentTime: '{{ $serverTime }}',
                clockTimer: null,

                selectedStatus: null,

                cameraReady: false,
                selfieCaptured: false,
                selfieData: null,
                flashActive: false,
                gpsReady: false,
                locationLat: null,
                locationLng: null,
                locationAddress: '',
                mapsUrl: '',
                submitting: false,
                stream: null,
                gpsWatchId: null,
                toast: { show: false, message: '', type: 'success' },
                secureAvailable: window.isSecureContext === true,
                secureGps: false,
                secureCam: false,
                gpsDenied: false,
                camDenied: false,
                camDeniedMsg: '',
                cameraFacing: 'environment',
                cameraSwitching: false,
                cameraNotice: '',
                isMobile: /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && !/Windows NT|Macintosh|Linux x86|CrOS|X11/i.test(navigator.userAgent),
                previewMirrored: false,
                mirrorOverrideDevices: [],

                absenceNote: '',
                absenceNoteError: '',
                absencePhotoError: '',
                attendanceNotes: '',

                get phase() {
                    if (this.isAbsence) return 'absence_done';
                    if (this.checkedOut) return 'done';
                    if (this.checkedIn) return 'checkout';
                    if (this.selectedStatus === 'izin') return 'izin_form';
                    if (this.selectedStatus === 'sakit') return 'sakit_form';
                    if (this.selectedStatus === 'hadir') return 'checkin';
                    return 'select';
                },

                get absenceDoneLabel() {
                    if (this.absenceStatus === 'izin') return 'Presensi Izin Terkirim';
                    if (this.absenceStatus === 'sakit') return 'Presensi Sakit Terkirim';
                    return 'Presensi Terkirim';
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

                get cameraHint() {
                    if (this.cameraReady) return 'Mengaktifkan kamera...';
                    if (!this.secureAvailable) return 'Kamera memerlukan koneksi aman (HTTPS) — ketuk untuk detail';
                    if (this.camDenied) return 'Izin kamera ditolak — ketuk "Coba Lagi"';
                    return 'Ketuk "Izinkan Kamera" untuk memulai';
                },

                init() {
                    this.initClock();
                    if (this.phase === 'checkout') {
                        if (window.isSecureContext === true) {
                            this.startGPS();
                            this.startCamera();
                        }
                    }
                    window.addEventListener('pagehide', () => this.stopCameras());
                    window.addEventListener('beforeunload', () => this.stopCameras());
                },

                selectStatus(status) {
                    this.selectedStatus = status;
                    this.selfieCaptured = false;
                    this.selfieData = null;
                    this.absenceNote = '';
                    this.absenceNoteError = '';
                    this.absencePhotoError = '';
                    this.camDenied = false;
                    this.camDeniedMsg = '';
                    this.cameraNotice = '';

                    if (window.isSecureContext === true) {
                        // GPS aktif untuk semua status presensi (Hadir, Izin, Sakit)
                        this.startGPS();

                        if (status === 'hadir') {
                            this.startCamera();
                        } else {
                            this.startCameraAbsence();
                        }
                    }
                },

                cancelAbsence() {
                    this.selectedStatus = null;
                    this.selfieCaptured = false;
                    this.selfieData = null;
                    this.absenceNote = '';
                    this.absenceNoteError = '';
                    this.absencePhotoError = '';
                    this.releaseStream();
                },

                onCheckIn(type) {
                    if (this.submitting) return;
                    if (window.isSecureContext !== true) {
                        this.secureGps = true;
                        this.secureCam = true;
                        return;
                    }
                    if (!this.gpsReady) this.startGPS();
                    if (!this.cameraReady) this.startCamera();
                    if (!this.gpsReady || !this.selfieCaptured) {
                        if (this.camDenied) { this.showToast(this.camDeniedMsg || 'Kamera diperlukan untuk mengambil foto presensi.', 'error'); return; }
                        if (!this.selfieCaptured) { this.showToast('Ambil selfie terlebih dahulu, lalu ketuk ' + (type === 'check-in' ? 'Check In' : 'Check Out') + '.', 'warning'); return; }
                        this.showToast(this.gpsDenied ? 'Lokasi diperlukan untuk melakukan presensi.' : 'Lokasi belum berhasil diperoleh. Aktifkan lokasi perangkat dan coba kembali.', 'warning');
                        return;
                    }
                    this.submitAttendance(type);
                },

                onGpsBlocked() {
                    if (window.isSecureContext !== true) { this.secureGps = true; return; }
                    this.startGPS();
                },

                onCameraBlocked() {
                    if (window.isSecureContext !== true) { this.secureCam = true; return; }
                    if (this.phase === 'checkin' || this.phase === 'checkout') {
                        this.startCamera();
                    } else {
                        this.startCameraAbsence();
                    }
                },

                retryGps() {
                    this.gpsDenied = false;
                    if (this.gpsWatchId) { navigator.geolocation.clearWatch(this.gpsWatchId); this.gpsWatchId = null; }
                    this.startGPS();
                },

                async retryCamera() {
                    this.camDenied = false;
                    this.camDeniedMsg = '';
                    this.cameraNotice = '';
                    this.releaseStream();
                    const camState = await this.queryCameraPermission();
                    if (camState === 'denied') {
                        this.camDenied = true;
                        this.camDeniedMsg = 'Akses kamera saat ini diblokir. Buka pengaturan izin situs browser dan ubah Kamera menjadi Izinkan, kemudian tekan Coba Lagi.';
                        return;
                    }
                    if (this.phase === 'checkin' || this.phase === 'checkout') {
                        this.startCamera();
                    } else {
                        this.startCameraAbsence();
                    }
                },

                queryCameraPermission() {
                    if (!navigator.permissions || typeof navigator.permissions.query !== 'function') return Promise.resolve(null);
                    return navigator.permissions.query({ name: 'camera' })
                        .then((status) => status.state)
                        .catch(() => null);
                },

                cameraErrorMessage(e) {
                    if (!e) return 'Tidak dapat mengakses kamera. Silakan coba lagi.';
                    if (e.name === 'NotFoundError') return 'Kamera tidak ditemukan pada perangkat ini.';
                    if (e.name === 'NotReadableError') return 'Kamera sedang digunakan oleh aplikasi lain. Tutup aplikasi lain lalu coba lagi.';
                    if (e.name === 'OverconstrainedError') return 'Mode kamera tidak didukung perangkat ini. Coba balik kamera.';
                    if (e.name === 'SecurityError') return 'Kamera tidak dapat diakses pada halaman ini.';
                    if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') return 'Izin kamera diperlukan.';
                    return 'Tidak dapat mengakses kamera. Silakan coba lagi.';
                },

                initClock() {
                    const update = () => {
                        const nowDate = new Date();
                        this.currentDate = new Intl.DateTimeFormat('id-ID', {
                            weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta'
                        }).format(nowDate);
                        this.currentTime = new Intl.DateTimeFormat('id-ID', {
                            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'Asia/Jakarta'
                        }).format(nowDate);
                    };
                    update();
                    this.clockTimer = setInterval(update, 1000);
                },

                getActiveVideoId() {
                    if (this.phase === 'izin_form') return 'camera-preview-izin';
                    if (this.phase === 'sakit_form') return 'camera-preview-sakit';
                    return 'camera-preview';
                },

                getActiveCanvasId() {
                    if (this.phase === 'izin_form') return 'camera-canvas-izin';
                    if (this.phase === 'sakit_form') return 'camera-canvas-sakit';
                    return 'camera-canvas';
                },

                async startCamera() {
                    if (window.isSecureContext !== true) return;
                    if (this.cameraReady || this.stream) return;
                    await this.activateCamera(this.cameraFacing, 'camera-preview');
                },

                async startCameraAbsence() {
                    if (window.isSecureContext !== true) return;
                    if (this.cameraReady || this.stream) return;
                    const vid = this.getActiveVideoId();
                    await this.activateCamera(this.cameraFacing, vid);
                },

                async activateCamera(facing, videoId) {
                    const vid = videoId || this.getActiveVideoId();
                    if (this.stream) this.releaseStream();
                    this.cameraSwitching = true;
                    this.cameraNotice = '';
                    try {
                        const newStream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: facing, width: { ideal: 1280 }, height: { ideal: 720 } },
                            audio: false,
                        });
                        this.attachCameraStream(newStream, facing, false, vid);
                    } catch (e) {
                        try {
                            const newStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                            this.attachCameraStream(newStream, facing, true, vid);
                        } catch (e2) {
                            this.cameraReady = false;
                            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
                            const camState = await this.queryCameraPermission();
                            if (e2 && (e2.name === 'NotAllowedError' || e2.name === 'PermissionDeniedError')) {
                                this.camDenied = true;
                                this.camDeniedMsg = camState === 'denied'
                                    ? 'Akses kamera saat ini diblokir. Buka pengaturan izin situs browser dan ubah Kamera menjadi Izinkan, kemudian tekan Coba Lagi.'
                                    : 'Kamera diperlukan untuk mengambil foto presensi.';
                                this.showToast(this.camDeniedMsg, 'error');
                            } else {
                                this.camDenied = false;
                                this.showToast(this.cameraErrorMessage(e2), 'error');
                            }
                        }
                    } finally {
                        this.cameraSwitching = false;
                    }
                },

                attachCameraStream(newStream, requestedFacing, viaFallback, videoId) {
                    const vid = videoId || this.getActiveVideoId();
                    const track = newStream.getVideoTracks()[0];
                    let actualFacing = requestedFacing;
                    let activeDeviceId = null;
                    try {
                        const s = track.getSettings();
                        if (s) {
                            if (s.facingMode) actualFacing = s.facingMode;
                            activeDeviceId = s.deviceId || null;
                        }
                    } catch (e) {}
                    if (this.stream && this.stream !== newStream) {
                        this.stream.getTracks().forEach(t => t.stop());
                    }
                    this.stream = newStream;
                    this.cameraFacing = (actualFacing === 'user' || actualFacing === 'environment') ? actualFacing : requestedFacing;
                    if ((actualFacing && actualFacing !== requestedFacing) || viaFallback) {
                        this.cameraNotice = requestedFacing === 'environment'
                            ? 'Kamera belakang tidak tersedia pada perangkat ini.'
                            : 'Kamera depan tidak tersedia pada perangkat ini.';
                    }
                    const video = document.getElementById(vid);
                    if (!video) return;
                    video.muted = true;
                    video.setAttribute('playsinline', 'true');
                    video.setAttribute('webkit-playsinline', 'true');
                    const overrideMirror = activeDeviceId && this.mirrorOverrideDevices.indexOf(activeDeviceId) !== -1;
                    this.previewMirrored = overrideMirror || (this.isMobile ? (this.cameraFacing === 'user') : true);
                    video.style.transform = this.previewMirrored ? 'scaleX(-1)' : 'none';
                    video.srcObject = this.stream;
                    video.onloadedmetadata = () => {
                        video.play().catch(() => {});
                        this.cameraReady = true;
                    };
                },

                async flipCamera() {
                    if (this.cameraSwitching) return;
                    if (window.isSecureContext !== true) return;
                    if (!this.stream && !this.cameraReady) return;
                    this.cameraFacing = this.cameraFacing === 'user' ? 'environment' : 'user';
                    const vid = this.getActiveVideoId();
                    await this.activateCamera(this.cameraFacing, vid);
                },

                releaseStream() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                        this.stream = null;
                    }
                    const ids = ['camera-preview', 'camera-preview-izin', 'camera-preview-sakit'];
                    ids.forEach(id => {
                        const v = document.getElementById(id);
                        if (v) v.srcObject = null;
                    });
                    this.cameraReady = false;
                },

                captureSelfie() {
                    const video = document.getElementById('camera-preview');
                    const canvas = document.getElementById('camera-canvas');
                    this._captureFromVideo(video, canvas);
                },

                captureSelfieAbsence() {
                    const vid = this.getActiveVideoId();
                    const cid = this.getActiveCanvasId();
                    const video = document.getElementById(vid);
                    const canvas = document.getElementById(cid);
                    this._captureFromVideo(video, canvas);
                    this.absencePhotoError = '';
                },

                _captureFromVideo(video, canvas) {
                    if (!video || !canvas) return;
                    const vw = video.videoWidth || video.clientWidth || 640;
                    const vh = video.videoHeight || video.clientHeight || 480;
                    canvas.width = vw;
                    canvas.height = vh;
                    const ctx = canvas.getContext('2d');
                    if (this.previewMirrored) {
                        ctx.translate(canvas.width, 0);
                        ctx.scale(-1, 1);
                    }
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.selfieData = canvas.toDataURL('image/jpeg', 0.85);
                    this.selfieCaptured = true;
                    this.flashActive = true;
                    setTimeout(() => this.flashActive = false, 150);
                },

                retakeSelfie() {
                    this.selfieCaptured = false;
                    this.selfieData = null;
                    this.absencePhotoError = '';
                },

                startGPS() {
                    if (window.isSecureContext !== true) return;
                    if (this.gpsReady || this.gpsWatchId) return;
                    if (!navigator.geolocation) {
                        this.showToast('GPS tidak tersedia di perangkat ini.', 'error');
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
                            this.showToast(this.gpsDenied ? 'Lokasi diperlukan untuk melakukan presensi.' : 'Gagal mendapatkan lokasi. Coba lagi.', 'error');
                        },
                        { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
                    );
                },

                async reverseGeocode(lat, lng) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`);
                        const data = await res.json();
                        this.locationAddress = data.display_name || `${lat}, ${lng}`;
                    } catch {
                        this.locationAddress = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    }
                },

                async submitAttendance(type) {
                    if (!this.gpsReady || !this.selfieCaptured || this.submitting) return;

                    this.submitting = true;

                    try {
                        const url = type === 'check-in'
                            ? '{{ route('attendance.check-in') }}'
                            : '{{ route('attendance.check-out') }}';

                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                latitude: this.locationLat,
                                longitude: this.locationLng,
                                address: this.locationAddress,
                                maps_url: this.mapsUrl,
                                selfie: this.selfieData,
                                notes: this.attendanceNotes ? this.attendanceNotes.trim() : null,
                            }),
                        });

                        const data = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            if (res.status === 401 || res.status === 419) {
                                this.stopCameras();
                                this.showToast('Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.', 'error');
                                setTimeout(() => window.location.href = '{{ route('login') }}', 1500);
                                return;
                            }
                            throw new Error(this.extractError(data, res.status));
                        }

                        if (type === 'check-in') {
                            this.checkedIn = true;
                            this.clockInTime = data.data.clock_in;
                        } else {
                            this.checkedOut = true;
                            this.clockOutTime = data.data.clock_out;
                            this.stopCameras();
                        }

                        this.showToast(data.message, 'success');
                        this.selfieCaptured = false;
                        this.selfieData = null;
                    } catch (e) {
                        this.showToast(e.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                async submitAbsence(type) {
                    this.absenceNoteError = '';
                    this.absencePhotoError = '';

                    if (!this.absenceNote || this.absenceNote.trim().length < 3) {
                        this.absenceNoteError = 'Catatan izin wajib diisi (minimal 3 karakter).';
                        if (type === 'sakit') this.absenceNoteError = 'Catatan wajib diisi (minimal 3 karakter).';
                        return;
                    }

                    if (!this.selfieCaptured || !this.selfieData) {
                        this.absencePhotoError = 'Foto dokumentasi wajib diambil.';
                        return;
                    }

                    if (!this.gpsReady || !this.locationLat || !this.locationLng) {
                        if (this.gpsDenied) {
                            this.showToast('Lokasi diperlukan untuk melakukan presensi.', 'error');
                        } else {
                            this.startGPS();
                            this.showToast('Lokasi belum berhasil diperoleh. Aktifkan lokasi perangkat dan coba kembali.', 'warning');
                        }
                        return;
                    }

                    if (this.submitting) return;
                    this.submitting = true;

                    try {
                        const res = await fetch('{{ route('attendance.submit-absence') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                type: type,
                                absence_note: this.absenceNote.trim(),
                                selfie: this.selfieData,
                                latitude: this.locationLat,
                                longitude: this.locationLng,
                                address: this.locationAddress,
                                maps_url: this.mapsUrl,
                            }),
                        });

                        const data = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            if (res.status === 401 || res.status === 419) {
                                this.stopCameras();
                                this.showToast('Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan.', 'error');
                                setTimeout(() => window.location.href = '{{ route('login') }}', 1500);
                                return;
                            }
                            throw new Error(this.extractError(data, res.status));
                        }

                        this.isAbsence = true;
                        this.absenceStatus = type;
                        this.selectedStatus = null;
                        this.stopCameras();
                        this.showToast(data.message, 'success');
                    } catch (e) {
                        this.showToast(e.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                extractError(data, status) {
                    if (data && data.message) return data.message;
                    if (data && data.errors) {
                        const first = Object.values(data.errors).flat()[0];
                        if (first) return first;
                    }
                    if (status === 409) return 'Data attendance sudah tercatat. Silakan muat ulang halaman.';
                    return 'Terjadi kesalahan saat menyimpan. Silakan coba lagi.';
                },

                stopCameras() {
                    this.releaseStream();
                    if (this.gpsWatchId) { navigator.geolocation.clearWatch(this.gpsWatchId); this.gpsWatchId = null; }
                },

                showToast(message, type) {
                    this.toast = { show: true, message, type };
                    setTimeout(() => this.toast.show = false, 4000);
                },
            };
        }
    </script>
</x-app-layout>
