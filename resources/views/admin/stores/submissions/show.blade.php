<x-app-layout>
    <x-slot name="header">
        <x-page-header icon="store" title="Periksa Pengajuan Toko" subtitle="Tinjau informasi toko yang diajukan Sales dan tentukan assignment"></x-page-header>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6"
         x-data="{
             rejectModal: false,
             salesId: '{{ old('sales_penanggung_jawab_id', $submission->user_id) }}',
             delivery: '{{ old('is_delivery_destination', '1') }}',
             get hasSales() {
                 return this.salesId && this.salesId !== '';
             }
         }">
        <a href="{{ route('admin.stores.submissions.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-[#0DA4CE] transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Daftar Pengajuan
        </a>

        {{-- Peringatan Potensi Duplikasi (BR-14) --}}
        @if($potentialDuplicates->isNotEmpty())
            <div class="rounded-2xl p-5 bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-300 dark:border-amber-700/60 text-amber-900 dark:text-amber-100 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-sm">Peringatan Kemungkinan Toko Duplikat</h4>
                        <p class="text-xs text-amber-800 dark:text-amber-200 mt-1">Ditemukan toko di Master Toko yang memiliki kemiripan nama, alamat, atau nomor telepon dengan pengajuan ini:</p>
                        <div class="mt-3 space-y-2">
                            @foreach($potentialDuplicates as $dup)
                                <div class="p-2.5 bg-white dark:bg-gray-900 rounded-xl border border-amber-200 dark:border-amber-800/60 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $dup->name }} ({{ $dup->code }})</span>
                                        <p class="text-gray-500 dark:text-gray-400 mt-0.5">{{ $dup->address }} &middot; {{ $dup->city }}</p>
                                    </div>
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $dup->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $dup->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Detail Pengajuan & Form Persetujuan --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 border-b border-gray-100 dark:border-gray-800 pb-5 mb-6">
                <div class="min-w-0 flex-1">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white break-words">{{ $submission->name }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Diajukan oleh <strong class="text-gray-700 dark:text-gray-300">{{ $submission->user?->name ?: 'Sales' }}</strong> pada {{ $submission->created_at?->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="self-start sm:self-center shrink-0">
                    @if($submission->status === 'pending')
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Menunggu Keputusan
                        </span>
                    @elseif($submission->status === 'approved')
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Telah Disetujui
                        </span>
                    @elseif($submission->status === 'rejected')
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-red-50 dark:bg-red-950/50 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/60 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            Telah Ditolak
                        </span>
                    @endif
                </div>
            </div>

            @if($submission->status === 'pending')
                <form action="{{ route('admin.stores.submissions.approve', $submission->id) }}" method="POST" class="space-y-5">
                    @csrf

                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[#0DA4CE]">
                        1. Penentuan Assignment &amp; Kebijakan (Wajib oleh Admin - BR-13)
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 p-4 sm:p-5 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-100 dark:border-gray-800">
                        {{-- Sales Penanggung Jawab --}}
                        <div class="w-full min-w-0">
                            <label class="sw-label block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5" for="sales_penanggung_jawab_id">Sales Penanggung Jawab</label>
                            <select id="sales_penanggung_jawab_id" name="sales_penanggung_jawab_id" x-model="salesId" class="sw-input w-full min-h-[2.75rem] text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE]">
                                <option value="">-- Belum Memiliki Sales (Toko Umum) --</option>
                                @foreach($salesUsers as $sales)
                                    <option value="{{ $sales->id }}">
                                        Sales: {{ $sales->name }} ({{ $sales->username }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">Admin menentukan jangkauan Sales (default: sales yang mengajukan).</p>
                        </div>

                        {{-- Tujuan Pengiriman Driver --}}
                        <div class="w-full min-w-0">
                            <label class="sw-label block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tujuan Pengiriman Driver</label>
                            <div x-show="hasSales" x-cloak>
                                <input type="hidden" name="is_delivery_destination" value="1" :disabled="!hasSales">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-3.5 py-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-xs">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Ya — Otomatis dapat dikirim Driver</span>
                                </div>
                            </div>
                            <div x-show="!hasSales" x-cloak>
                                <div class="mt-2 flex flex-wrap gap-4 py-1">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="is_delivery_destination" value="1" x-model="delivery" :disabled="hasSales"
                                            class="text-[#0DA4CE] focus:ring-[#0DA4CE] bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600">
                                        <span class="ml-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Ya (Dapat Dikirim)</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="is_delivery_destination" value="0" x-model="delivery" :disabled="hasSales"
                                            class="text-[#0DA4CE] focus:ring-[#0DA4CE] bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600">
                                        <span class="ml-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Tidak</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Status Toko --}}
                        <div class="sm:col-span-2 pt-2 border-t border-gray-200/60 dark:border-gray-700/60">
                            <label class="sw-label block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Status Toko Awal</label>
                            <div class="mt-1 flex flex-wrap gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="status" value="active" class="text-[#0DA4CE] focus:ring-[#0DA4CE] bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600" {{ old('status', 'active') === 'active' ? 'checked' : '' }}>
                                    <span class="ml-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Aktif (Langsung dapat digunakan)</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="status" value="inactive" class="text-[#0DA4CE] focus:ring-[#0DA4CE] bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600" {{ old('status') === 'inactive' ? 'checked' : '' }}>
                                    <span class="ml-2 text-xs font-semibold text-gray-700 dark:text-gray-300">Nonaktif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[#0DA4CE] pt-2">
                        2. Verifikasi &amp; Koreksi Data Toko
                    </h3>

                    <div>
                        <label class="sw-label" for="name">Nama Toko *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $submission->name) }}" class="sw-input" required>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                        <div>
                            <label class="sw-label" for="owner">Nama Pemilik / PIC</label>
                            <input type="text" id="owner" name="owner" value="{{ old('owner', $submission->owner) }}" class="sw-input">
                        </div>
                        <div>
                            <label class="sw-label" for="phone">No. Telepon Toko / PIC</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $submission->phone) }}" class="sw-input">
                        </div>
                    </div>

                    <div>
                        <label class="sw-label" for="address">Alamat Lengkap *</label>
                        <textarea id="address" name="address" rows="3" class="sw-input" required>{{ old('address', $submission->address) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                        <div>
                            <label class="sw-label" for="city">Kota / Kabupaten *</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $submission->city) }}" class="sw-input" required>
                        </div>
                        <div>
                            <label class="sw-label" for="kecamatan">Kecamatan</label>
                            <input type="text" id="kecamatan" name="kecamatan" value="{{ old('kecamatan', $submission->kecamatan) }}" class="sw-input">
                        </div>
                        <div>
                            <label class="sw-label" for="province">Provinsi *</label>
                            <input type="text" id="province" name="province" value="{{ old('province', $submission->province) }}" class="sw-input" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                        <div>
                            <label class="sw-label" for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" value="{{ old('latitude', $submission->latitude) }}" class="sw-input">
                        </div>
                        <div>
                            <label class="sw-label" for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" value="{{ old('longitude', $submission->longitude) }}" class="sw-input">
                        </div>
                    </div>

                    <div>
                        <label class="sw-label" for="maps_url">Link Google Maps</label>
                        <input type="url" id="maps_url" name="maps_url" value="{{ old('maps_url', $submission->maps_url) }}" class="sw-input">
                    </div>

                    @if($submission->photo)
                        <div>
                            <span class="sw-label">Foto dari Sales:</span>
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $submission->photo) }}" alt="Foto Toko" class="w-full max-w-xs sm:w-48 h-36 object-cover rounded-xl border border-gray-200 dark:border-gray-700">
                            </div>
                        </div>
                    @endif

                    @if($submission->notes)
                        <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-200 dark:border-blue-800 text-xs">
                            <span class="font-bold text-blue-900 dark:text-blue-100">Catatan Sales:</span>
                            <p class="text-blue-800 dark:text-blue-200 mt-0.5">{{ $submission->notes }}</p>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-5 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" @click="rejectModal = true"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 sm:py-2.5 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-xl text-sm font-semibold transition border border-red-200/60 dark:border-red-800/40">
                            Tolak Pengajuan
                        </button>
                        <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 sm:py-2.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-sm font-semibold transition shadow-xs">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Setujui &amp; Buat Master Toko
                        </button>
                    </div>
                </form>
            @else
                <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                    <p>Status: <strong class="uppercase">{{ $submission->status }}</strong></p>
                    @if($submission->status === 'approved' && $submission->store)
                        <p>Master Toko Terkait: <a href="{{ route('admin.stores.edit', $submission->store->id) }}" class="text-[#0DA4CE] font-bold underline">{{ $submission->store->code }} - {{ $submission->store->name }}</a></p>
                    @endif
                    @if($submission->status === 'rejected')
                        <p class="text-red-600">Alasan Penolakan: {{ $submission->rejection_reason }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Modal Tolak Pengajuan --}}
        <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div x-show="rejectModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="rejectModal = false"></div>
                <div x-show="rejectModal" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:max-w-md w-full p-5 sm:p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-2">Tolak Pengajuan Toko?</h3>
                    <p class="text-xs text-gray-500 mb-4">Berikan alasan penolakan agar Sales mendapatkan penjelasan yang jelas.</p>

                    <form action="{{ route('admin.stores.submissions.reject', $submission->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="sw-label" for="rejection_reason">Alasan Penolakan *</label>
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" class="sw-input" placeholder="Misal: Toko sudah terdaftar dengan nama Toko Sumber Rejeki (ST-004)" required></textarea>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 sm:gap-3 pt-2">
                            <button type="button" @click="rejectModal = false" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">Batal</button>
                            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition">Tolak Pengajuan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
