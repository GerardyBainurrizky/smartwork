@php
    $isDriverRoute = $route->user && $route->user->hasRole('driver');
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="route" :title="$isDriverRoute ? 'Edit Rencana Pengiriman' : 'Edit Rencana Kunjungan'" :subtitle="$isDriverRoute ? 'Perbarui rute pengiriman untuk Driver' : 'Perbarui rute kunjungan untuk Sales'"></x-page-header>
    </x-slot>

    <div class="max-w-2xl mx-auto" x-data="adminEditRoute()">
        @php
            $inputClass = 'w-full rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 shadow-sm transition hover:border-gray-300 dark:hover:border-gray-600 focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/20 focus:outline-none disabled:bg-gray-50 dark:disabled:bg-gray-900 disabled:text-gray-400 dark:disabled:text-gray-500';
            $labelClass = 'block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5';
            $req = '<span class="text-red-500">*</span>';
            $selectedStopsJson = $route->stops->map(function ($stop) {
                return [
                    'store_id' => $stop->store_id,
                    'sequence' => $stop->sequence,
                    'estimated_duration_minutes' => $stop->estimated_duration_minutes,
                    'notes' => $stop->notes,
                ];
            })->values();
        @endphp

        <x-back-button href="{{ $isDriverRoute ? route('admin.driver.routes.index') : route('admin.routes.index') }}" :label="$isDriverRoute ? 'Kembali ke Rencana Pengiriman' : 'Kembali ke Rencana Kunjungan'" class="mb-4" />

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="px-5 sm:p-6">
                <form @submit.prevent="submitForm" class="space-y-7">
                    {{-- Section: Informasi Rute --}}
                    <section>
                        <h2 class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            <svg class="w-4 h-4 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            Informasi Rute
                        </h2>
                        <div class="space-y-5">
                            <div>
                                <label class="{{ $labelClass }}">{{ $isDriverRoute ? 'Driver' : 'Sales Penanggung Jawab' }} {!! $req !!}</label>
                                <div class="relative flex items-center">
                                    <div class="w-full rounded-xl border-2 border-gray-200 dark:border-gray-700/80 bg-gray-50 dark:bg-gray-800/80 px-3.5 py-2.5 pr-10 text-sm font-medium text-gray-800 dark:text-gray-200 shadow-sm cursor-not-allowed select-none flex items-center justify-between">
                                        <span class="truncate">{{ $route->user?->name ?? ($isDriverRoute ? 'Driver Tidak Ditemukan' : 'Sales Tidak Ditemukan') }}</span>
                                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                                    {{ $isDriverRoute ? 'Driver pemilik rute pengiriman ini terkunci dan tidak dapat diubah.' : 'Sales penanggung jawab rute kunjungan ini terkunci dan tidak dapat diubah.' }}
                                </p>
                            </div>

                            <div>
                                <label for="name" class="{{ $labelClass }}">Rute Wilayah {!! $req !!}</label>
                                <input type="text" id="name" x-model="form.name"
                                    class="{{ $inputClass }}"
                                    :class="{ '!border-red-400 focus:!border-red-500 focus:!ring-red-200': errors.name }"
                                    @input="clearFieldError('name')"
                                    placeholder="{{ $isDriverRoute ? 'cth. Rute Pengiriman Subang Hari Ini' : 'cth. Rute Kunjungan Subang Hari Ini' }}"
                                    maxlength="200" required>
                                <p class="text-xs text-red-500 mt-1.5" x-show="errors.name" x-text="errors.name"></p>
                                <p class="text-xs text-gray-400 mt-1.5" x-show="!errors.name" x-text="200 - (form.name?.length || 0) + ' karakter tersisa'"></p>
                            </div>

                            <div>
                                <label for="date" class="{{ $labelClass }}">Tanggal {!! $req !!}</label>
                                <input type="date" id="date" x-model="form.date"
                                    class="{{ $inputClass }}"
                                    :class="{ '!border-red-400 focus:!border-red-500 focus:!ring-red-200': errors.date }"
                                    @input="clearFieldError('date')" required>
                                <p class="text-xs text-red-500 mt-1.5" x-show="errors.date" x-text="errors.date"></p>
                            </div>

                            <div>
                                <label for="notes" class="{{ $labelClass }}">Catatan <span class="font-normal text-gray-400">(opsional)</span></label>
                                <textarea id="notes" x-model="form.notes" rows="3"
                                    class="{{ $inputClass }} resize-none"
                                    placeholder="Tambahkan catatan untuk rute ini"
                                    maxlength="500"></textarea>
                                <p class="text-xs text-gray-400 mt-1.5" x-text="500 - (form.notes?.length || 0) + ' karakter tersisa'"></p>
                            </div>
                        </div>
                    </section>

                    {{-- Section: Pilih Toko & Estimasi Durasi --}}
                    <section>
                        <h2 class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            <svg class="w-4 h-4 text-[#0DA4CE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            {{ $isDriverRoute ? 'Daftar Tujuan' : 'Daftar Toko' }} {!! $req !!}
                        </h2>

                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $isDriverRoute ? 'Hapus tujuan yang tidak diinginkan, lalu tambahkan atau ubah urutan pengiriman.' : 'Hapus toko yang tidak diinginkan, lalu tambahkan atau ubah urutan kunjungan.' }}</p>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 shrink-0">
                                <span x-text="selectedStores.length" class="font-bold text-[#0DA4CE]"></span> toko
                            </span>
                        </div>

                        <div class="relative mb-3">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="searchQuery" placeholder="Cari nama toko, kota, atau provinsi..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 shadow-sm transition hover:border-gray-300 dark:hover:border-gray-600 focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/20 focus:outline-none">
                        </div>

                        <div class="border-2 border-gray-200 dark:border-gray-700 rounded-xl divide-y divide-gray-100 dark:divide-gray-800 max-h-80 overflow-y-auto bg-white dark:bg-gray-800/90" x-show="stores.length > 0">
                            <template x-for="store in filteredStores" :key="store.id">
                                <label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                                    :class="{ 'bg-[#0DA4CE]/5 dark:bg-[#0DA4CE]/10': isSelected(store.id) }">
                                    <input type="checkbox" :checked="isSelected(store.id)"
                                        @change="toggleStore(store)"
                                        class="rounded border-gray-300 dark:border-gray-600 text-[#0DA4CE] focus:ring-[#0DA4CE] dark:bg-gray-700">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="store.name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="getStoreLocation(store)"></p>
                                    </div>
                                    <svg x-show="isSelected(store.id)" class="w-5 h-5 text-[#0DA4CE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </label>
                            </template>
                            <template x-if="stores.length > 0 && filteredStores.length === 0">
                                <div class="px-4 py-8 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Tidak ada toko ditemukan</p>
                                </div>
                            </template>
                        </div>

                        <template x-if="selectedStores.length > 0">
                            <div class="mt-5">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    {{ $isDriverRoute ? 'Urutan Pengiriman' : 'Urutan Kunjungan & Estimasi Durasi' }}
                                </p>
                                <div class="space-y-3">
                                    <template x-for="(stop, index) in selectedStores" :key="stop.store_id">
                                        <div class="p-3 sm:p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border-2 border-gray-100 dark:border-gray-700/60"
                                            x-data="{ dragging: false }"
                                            draggable="true"
                                            @dragstart="dragging = true; $event.dataTransfer.setData('text/plain', index)"
                                            @dragend="dragging = false"
                                            @dragover.prevent="$el.classList.add('ring-2', 'ring-[#0DA4CE]')"
                                            @dragleave="$el.classList.remove('ring-2', 'ring-[#0DA4CE]')"
                                            @drop="moveStore($event, index)">
                                            <div class="flex items-center gap-3">
                                                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-[#0DA4CE] text-white text-xs font-bold"
                                                    x-text="stop.sequence || (index + 1)"></span>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                                                        x-text="getStoreName(stop.store_id)"></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                                        x-text="getStoreInfo(stop.store_id)"></p>
                                                </div>
                                                <button type="button" @click="removeStore(index)"
                                                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition shrink-0"
                                                    aria-label="Hapus toko">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="mt-3 grid grid-cols-1 {{ $isDriverRoute ? 'sm:grid-cols-2' : 'sm:grid-cols-3' }} gap-3">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1" :for="'seq-' + stop.store_id">
                                                        {{ $isDriverRoute ? 'Urutan Pengiriman' : 'Urutan Kunjungan' }} <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="number" x-model="stop.sequence"
                                                        :disabled="dragging"
                                                        :id="'seq-' + stop.store_id"
                                                        min="1" :max="selectedStores.length" step="1" inputmode="numeric"
                                                        placeholder="cth. 1"
                                                        @input="stop.sequence = String(stop.sequence).replace(/[^0-9]/g, ''); if(parseInt(stop.sequence, 10) > selectedStores.length) { stop.sequence = selectedStores.length; } clearStopError(stop.store_id, 'sequence')"
                                                        @blur="onSequenceBlur(stop)"
                                                        class="{{ $inputClass }}"
                                                        :class="{ '!border-red-400 focus:!border-red-500 focus:!ring-red-200': errors.byStore[stop.store_id]?.sequence }">
                                                    <p class="text-xs text-red-500 mt-1" x-show="errors.byStore[stop.store_id]?.sequence" x-text="errors.byStore[stop.store_id]?.sequence"></p>
                                                </div>
                                                @if(!$isDriverRoute)
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1" :for="'dur-' + stop.store_id">
                                                        Estimasi Durasi (Menit) <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="number" x-model="stop.estimated_duration_minutes"
                                                        :disabled="dragging"
                                                        :id="'dur-' + stop.store_id"
                                                        min="1" max="480" step="1" inputmode="numeric"
                                                        placeholder="cth. 30"
                                                        @input="clearStopError(stop.store_id, 'duration')"
                                                        class="{{ $inputClass }}"
                                                        :class="{ '!border-red-400 focus:!border-red-500 focus:!ring-red-200': errors.byStore[stop.store_id]?.duration }">
                                                    <p class="text-xs text-red-500 mt-1" x-show="errors.byStore[stop.store_id]?.duration" x-text="errors.byStore[stop.store_id]?.duration"></p>
                                                    <p class="text-xs text-gray-400 mt-1" x-show="!errors.byStore[stop.store_id]?.duration">1 - 480 menit</p>
                                                </div>
                                                @endif
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1" :for="'note-' + stop.store_id">
                                                        Catatan <span class="font-normal text-gray-400">(opsional)</span>
                                                    </label>
                                                    <input type="text" x-model="stop.notes"
                                                        :disabled="dragging"
                                                        :id="'note-' + stop.store_id"
                                                        maxlength="255"
                                                        placeholder="{{ $isDriverRoute ? 'Catatan pengiriman' : 'Catatan kunjungan' }}"
                                                        class="{{ $inputClass }}">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </section>

                    {{-- Submit --}}
                    <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ $isDriverRoute ? route('admin.driver.routes.index') : route('admin.routes.index') }}"
                            class="sm:flex-1 inline-flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Batal
                        </a>
                        <button type="submit" :disabled="loading || selectedStores.length === 0"
                            class="sm:flex-1 inline-flex items-center justify-center px-4 py-3 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] disabled:opacity-50 transition shadow-sm shadow-[#0DA4CE]/20">
                            <svg x-show="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="toast.show" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            class="fixed bottom-6 left-4 right-4 z-50 max-w-lg mx-auto">
            <div class="rounded-2xl px-6 py-4 shadow-xl border"
                :class="toast.type === 'success' ? 'bg-green-50 dark:bg-green-950/90 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-950/90 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'">
                <div class="flex items-center gap-3">
                    <svg x-show="toast.type === 'success'" class="w-6 h-6 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
        function adminEditRoute() {
            return {
                loading: false,
                searchQuery: '',
                stores: @json($stores),
                salesUsers: @json($salesUsers),
                form: {
                    user_id: '{{ $route->user_id }}',
                    name: '{{ $route->name }}',
                    date: '{{ $route->date->format('Y-m-d') }}',
                    notes: '{{ $route->notes ?? '' }}',
                },
                selectedStores: @json($selectedStopsJson),
                toast: { show: false, message: '', type: 'success' },
                errors: { user_id: '', name: '', date: '', general: '', byStore: {} },

                get filteredStores() {
                    let list = this.stores;
                    const isDriver = '{{ $route->user?->hasRole('driver') ? '1' : '0' }}' === '1';
                    if (!isDriver && this.form.user_id) {
                        list = list.filter(s => String(s.sales_penanggung_jawab_id) === String(this.form.user_id));
                    }
                    if (!this.searchQuery) return list;
                    const query = this.searchQuery.toLowerCase();
                    return list.filter(s =>
                        s.name.toLowerCase().includes(query) ||
                        (s.code && s.code.toLowerCase().includes(query)) ||
                        (s.kecamatan && s.kecamatan.toLowerCase().includes(query)) ||
                        (s.city && s.city.toLowerCase().includes(query)) ||
                        (s.province && s.province.toLowerCase().includes(query)) ||
                        (s.address && s.address.toLowerCase().includes(query))
                    );
                },

                getStoreName(storeId) {
                    const store = this.stores.find(s => s.id === storeId);
                    return store ? store.name : 'Toko';
                },

                getStoreLocation(store) {
                    const parts = [];
                    if (store.kecamatan && String(store.kecamatan).trim()) parts.push(String(store.kecamatan).trim());
                    if (store.city && String(store.city).trim()) parts.push(String(store.city).trim());
                    if (store.province && String(store.province).trim()) parts.push(String(store.province).trim());
                    if (store.code && String(store.code).trim()) parts.push(String(store.code).trim());
                    return parts.join(' · ');
                },

                getStoreInfo(storeId) {
                    const store = this.stores.find(s => s.id === storeId);
                    if (!store) return '';
                    return this.getStoreLocation(store);
                },

                isSelected(storeId) {
                    return this.selectedStores.some(s => s.store_id === storeId);
                },

                toggleStore(store) {
                    const idx = this.selectedStores.findIndex(s => s.store_id === store.id);
                    if (idx === -1) {
                        this.selectedStores.push({
                            store_id: store.id,
                            sequence: this.nextSequence(),
                            estimated_duration_minutes: {{ $isDriverRoute ? 'null' : '30' }},
                            notes: '',
                        });
                    } else {
                        this.selectedStores.splice(idx, 1);
                        this.renumberSequences();
                    }
                },

                removeStore(index) {
                    this.selectedStores.splice(index, 1);
                    this.renumberSequences();
                },

                moveStore(event, targetIndex) {
                    const sourceIndex = parseInt(event.dataTransfer.getData('text/plain'));
                    const item = this.selectedStores.splice(sourceIndex, 1)[0];
                    this.selectedStores.splice(targetIndex, 0, item);
                    this.renumberSequences();
                    event.target.classList.remove('ring-2', 'ring-[#0DA4CE]');
                },

                nextSequence() {
                    const used = this.selectedStores.map(s => parseInt(s.sequence, 10) || 0);
                    return (used.length ? Math.max(...used) : 0) + 1;
                },

                renumberSequences() {
                    this.selectedStores.forEach((s, i) => {
                        s.sequence = i + 1;
                    });
                    this.errors.byStore = {};
                },

                onSequenceBlur(stop) {
                    let seq = parseInt(stop.sequence, 10);
                    const maxSeq = this.selectedStores.length;
                    
                    if (!Number.isInteger(seq) || seq < 1) {
                        seq = 1;
                    } else if (seq > maxSeq) {
                        seq = maxSeq;
                    }
                    
                    stop.sequence = seq;

                    const oldIndex = this.selectedStores.findIndex(s => s.store_id === stop.store_id);
                    const newIndex = seq - 1;

                    if (oldIndex !== newIndex) {
                        const item = this.selectedStores.splice(oldIndex, 1)[0];
                        this.selectedStores.splice(newIndex, 0, item);
                        this.renumberSequences();
                    }
                },

                clearStopError(storeId, key) {
                    const err = this.errors.byStore[storeId];
                    if (!err) return;
                    delete err[key];
                    if (!err.sequence && !err.duration) {
                        delete this.errors.byStore[storeId];
                    }
                },

                clearFieldError(key) {
                    this.errors[key] = '';
                    this.errors.general = '';
                },

                validateForm() {
                    const errors = { user_id: '', name: '', date: '', general: '', byStore: {} };
                    const isDriverRoute = {{ $isDriverRoute ? 'true' : 'false' }};

                    if (!this.form.user_id) {
                        errors.user_id = isDriverRoute ? 'Driver wajib dipilih.' : 'Sales wajib dipilih.';
                    }
                    if (!this.form.name || !this.form.name.trim()) {
                        errors.name = 'Rute Wilayah wajib diisi.';
                    }
                    if (!this.form.date) {
                        errors.date = 'Tanggal wajib diisi.';
                    }
                    if (this.selectedStores.length === 0) {
                        errors.general = 'Pilih minimal satu toko.';
                        return errors;
                    }

                    const n = this.selectedStores.length;
                    const seqSet = new Set();
                    let isValidSequenceRange = true;

                    this.selectedStores.forEach(s => {
                        const err = { sequence: '', duration: '' };
                        const storeName = this.getStoreName(s.store_id);
                        const seqRaw = String(s.sequence ?? '').trim();
                        const durRaw = String(s.estimated_duration_minutes ?? '').trim();

                        if (seqRaw === '') {
                            err.sequence = isDriverRoute
                                ? `Urutan pengiriman untuk ${storeName} wajib diisi.`
                                : `Urutan kunjungan untuk ${storeName} wajib diisi.`;
                            isValidSequenceRange = false;
                        } else if (!/^\d+$/.test(seqRaw)) {
                            err.sequence = isDriverRoute
                                ? `Urutan pengiriman untuk ${storeName} harus berupa angka.`
                                : `Urutan kunjungan untuk ${storeName} harus berupa angka.`;
                            isValidSequenceRange = false;
                        } else {
                            const seqNum = parseInt(seqRaw, 10);
                            if (seqNum < 1 || seqNum > n) {
                                err.sequence = isDriverRoute
                                    ? `Urutan pengiriman harus berurutan dari 1 sampai ${n}.`
                                    : `Urutan kunjungan harus berurutan dari 1 sampai ${n}.`;
                                isValidSequenceRange = false;
                            } else {
                                seqSet.add(seqNum);
                            }
                        }

                        if (!isDriverRoute) {
                            if (durRaw === '') {
                                err.duration = `Estimasi durasi untuk ${storeName} wajib diisi.`;
                            } else if (!/^\d+$/.test(durRaw) || parseInt(durRaw, 10) < 1) {
                                err.duration = 'Estimasi durasi harus berupa angka dalam menit.';
                            } else if (parseInt(durRaw, 10) > 480) {
                                err.duration = 'Estimasi durasi maksimal 480 menit.';
                            }
                        }

                        errors.byStore[s.store_id] = err;
                    });

                    if (isValidSequenceRange && seqSet.size !== n) {
                        this.selectedStores.forEach(s => {
                            if (errors.byStore[s.store_id]) {
                                errors.byStore[s.store_id].sequence = isDriverRoute
                                    ? `Urutan pengiriman harus berurutan dari 1 sampai ${n}.`
                                    : `Urutan kunjungan harus berurutan dari 1 sampai ${n}.`;
                            }
                        });
                    }

                    return errors;
                },

                getSubmitStops() {
                    const isDriverRoute = {{ $isDriverRoute ? 'true' : 'false' }};
                    return this.selectedStores.map(s => ({
                        store_id: s.store_id,
                        sequence: s.sequence,
                        estimated_duration_minutes: isDriverRoute ? null : (s.estimated_duration_minutes || null),
                        notes: s.notes || null,
                    }));
                },

                async submitForm() {
                    this.errors = this.validateForm();
                    const hasErrors = this.errors.user_id
                        || this.errors.name
                        || this.errors.date
                        || this.errors.general
                        || Object.values(this.errors.byStore).some(e => e.sequence || e.duration);

                    if (hasErrors) {
                        this.showToast('Periksa kembali data rute sebelum menyimpan.', 'error');
                        return;
                    }

                    this.loading = true;
                    try {
                        const targetUpdateUrl = '{{ $isDriverRoute ? route('admin.driver.routes.update', $route->id) : route('admin.routes.update', $route->id) }}';
                        const res = await fetch(targetUpdateUrl, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                user_id: this.form.user_id,
                                name: this.form.name,
                                date: this.form.date,
                                notes: this.form.notes,
                                stops: this.getSubmitStops(),
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            let message = data.message || 'Gagal memperbarui rute';
                            if (data.errors) {
                                const first = Object.values(data.errors)[0];
                                message = Array.isArray(first) ? first[0] : String(first);
                            }
                            throw new Error(message);
                        }
                        this.showToast(data.message || 'Rute berhasil diperbarui', 'success');
                        setTimeout(() => window.location.href = '{{ $isDriverRoute ? route('admin.driver.routes.index') : route('admin.routes.index') }}', 1200);
                    } catch (e) {
                        this.showToast(e.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                showToast(message, type) {
                    this.toast = { show: true, message, type };
                    setTimeout(() => this.toast.show = false, 4000);
                },
            };
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
