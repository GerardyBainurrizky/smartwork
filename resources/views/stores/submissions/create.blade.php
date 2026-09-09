<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="plus" title="Informasikan Toko Baru" subtitle="Ajukan data toko baru untuk diverifikasi oleh Admin"></x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <a href="{{ route('stores.submissions.index') }}" class="inline-flex items-center mb-4 px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Riwayat Pengajuan
        </a>

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-6">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Form Pengajuan Toko Baru</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Data yang Anda masukkan akan ditinjau oleh Admin/Super Admin sebelum dimasukkan ke Master Toko.</p>
                </div>

                <form action="{{ route('stores.submissions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5" id="submission-form" novalidate>
                    @csrf

                    <div>
                        <label class="sw-label" for="name">Nama Toko *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            class="sw-input {{ $errors->has('name') ? 'sw-input-error' : '' }}" required autofocus placeholder="Contoh: Toko Berkah Mandiri"
                            maxlength="200">
                        @error('name')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        <div id="err-name" class="sw-input-helper sw-helper-error hidden"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="sw-label" for="owner">Nama Pemilik / PIC</label>
                            <input type="text" id="owner" name="owner" value="{{ old('owner') }}"
                                class="sw-input {{ $errors->has('owner') ? 'sw-input-error' : '' }}" placeholder="Contoh: Pak Herman"
                                maxlength="200">
                            @error('owner')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="sw-label" for="phone">No. Telepon Toko / PIC</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                class="sw-input {{ $errors->has('phone') ? 'sw-input-error' : '' }}" placeholder="Contoh: 0812-3456-7890"
                                maxlength="20">
                            @error('phone')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                            <div id="err-phone" class="sw-input-helper sw-helper-error hidden"></div>
                        </div>
                    </div>

                    <div>
                        <label class="sw-label" for="address">Alamat Lengkap *</label>
                        <textarea id="address" name="address" rows="3"
                            class="sw-input {{ $errors->has('address') ? 'sw-input-error' : '' }}"
                            placeholder="Alamat lengkap toko / patokan jalan" required maxlength="500">{{ old('address') }}</textarea>
                        @error('address')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        <div id="err-address" class="sw-input-helper sw-helper-error hidden"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <label class="sw-label" for="city">Kota / Kabupaten *</label>
                            <input type="text" id="city" name="city" value="{{ old('city') }}"
                                class="sw-input {{ $errors->has('city') ? 'sw-input-error' : '' }}" required placeholder="Subang"
                                maxlength="100">
                            @error('city')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                            <div id="err-city" class="sw-input-helper sw-helper-error hidden"></div>
                        </div>
                        <div>
                            <label class="sw-label" for="kecamatan">Kecamatan</label>
                            <input type="text" id="kecamatan" name="kecamatan" value="{{ old('kecamatan') }}"
                                class="sw-input {{ $errors->has('kecamatan') ? 'sw-input-error' : '' }}" placeholder="Pagaden"
                                maxlength="100">
                            @error('kecamatan')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="sw-label" for="province">Provinsi *</label>
                            <input type="text" id="province" name="province" value="{{ old('province', 'Jawa Barat') }}"
                                class="sw-input {{ $errors->has('province') ? 'sw-input-error' : '' }}" required placeholder="Jawa Barat"
                                maxlength="100">
                            @error('province')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                            <div id="err-province" class="sw-input-helper sw-helper-error hidden"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="sw-label" for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" value="{{ old('latitude') }}" inputmode="decimal"
                                class="sw-input {{ $errors->has('latitude') ? 'sw-input-error' : '' }}" placeholder="cth. -6.5521">
                            @error('latitude')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                            <div id="err-latitude" class="sw-input-helper sw-helper-error hidden"></div>
                        </div>
                        <div>
                            <label class="sw-label" for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" value="{{ old('longitude') }}" inputmode="decimal"
                                class="sw-input {{ $errors->has('longitude') ? 'sw-input-error' : '' }}" placeholder="cth. 107.7612">
                            @error('longitude')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                            <div id="err-longitude" class="sw-input-helper sw-helper-error hidden"></div>
                        </div>
                    </div>

                    <div>
                        <label class="sw-label" for="maps_url">Link Google Maps</label>
                        <input type="url" id="maps_url" name="maps_url" value="{{ old('maps_url') }}"
                            class="sw-input {{ $errors->has('maps_url') ? 'sw-input-error' : '' }}" placeholder="https://maps.app.goo.gl/...">
                        @error('maps_url')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                        <div id="err-maps-url" class="sw-input-helper sw-helper-error hidden"></div>
                    </div>

                    {{-- Upload Foto dengan Preview --}}
                    <div x-data="photoUpload()" x-init="init()">
                        <label class="sw-label">Foto Toko / Lokasi (Opsional)</label>

                        {{-- Area Preview (muncul setelah foto dipilih) --}}
                        <div x-show="hasFile" x-cloak class="mb-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-start gap-3">
                                <img :src="previewUrl" alt="Preview foto" class="w-20 h-20 object-cover rounded-lg border border-gray-200 dark:border-gray-700 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-900 dark:text-white truncate" x-text="fileName"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="fileSize"></p>
                                    <button type="button" @click="clearFile()"
                                        class="mt-2 text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition">
                                        × Hapus Foto
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="!hasFile">
                            <label for="photo" class="cursor-pointer block">
                                <div class="border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl px-4 py-5 text-center hover:border-[#0DA4CE]/60 hover:bg-[#0DA4CE]/5 transition-colors">
                                    <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Klik untuk pilih foto</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">JPG, PNG, GIF — maks. 4MB</p>
                                </div>
                            </label>
                        </div>

                        <input type="file" id="photo" name="photo" accept="image/*" class="hidden"
                            @change="handleFile($event)">
                        <div id="err-photo" class="sw-input-helper sw-helper-error hidden"></div>
                        @error('photo')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="sw-label" for="notes">Catatan Tambahan untuk Admin</label>
                        <textarea id="notes" name="notes" rows="2"
                            class="sw-input {{ $errors->has('notes') ? 'sw-input-error' : '' }}"
                            placeholder="Misal: Toko baru buka bulan lalu, potensi omset besar..." maxlength="500">{{ old('notes') }}</textarea>
                        @error('notes')<div class="sw-input-helper sw-helper-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <button type="submit" id="submit-btn"
                            class="inline-flex items-center px-6 py-2.5 bg-[#0DA4CE] text-white rounded-xl font-semibold text-sm hover:bg-[#097A99] transition shadow-sm shadow-[#0DA4CE]/20">
                            Kirim Pengajuan Toko
                        </button>
                        <a href="{{ route('stores.submissions.index') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-semibold text-sm hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function photoUpload() {
        return {
            hasFile: false,
            previewUrl: '',
            fileName: '',
            fileSize: '',
            init() {
                const input = document.getElementById('photo');
                if (input && input.files && input.files.length > 0) {
                    this._processFile(input.files[0]);
                }
            },
            handleFile(event) {
                const file = event.target.files[0];
                const errEl = document.getElementById('err-photo');
                if (errEl) { errEl.textContent = ''; errEl.classList.add('hidden'); }

                if (!file) { this.clearFile(); return; }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    if (errEl) { errEl.textContent = 'File harus berupa gambar (JPG, PNG, GIF, WEBP).'; errEl.classList.remove('hidden'); }
                    this.clearFile();
                    return;
                }
                if (file.size > 4 * 1024 * 1024) {
                    if (errEl) { errEl.textContent = 'Ukuran foto maksimal 4MB.'; errEl.classList.remove('hidden'); }
                    this.clearFile();
                    return;
                }
                this._processFile(file);
            },
            _processFile(file) {
                this.hasFile = true;
                this.fileName = file.name;
                const kb = file.size / 1024;
                this.fileSize = kb >= 1024 ? (kb / 1024).toFixed(2) + ' MB' : Math.round(kb) + ' KB';
                const reader = new FileReader();
                reader.onload = (e) => { this.previewUrl = e.target.result; };
                reader.readAsDataURL(file);
            },
            clearFile() {
                this.hasFile = false;
                this.previewUrl = '';
                this.fileName = '';
                this.fileSize = '';
                const input = document.getElementById('photo');
                if (input) { input.value = ''; }
            }
        };
    }

    document.getElementById('submission-form').addEventListener('submit', function(e) {
        let hasError = false;

        function showErr(id, msg) {
            const el = document.getElementById(id);
            if (el) { el.textContent = msg; el.classList.remove('hidden'); }
        }
        function clearErr(id) {
            const el = document.getElementById(id);
            if (el) { el.textContent = ''; el.classList.add('hidden'); }
        }
        function isBlank(val) { return !val || val.trim() === ''; }

        clearErr('err-name'); clearErr('err-address'); clearErr('err-city');
        clearErr('err-province'); clearErr('err-phone'); clearErr('err-latitude');
        clearErr('err-longitude'); clearErr('err-maps-url'); clearErr('err-photo');

        const name = document.getElementById('name').value;
        if (isBlank(name)) { showErr('err-name', 'Nama toko wajib diisi.'); hasError = true; }
        else if (name.length > 200) { showErr('err-name', 'Nama toko maksimal 200 karakter.'); hasError = true; }

        const address = document.getElementById('address').value;
        if (isBlank(address)) { showErr('err-address', 'Alamat toko wajib diisi.'); hasError = true; }

        const city = document.getElementById('city').value;
        if (isBlank(city)) { showErr('err-city', 'Kota wajib diisi.'); hasError = true; }

        const province = document.getElementById('province').value;
        if (isBlank(province)) { showErr('err-province', 'Provinsi wajib diisi.'); hasError = true; }

        const phone = document.getElementById('phone').value;
        if (phone.trim() !== '' && phone.trim().length > 20) {
            showErr('err-phone', 'Nomor telepon maksimal 20 karakter.'); hasError = true;
        }

        const lat = document.getElementById('latitude').value;
        if (lat.trim() !== '') {
            const latNum = parseFloat(lat);
            if (isNaN(latNum) || latNum < -90 || latNum > 90) {
                showErr('err-latitude', 'Latitude harus antara -90 dan 90.'); hasError = true;
            }
        }

        const lng = document.getElementById('longitude').value;
        if (lng.trim() !== '') {
            const lngNum = parseFloat(lng);
            if (isNaN(lngNum) || lngNum < -180 || lngNum > 180) {
                showErr('err-longitude', 'Longitude harus antara -180 dan 180.'); hasError = true;
            }
        }

        const mapsUrl = document.getElementById('maps_url').value;
        if (mapsUrl.trim() !== '') {
            try { new URL(mapsUrl); } catch (_) {
                showErr('err-maps-url', 'Link Google Maps harus berupa URL yang valid.'); hasError = true;
            }
        }

        if (hasError) {
            e.preventDefault();
            const firstErr = document.querySelector('.sw-input-helper.sw-helper-error:not(.hidden)');
            if (firstErr) { firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        }
    });
    </script>
</x-app-layout>
