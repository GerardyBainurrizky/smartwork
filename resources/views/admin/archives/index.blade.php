<x-app-layout>
    @section('breadcrumbs', 'Arsip & Kelola Data')

    <div class="animate-fade-in space-y-6 max-w-7xl mx-auto" x-data="archiveManager()" @keydown.escape.window="closeDownloadMenu()">
        {{-- Header Halaman --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#0DA4CE]/10 text-[#0DA4CE] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    Arsip &amp; Kelola Data
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Pusat arsip data Super Admin. Kelola arsip data operasional, pulihkan (restore), atau hapus permanen secara aman.
                </p>
            </div>
            <div class="self-start inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#0DA4CE]/10 text-[#0DA4CE] dark:bg-[#0DA4CE]/20 border border-[#0DA4CE]/20">
                <span class="w-1.5 h-1.5 rounded-full bg-[#0DA4CE] animate-pulse"></span>
                Khusus Super Admin
            </div>
        </div>

        {{-- Flash Messages dengan Auto-Dismiss 4 Detik & Transisi Halus --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"
            class="rounded-xl p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-100 text-sm flex items-center justify-between gap-3 shadow-md">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 p-1 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"
            class="rounded-xl p-4 bg-red-50 dark:bg-red-950/60 border border-red-300 dark:border-red-700 text-red-900 dark:text-red-100 text-sm flex items-center justify-between gap-3 shadow-md">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" type="button" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 p-1 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif

        {{-- 1. Ringkasan Data Aktif & Arsip (KPI Cards Modern) --}}
        <div class="archive-kpi-grid grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5 sm:gap-3">
            {{-- Card Total --}}
            <div class="col-span-2 sm:col-span-4 lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3.5 sm:p-4 shadow-xs min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total Operasional</span>
                    <span class="w-2 h-2 rounded-full bg-[#0DA4CE]"></span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ number_format($totalActive, 0, ',', '.') }}</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Aktif</span>
                </div>
                <p class="text-[11px] sm:text-xs text-gray-400 dark:text-gray-500 mt-1 sm:mt-1.5">
                    <span class="font-semibold text-amber-600 dark:text-amber-400">{{ number_format($totalArchived, 0, ',', '.') }}</span> diarsipkan
                </p>
            </div>

            {{-- Card Presensi --}}
            <div class="col-span-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3 sm:p-3.5 shadow-xs min-w-0">
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate">Presensi</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ number_format($totalAttendanceActive, 0, ',', '.') }}</div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 truncate">{{ number_format($totalAttendanceArchived, 0, ',', '.') }} arsip</p>
            </div>

            {{-- Card Rencana Sales --}}
            <div class="col-span-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3 sm:p-3.5 shadow-xs min-w-0">
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate">Rencana Sales</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                </div>
                <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ number_format($totalRouteActive, 0, ',', '.') }}</div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 truncate">{{ number_format($totalRouteArchived, 0, ',', '.') }} arsip</p>
            </div>

            {{-- Card Kunjungan Sales --}}
            <div class="col-span-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3 sm:p-3.5 shadow-xs min-w-0">
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate">Kunjungan</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ number_format($totalVisitActive, 0, ',', '.') }}</div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 truncate">{{ number_format($totalVisitArchived, 0, ',', '.') }} arsip</p>
            </div>

            {{-- Card Rencana Driver --}}
            <div class="col-span-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3 sm:p-3.5 shadow-xs min-w-0">
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate">Rencana Driver</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 18h-2m-2.5-4h11.5m-11.5-4H20M5 6h12l4 5v5H5V6z"/></svg>
                </div>
                <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ number_format($totalRouteDriverActive, 0, ',', '.') }}</div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 truncate">{{ number_format($totalRouteDriverArchived, 0, ',', '.') }} arsip</p>
            </div>

            {{-- Card Pengiriman Driver --}}
            <div class="col-span-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3 sm:p-3.5 shadow-xs min-w-0">
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate">Pengiriman</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                </div>
                <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ number_format($totalVisitDriverActive, 0, ',', '.') }}</div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 truncate">{{ number_format($totalVisitDriverArchived, 0, ',', '.') }} arsip</p>
            </div>

            {{-- Card Transaksi --}}
            <div class="col-span-1 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-3 sm:p-3.5 shadow-xs min-w-0">
                <div class="flex items-center justify-between gap-1 mb-1.5">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 truncate">Transaksi</span>
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">{{ number_format($totalTransactionActive, 0, ',', '.') }}</div>
                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 truncate">{{ number_format($totalTransactionArchived, 0, ',', '.') }} arsip</p>
            </div>
        </div>

        {{-- 2. Form Aksi Pengarsipan Manual - ROMBAK TOTAL UI --}}
        <style>
            .archive-form-card{box-sizing:border-box;width:100%;max-width:100%;min-width:0;overflow:hidden}
            .archive-form-card *,.archive-form-card *::before,.archive-form-card *::after{box-sizing:border-box}
            .archive-form-header{display:flex;align-items:flex-start;gap:0.75rem;padding-bottom:1rem;margin-bottom:0;border-bottom:1px solid rgb(243 244 246)}
            .dark .archive-form-header{border-color:rgb(31 41 55)}
            .archive-form-body{display:flex;flex-direction:column;gap:1rem;width:100%;max-width:100%;min-width:0;padding-top:1rem}
            @media(min-width:640px){.archive-form-body{gap:1.125rem;padding-top:1.125rem}}
            .archive-field-group{width:100%;max-width:100%;min-width:0;display:flex;flex-direction:column}
            .archive-field-label{display:block;font-size:0.75rem;font-weight:700;color:rgb(55 65 81);margin:0 0 0.375rem 0;line-height:1.4;letter-spacing:0.01em}
            .dark .archive-field-label{color:rgb(229 231 235)}
            @media(min-width:640px){.archive-field-label{font-size:0.8125rem;margin-bottom:0.5rem}}
            .archive-period-section{width:100%;max-width:100%;min-width:0;display:flex;flex-direction:column}
            .archive-period-head{display:flex;align-items:center;gap:0.375rem;margin:0 0 0.625rem 0}
            .archive-period-title{font-size:0.75rem;font-weight:700;color:rgb(55 65 81);line-height:1.4;letter-spacing:0.01em}
            .dark .archive-period-title{color:rgb(229 231 235)}
            @media(min-width:640px){.archive-period-title{font-size:0.8125rem}.archive-period-head{margin-bottom:0.75rem}}
            .archive-date-grid {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: 12px;
            }
            @media (min-width: 640px) {
                .archive-date-grid {
                    gap: 16px;
                }
            }
            .archive-date-field {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                box-sizing: border-box;
            }
            .archive-date-label {
                display: block;
                width: 100%;
                margin-bottom: 6px;
                font-size: 0.75rem;
                font-weight: 600;
                color: rgb(75 85 99);
                line-height: 1.25;
            }
            .dark .archive-date-label{color:rgb(209 213 219)}
            .archive-control{box-sizing:border-box;width:100%!important;max-width:100%!important;min-width:0!important;min-inline-size:0!important;display:block;height:2.75rem;min-height:2.75rem;font-size:0.8125rem;border-radius:0.75rem;border:1px solid rgb(209 213 219);background:#fff;color:rgb(17 24 39);padding:0 0.875rem;outline:none;transition:border-color 0.15s,box-shadow 0.15s;font-weight:500;line-height:normal}
            .dark .archive-control{background:rgb(31 41 55);border-color:rgb(75 85 99);color:#fff}
            .archive-control:hover{border-color:rgb(156 163 175)}.dark .archive-control:hover{border-color:rgb(107 114 128)}
            .archive-control:focus{border-color:#0DA4CE;box-shadow:0 0 0 3px rgba(13,164,206,0.15)}
            @media(min-width:640px){.archive-control{height:3rem;min-height:3rem;font-size:0.875rem}}
            .archive-control::placeholder{color:rgb(156 163 175)}

            .archive-form-card input[type="date"].archive-control,
            .archive-form-card #archive_start_date,
            .archive-form-card #archive_end_date {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                min-inline-size: 0 !important;
                box-sizing: border-box !important;

                height: 48px !important;
                min-height: 48px !important;

                padding: 0 10px !important;

                font-size: 15px !important;
                line-height: 48px !important;

                border-radius: 14px !important;

                vertical-align: middle !important;
            }

            @media (min-width: 640px) {
                .archive-form-card input[type="date"].archive-control,
                .archive-form-card #archive_start_date,
                .archive-form-card #archive_end_date {
                    padding: 0 14px !important;
                    font-size: 16px !important;
                    border-radius: 16px !important;
                }
            }

            .archive-form-card input[type="date"]::-webkit-date-and-time-value {
                height: 48px;
                line-height: 48px;
                text-align: left;
            }

            .archive-form-card input[type="date"]::-webkit-datetime-edit {
                line-height: 48px;
            }

            .archive-form-card input[type="date"]::-webkit-calendar-picker-indicator {
                margin: 0;
                padding: 0;
                cursor: pointer;
            }

            @media(max-width:1023px){.archive-form-card .archive-control{font-size:16px!important}}
            .archive-form-actions{display:flex;flex-direction:column;gap:0.625rem;padding-top:0.75rem;width:100%;max-width:100%;min-width:0;border-top:1px solid rgb(249 250 251);margin-top:0.25rem}
            .dark .archive-form-actions{border-color:rgb(31 41 55 / 0.5)}
            @media(min-width:640px){.archive-form-actions{flex-direction:row;justify-content:flex-end;align-items:center;gap:0.75rem;padding-top:1rem}}
            .archive-btn{box-sizing:border-box;display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;max-width:100%;min-width:0;height:2.75rem;padding:0 1.25rem;border-radius:0.75rem;font-size:0.8125rem;font-weight:600;transition:all 0.15s;white-space:nowrap;cursor:pointer}
            @media(min-width:640px){.archive-btn{width:auto;height:3rem;font-size:0.875rem;padding:0 1.5rem}}
            .archive-btn:active{transform:scale(0.97)}
            .archive-btn-secondary{background:#fff;border:1px solid rgb(209 213 219);color:rgb(55 65 81);box-shadow:0 1px 2px rgba(0,0,0,0.04)}
            .dark .archive-btn-secondary{background:rgb(31 41 55);border-color:rgb(75 85 99);color:rgb(229 231 235)}
            .archive-btn-secondary:hover{background:rgb(249 250 251)}.dark .archive-btn-secondary:hover{background:rgb(55 65 81)}
            .archive-btn-primary{background:rgb(217 119 6);color:#fff;border:1px solid rgb(217 119 6);box-shadow:0 1px 2px rgba(217,119,6,0.2)}
            .archive-btn-primary:hover{background:rgb(180 83 9);border-color:rgb(180 83 9)}
            .archive-btn-primary:disabled{opacity:0.5;cursor:not-allowed}
            .archive-kpi-grid,.archive-history-filter-wrap{min-width:0;max-width:100%}
            .archive-kpi-grid > *{min-width:0}
        </style>
        <div class="archive-form-card bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 sm:p-5 lg:p-6 shadow-xs">
            <div class="archive-form-header">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-[15px] sm:text-base font-bold text-gray-900 dark:text-white leading-tight">Arsipkan Data Lama</h2>
                    <p class="text-xs sm:text-[13px] text-gray-500 dark:text-gray-400 leading-relaxed mt-1">Pilih jenis data dan periode untuk mengarsipkan data operasional secara aman tanpa menghapus data fisik.</p>
                </div>
            </div>
            <form @submit.prevent="checkPreview()" class="min-w-0 max-w-full">
                <div class="archive-form-body">
                    <div class="archive-field-group">
                        <label for="archive_data_type" class="archive-field-label">Jenis Data</label>
                        <select id="archive_data_type" x-model="form.data_type" class="archive-control">
                            <option value="all">Semua Data Operasional</option>
                            <option value="attendance">Presensi</option>
                            <option value="route">Rencana Kunjungan (Sales)</option>
                            <option value="visit">Kunjungan Sales</option>
                            <option value="route_driver">Rencana Pengiriman (Driver)</option>
                            <option value="visit_driver">Pengiriman (Driver)</option>
                            <option value="transaction">Transaksi</option>
                            <option value="transaction_result">Hasil Transaksi</option>
                            <option value="receivable">Piutang</option>
                            <option value="finance">Keuangan</option>
                        </select>
                    </div>
                    <div class="archive-period-section">
                        <div class="archive-period-head">
                            <svg class="w-4 h-4 text-[#0DA4CE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="archive-period-title">Periode Arsip</span>
                        </div>
                        <div class="archive-date-grid">
                            <div class="archive-date-field">
                                <label for="archive_start_date" class="archive-date-label">Mulai dari</label>
                                <input id="archive_start_date" type="date" x-model="form.start_date" required aria-label="Mulai dari" class="archive-control">
                            </div>
                            <div class="archive-date-field">
                                <label for="archive_end_date" class="archive-date-label">Sampai dengan</label>
                                <input id="archive_end_date" type="date" x-model="form.end_date" required aria-label="Sampai dengan" class="archive-control">
                            </div>
                        </div>
                    </div>
                    <div class="archive-field-group">
                        <label for="archive_notes" class="archive-field-label">Catatan / Alasan Pengarsipan (Opsional)</label>
                        <input id="archive_notes" type="text" x-model="form.notes" placeholder="Contoh: Pengarsipan kuartal 1 tahun 2026" class="archive-control">
                    </div>
                </div>
                <div class="archive-form-actions">
                    <button type="button" @click="resetForm()" class="archive-btn archive-btn-secondary order-2 sm:order-1">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset</span>
                    </button>
                    <button type="submit" :disabled="loading" class="archive-btn archive-btn-primary order-1 sm:order-2">
                        <svg x-show="loading" class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <svg x-show="!loading" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Cek &amp; Pratinjau</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- 3. Riwayat Sesi Arsip & Manajemen Restore --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-visible shadow-xs">
            <div class="archive-history-filter-wrap px-4 sm:px-5 lg:px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Sesi Arsip</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Daftar arsip yang pernah dibuat. Seluruh sesi terisolasi berdasarkan ID sesi arsip.</p>
                </div>

                {{-- Filter & Search Riwayat --}}
                <form method="GET" action="{{ route('admin.archives.index') }}" class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2 w-full md:w-auto min-w-0">
                    <select name="type" onchange="this.form.submit()" class="col-span-1 h-9 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-2.5 min-w-0">
                        <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>Semua Jenis</option>
                        <option value="attendance" {{ request('type') == 'attendance' ? 'selected' : '' }}>Presensi</option>
                        <option value="route" {{ request('type') == 'route' ? 'selected' : '' }}>Rencana Sales</option>
                        <option value="visit" {{ request('type') == 'visit' ? 'selected' : '' }}>Kunjungan Sales</option>
                        <option value="route_driver" {{ request('type') == 'route_driver' ? 'selected' : '' }}>Rencana Driver</option>
                        <option value="visit_driver" {{ request('type') == 'visit_driver' ? 'selected' : '' }}>Pengiriman Driver</option>
                        <option value="transaction" {{ request('type') == 'transaction' ? 'selected' : '' }}>Transaksi</option>
                        <option value="transaction_result" {{ request('type') == 'transaction_result' ? 'selected' : '' }}>Hasil Transaksi</option>
                        <option value="receivable" {{ request('type') == 'receivable' ? 'selected' : '' }}>Piutang</option>
                        <option value="finance" {{ request('type') == 'finance' ? 'selected' : '' }}>Keuangan</option>
                    </select>

                    <select name="status" onchange="this.form.submit()" class="col-span-1 h-9 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-2.5 min-w-0">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Diarsipkan</option>
                        <option value="restored" {{ request('status') == 'restored' ? 'selected' : '' }}>Dipulihkan</option>
                        <option value="purged" {{ request('status') == 'purged' ? 'selected' : '' }}>Dihapus Permanen</option>
                    </select>

                    <div class="relative col-span-2 sm:col-span-1 sm:w-44 min-w-0">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari arsip..." class="w-full h-9 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 pl-8 pr-3 min-w-0">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </form>
            </div>

            {{-- Mobile Card View (List Responsive Cards) --}}
            <div class="block md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($archives as $item)
                <div class="p-4 space-y-3 relative">
                    {{-- Header Card: Tanggal Dibuat & Status --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-bold text-gray-900 dark:text-white block truncate">
                                {{ $item->archived_at->isoFormat('D MMM YYYY, HH:mm') }}
                            </span>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                                Oleh: {{ $item->user->name ?? 'Super Admin' }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            @if($item->status === 'archived')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Diarsipkan
                            </span>
                            @elseif($item->status === 'restored')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Dipulihkan
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Dihapus
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- Detail Info Card --}}
                    <div class="bg-gray-50/75 dark:bg-gray-800/40 rounded-xl p-3 space-y-1.5 border border-gray-100 dark:border-gray-800/60 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium shrink-0">Jenis Data:</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200 truncate">{{ $item->data_type_label }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium shrink-0">Periode:</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300 text-[11px] text-right">{{ $item->start_date->isoFormat('D MMM YYYY') }} &ndash; {{ $item->end_date->isoFormat('D MMM YYYY') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-gray-200/60 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 font-medium shrink-0">Jumlah Data:</span>
                            <span class="font-extrabold text-[#0DA4CE]">{{ number_format($item->records_count, 0, ',', '.') }} data</span>
                        </div>
                        @if($item->notes)
                        <div class="pt-1 text-[11px] text-gray-400 dark:text-gray-500 italic">
                            Catatan: {{ $item->notes }}
                        </div>
                        @endif
                    </div>

                    {{-- Actions on Mobile --}}
                    <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                        @if($item->status === 'archived')
                        {{-- 1. Tombol Unduh Popover --}}
                        <div class="sm:relative" @click.outside="if (openDownloadId === '{{ $item->id }}') openDownloadId = null">
                            <button type="button" 
                                @click.stop="toggleDownloadMenu('{{ $item->id }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:text-[#0DA4CE] dark:hover:text-[#0DA4CE] border border-gray-200 dark:border-gray-700 shadow-2xs transition active:scale-95"
                                :class="openDownloadId === '{{ $item->id }}' ? 'border-[#0DA4CE] text-[#0DA4CE] dark:border-[#0DA4CE] dark:text-[#0DA4CE] ring-2 ring-[#0DA4CE]/15' : ''">
                                <svg class="w-3.5 h-3.5 text-[#0DA4CE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Unduh</span>
                                <svg class="w-3 h-3 text-gray-400 transition-transform duration-200 shrink-0" :class="openDownloadId === '{{ $item->id }}' ? 'rotate-180 text-[#0DA4CE]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            {{-- POPOVER MENU DROPDOWN (Mobile: viewport-centered via card, Desktop: right-aligned) --}}
                            <div x-show="openDownloadId === '{{ $item->id }}'" 
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 transform -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                x-cloak
                                class="absolute left-1/2 -translate-x-1/2 sm:left-auto sm:right-0 sm:translate-x-0 mt-2 z-50 w-[calc(100vw-1.5rem)] sm:w-[22rem] max-w-[calc(100vw-1.5rem)] sm:max-w-[22rem] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl overflow-hidden text-left ring-1 ring-black/5">
                                
                                <div class="px-4 py-3 bg-gray-50/80 dark:bg-gray-800/80 border-b border-gray-100 dark:border-gray-700/80 flex items-center justify-between">
                                    <div class="min-w-0 pr-2">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Unduh Dokumen</p>
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white truncate mt-0.5">{{ $item->data_type_label }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#0DA4CE]/10 text-[#0DA4CE] shrink-0">
                                        {{ number_format($item->records_count, 0, ',', '.') }} data
                                    </span>
                                </div>

                                @if($item->data_type === 'all')
                                <div class="p-3 space-y-2.5 max-h-[60vh] overflow-y-auto">
                                    {{-- Modul Presensi --}}
                                    <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Presensi Karyawan</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'attendance', 'Presensi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition">
                                                <span>PDF</span>
                                            </button>
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'attendance', 'Presensi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition">
                                                <span>Excel</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Modul Rencana Sales --}}
                                    <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Rencana Kunjungan Sales</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'route', 'Rencana Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition">
                                                <span>PDF</span>
                                            </button>
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'route', 'Rencana Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition">
                                                <span>Excel</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Modul Kunjungan Sales --}}
                                    <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Kunjungan Sales</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'visit', 'Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition">
                                                <span>PDF</span>
                                            </button>
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'visit', 'Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition">
                                                <span>Excel</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Modul Rencana Driver --}}
                                    <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Rencana Pengiriman Driver</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'route_driver', 'Rencana Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition">
                                                <span>PDF</span>
                                            </button>
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'route_driver', 'Rencana Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition">
                                                <span>Excel</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Modul Pengiriman Driver --}}
                                    <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Pengiriman Driver</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'visit_driver', 'Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition">
                                                <span>PDF</span>
                                            </button>
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'visit_driver', 'Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition">
                                                <span>Excel</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Modul Transaksi --}}
                                    <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Transaksi &amp; Pembayaran</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'transaction', 'Transaksi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition">
                                                <span>PDF</span>
                                            </button>
                                            <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'transaction', 'Transaksi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition">
                                                <span>Excel</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="p-2 space-y-1">
                                    {{-- Opsi PDF --}}
                                    <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', '{{ $item->data_type }}', '{{ $item->data_type_label }}', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                        class="w-full flex items-start gap-3 p-2.5 rounded-xl hover:bg-red-50/60 dark:hover:bg-red-950/30 transition group text-left">
                                        <div class="w-8 h-8 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-900 dark:text-white">Laporan PDF</span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">PDF</span>
                                            </div>
                                            <p class="text-[11px] text-gray-400 dark:text-gray-400 mt-0.5">Dokumen cetak / arsip</p>
                                        </div>
                                    </button>

                                    {{-- Opsi Excel --}}
                                    <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', '{{ $item->data_type }}', '{{ $item->data_type_label }}', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                        class="w-full flex items-start gap-3 p-2.5 rounded-xl hover:bg-emerald-50/60 dark:hover:bg-emerald-950/30 transition group text-left">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-900 dark:text-white">Spreadsheet Excel</span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">XLSX</span>
                                            </div>
                                            <p class="text-[11px] text-gray-400 dark:text-gray-400 mt-0.5">Data tabular analisis</p>
                                        </div>
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- 2. Tombol Restore (hanya saat status archived) --}}
                        <button @click="openRestoreModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}', {{ $item->records_count }})" 
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Pulihkan</span>
                        </button>

                        {{-- 3. Hapus Permanen (hanya saat status archived) --}}
                        <button @click="openPurgeModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}', {{ $item->records_count }})" 
                            class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 border border-red-200 dark:border-red-800/40 transition active:scale-95" 
                            title="Hapus Permanen">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        @elseif($item->status === 'restored')
                        {{-- Saat status restored / sudah dipulihkan: Tampilkan Hapus Riwayat --}}
                        <button @click="openDeleteHistoryModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}')" 
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 shadow-2xs transition active:scale-95" 
                            title="Hapus Riwayat">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus Riwayat</span>
                        </button>
                        @else
                        {{-- Status purged: hanya info / Hapus Riwayat --}}
                        <button @click="openDeleteHistoryModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}')" 
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 shadow-2xs transition active:scale-95" 
                            title="Hapus Riwayat">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus Riwayat</span>
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-4 py-10 text-center text-gray-500 dark:text-gray-400 text-sm">
                    <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    <p class="font-semibold text-gray-700 dark:text-gray-300">Belum Ada Riwayat Arsip</p>
                    <p class="text-xs text-gray-400 mt-1">Seluruh data operasional saat ini berada pada status aktif.</p>
                </div>
                @endforelse
            </div>

            {{-- Table (Desktop View) --}}
            <div class="hidden md:block overflow-visible min-h-[320px]">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 text-xs font-semibold uppercase tracking-wider border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-5 py-3.5">Tanggal Dibuat</th>
                            <th class="px-5 py-3.5">Jenis Data</th>
                            <th class="px-5 py-3.5">Periode Data</th>
                            <th class="px-5 py-3.5 text-center">Jumlah Data</th>
                            <th class="px-5 py-3.5">Diarsipkan Oleh</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($archives as $item)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                            <td class="px-5 py-4 text-xs font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $item->archived_at->isoFormat('D MMM YYYY, HH:mm') }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                    {{ $item->data_type_label }}
                                </span>
                                @if($item->notes)
                                <p class="text-[11px] text-gray-400 mt-1 truncate max-w-xs">{{ $item->notes }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs whitespace-nowrap">
                                {{ $item->start_date->isoFormat('D MMM YYYY') }} &ndash; {{ $item->end_date->isoFormat('D MMM YYYY') }}
                            </td>
                            <td class="px-5 py-4 text-center font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ number_format($item->records_count, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $item->user->name ?? 'Super Admin' }}
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if($item->status === 'archived')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Diarsipkan
                                </span>
                                @elseif($item->status === 'restored')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Dipulihkan (Aktif)
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Dihapus Permanen
                                </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- 1. TOMBOL UNDUH / MENU EXPORT --}}
                                    @if($item->status === 'archived')
                                    <div class="relative" @click.outside="if (openDownloadId === '{{ $item->id }}') openDownloadId = null">
                                        <button type="button" 
                                            @click.stop="toggleDownloadMenu('{{ $item->id }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:text-[#0DA4CE] dark:hover:text-[#0DA4CE] hover:bg-[#0DA4CE]/5 dark:hover:bg-[#0DA4CE]/10 border border-gray-200 dark:border-gray-700 hover:border-[#0DA4CE]/40 shadow-2xs transition active:scale-95"
                                            :class="openDownloadId === '{{ $item->id }}' ? 'border-[#0DA4CE] text-[#0DA4CE] dark:border-[#0DA4CE] dark:text-[#0DA4CE] ring-2 ring-[#0DA4CE]/15' : ''">
                                            <svg class="w-3.5 h-3.5 text-[#0DA4CE] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            <span>Unduh</span>
                                            <svg class="w-3 h-3 text-gray-400 transition-transform duration-200 shrink-0" :class="openDownloadId === '{{ $item->id }}' ? 'rotate-180 text-[#0DA4CE]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>

                                        {{-- POPOVER MENU DROPDOWN --}}
                                        <div x-show="openDownloadId === '{{ $item->id }}'" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 scale-95 transform -translate-y-1"
                                            x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95"
                                            x-cloak
                                            class="absolute right-0 mt-2 z-50 {{ $item->data_type === 'all' ? 'w-80 sm:w-96' : 'w-72 sm:w-80' }} bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl overflow-hidden text-left ring-1 ring-black/5">
                                            
                                            {{-- Header Popover --}}
                                            <div class="px-4 py-3 bg-gray-50/80 dark:bg-gray-800/80 border-b border-gray-100 dark:border-gray-700/80 flex items-center justify-between">
                                                <div>
                                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Unduh Dokumen Sesi Ini</p>
                                                    <p class="text-xs font-semibold text-gray-900 dark:text-white truncate mt-0.5">{{ $item->data_type_label }}</p>
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#0DA4CE]/10 text-[#0DA4CE]">
                                                    {{ number_format($item->records_count, 0, ',', '.') }} data
                                                </span>
                                            </div>

                                            @if($item->data_type === 'all')
                                            {{-- KONTEN KHUSUS DATA TYPE = ALL --}}
                                            <div class="p-3 space-y-2.5 max-h-[70vh] overflow-y-auto">
                                                {{-- 1. Modul Presensi --}}
                                                <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <div class="w-5 h-5 rounded-md bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        </div>
                                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Presensi Karyawan</span>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'attendance', 'Presensi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition shadow-2xs">
                                                            <span>PDF</span>
                                                        </button>
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'attendance', 'Presensi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition shadow-2xs">
                                                            <span>Excel</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- 2. Modul Rencana Sales --}}
                                                <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <div class="w-5 h-5 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                                        </div>
                                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Rencana Kunjungan (Sales)</span>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'route', 'Rencana Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition shadow-2xs">
                                                            <span>PDF</span>
                                                        </button>
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'route', 'Rencana Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition shadow-2xs">
                                                            <span>Excel</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- 3. Modul Kunjungan Sales --}}
                                                <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <div class="w-5 h-5 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                        </div>
                                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Kunjungan Sales</span>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'visit', 'Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition shadow-2xs">
                                                            <span>PDF</span>
                                                        </button>
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'visit', 'Kunjungan Sales', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition shadow-2xs">
                                                            <span>Excel</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- 4. Modul Rencana Driver --}}
                                                <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <div class="w-5 h-5 rounded-md bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 18h-2m-2.5-4h11.5m-11.5-4H20M5 6h12l4 5v5H5V6z"/></svg>
                                                        </div>
                                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Rencana Pengiriman (Driver)</span>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'route_driver', 'Rencana Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition shadow-2xs">
                                                            <span>PDF</span>
                                                        </button>
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'route_driver', 'Rencana Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition shadow-2xs">
                                                            <span>Excel</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- 5. Modul Pengiriman Driver --}}
                                                <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <div class="w-5 h-5 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h11v10H3zM14 10h4l3 3v3h-7zM7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                                        </div>
                                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Pengiriman (Driver)</span>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'visit_driver', 'Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition shadow-2xs">
                                                            <span>PDF</span>
                                                        </button>
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'visit_driver', 'Pengiriman Driver', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition shadow-2xs">
                                                            <span>Excel</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- 6. Modul Transaksi --}}
                                                <div class="bg-gray-50/50 dark:bg-gray-900/40 rounded-xl p-2.5 border border-gray-100 dark:border-gray-700/60">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <div class="w-5 h-5 rounded-md bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                        </div>
                                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200">Transaksi &amp; Pembayaran</span>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', 'transaction', 'Transaksi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 dark:text-red-400 hover:bg-red-50 transition shadow-2xs">
                                                            <span>PDF</span>
                                                        </button>
                                                        <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', 'transaction', 'Transaksi', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                            class="flex items-center justify-center gap-1.5 py-1.5 px-2.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 transition shadow-2xs">
                                                            <span>Excel</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @else
                                            {{-- KONTEN SINGLE DATA TYPE --}}
                                            <div class="p-2 space-y-1">
                                                {{-- Opsi PDF --}}
                                                <button type="button" @click="openDownloadModal('{{ $item->id }}', 'pdf', '{{ $item->data_type }}', '{{ $item->data_type_label }}', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                    class="w-full flex items-start gap-3 p-2.5 rounded-xl hover:bg-red-50/60 dark:hover:bg-red-950/30 transition group text-left">
                                                    <div class="w-8 h-8 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400">Laporan PDF</span>
                                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">PDF</span>
                                                        </div>
                                                        <p class="text-[11px] text-gray-400 dark:text-gray-400 mt-0.5 leading-snug">Dokumen resmi siap cetak / arsip</p>
                                                    </div>
                                                </button>

                                                {{-- Opsi Excel --}}
                                                <button type="button" @click="openDownloadModal('{{ $item->id }}', 'excel', '{{ $item->data_type }}', '{{ $item->data_type_label }}', '{{ $item->start_date->format('Y-m-d') }}', '{{ $item->end_date->format('Y-m-d') }}')"
                                                    class="w-full flex items-start gap-3 p-2.5 rounded-xl hover:bg-emerald-50/60 dark:hover:bg-emerald-950/30 transition group text-left">
                                                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Spreadsheet Excel</span>
                                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">XLSX</span>
                                                        </div>
                                                        <p class="text-[11px] text-gray-400 dark:text-gray-400 mt-0.5 leading-snug">Data tabular terstruktur untuk analisis</p>
                                                    </div>
                                                </button>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @else
                                    {{-- Tombol Disabled jika status bukan archived --}}
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-medium text-gray-400 dark:text-gray-600 bg-gray-50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-800 cursor-not-allowed opacity-60" title="Unduhan hanya tersedia saat data berstatus arsip">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        <span class="text-[11px]">Unduh</span>
                                    </span>
                                    @endif

                                    @if($item->status === 'archived')
                                    {{-- 2. Tombol Restore (hanya saat status archived) --}}
                                    <button @click="openRestoreModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}', {{ $item->records_count }})" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 shadow-2xs transition active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>Pulihkan</span>
                                    </button>

                                    {{-- 3. Tombol Hapus Permanen (hanya saat status archived) --}}
                                    <button @click="openPurgeModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}', {{ $item->records_count }})" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 border border-red-200 dark:border-red-800/40 transition active:scale-95" 
                                        title="Hapus Permanen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @elseif($item->status === 'restored')
                                    {{-- Saat status restored / sudah dipulihkan: Tampilkan Hapus Riwayat saja --}}
                                    <button @click="openDeleteHistoryModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}')" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 shadow-2xs transition active:scale-95" 
                                        title="Hapus Riwayat">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus Riwayat</span>
                                    </button>
                                    @else
                                    {{-- Status purged: hanya info / Hapus Riwayat --}}
                                    <button @click="openDeleteHistoryModal('{{ $item->id }}', '{{ $item->data_type_label }}', '{{ $item->start_date->isoFormat('D MMM YYYY') }} - {{ $item->end_date->isoFormat('D MMM YYYY') }}')" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 shadow-2xs transition active:scale-95" 
                                        title="Hapus Riwayat">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus Riwayat</span>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
                                <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-3 text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </div>
                                <p class="font-semibold text-gray-700 dark:text-gray-300">Belum Ada Riwayat Arsip</p>
                                <p class="text-xs text-gray-400 mt-1">Seluruh data operasional saat ini berada pada status aktif.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($archives->hasPages())
            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                {{ $archives->links() }}
            </div>
            @endif
        </div>

        {{-- 4. Zona Bahaya / Informasi Keamanan --}}
        <div class="rounded-2xl border border-red-200 dark:border-red-900/40 bg-red-50/50 dark:bg-red-950/20 p-5 lg:p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-red-900 dark:text-red-200">Keamanan Data &amp; Prinsip Integritas</h3>
                    <p class="text-xs text-red-700 dark:text-red-300/80 mt-1 leading-relaxed">
                        Data yang diarsipkan tidak hilang dan dapat dipulihkan kapan saja. Penghapusan permanen hanya diizinkan untuk sesi arsip tertentu setelah melalui konfirmasi ketat. Data master Toko dan Pengguna dilindungi dan tidak akan ikut terhapus.
                    </p>
                </div>
            </div>
        </div>

        {{-- Modal Pratinjau Arsip --}}
        <div x-show="previewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="previewModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="previewModal = false"></div>
                <div x-show="previewModal" x-transition.scale class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Konfirmasi Pengarsipan</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Pastikan data yang dipilih sudah sesuai.</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="bg-gray-50 dark:bg-gray-700/50 p-3.5 rounded-xl border border-gray-200 dark:border-gray-600 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Periode:</span>
                                <span class="font-bold text-gray-900 dark:text-white" x-text="previewData.period"></span>
                            </div>
                            <template x-if="form.data_type === 'all' || form.data_type === 'attendance'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Presensi Ditemukan:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.attendance || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'all' || form.data_type === 'route'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Rencana Kunjungan (Sales):</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.route || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'all' || form.data_type === 'visit'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Kunjungan (Sales):</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.visit || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'all' || form.data_type === 'route_driver'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Rencana Pengiriman (Driver):</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.route_driver || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'all' || form.data_type === 'visit_driver'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Pengiriman (Driver):</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.visit_driver || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'all' || form.data_type === 'transaction'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Transaksi Ledger:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.transaction || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'transaction_result'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Hasil Transaksi Kunjungan:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.transaction_result || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'receivable'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Transaksi Piutang:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.receivable || 0) + ' data'"></span>
                                </div>
                            </template>
                            <template x-if="form.data_type === 'finance'">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Data Transaksi Keuangan:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white" x-text="(previewData.counts?.finance || 0) + ' data'"></span>
                                </div>
                            </template>

                            <div class="pt-2 border-t border-gray-200 dark:border-gray-600 flex justify-between text-sm font-bold">
                                <span class="text-gray-900 dark:text-white">Total Akan Diarsipkan:</span>
                                <span class="text-amber-600 dark:text-amber-400" x-text="(previewData.total || 0) + ' data'"></span>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                            * Data yang diarsipkan tidak dihapus dan dapat dipulihkan kapan saja melalui menu ini.
                        </p>
                    </div>

                    <form action="{{ route('admin.archives.store') }}" method="POST" class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 sm:gap-3">
                        @csrf
                        <input type="hidden" name="data_type" :value="form.data_type">
                        <input type="hidden" name="start_date" :value="form.start_date">
                        <input type="hidden" name="end_date" :value="form.end_date">
                        <input type="hidden" name="notes" :value="form.notes">

                        <button type="button" @click="previewModal = false" class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition text-center">
                            Batal
                        </button>
                        <button type="submit" :disabled="previewData.total === 0" class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition shadow-sm disabled:opacity-50 text-center">
                            Ya, Lanjutkan Arsipkan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Restore --}}
        <div x-show="restoreModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="restoreModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="restoreModal = false"></div>
                <div x-show="restoreModal" x-transition.scale class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Pulihkan Data Arsip</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Kembalikan data arsip ke status aktif.</p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Anda akan memulihkan <strong x-text="activeArchive.count + ' data ' + activeArchive.type"></strong> periode <strong x-text="activeArchive.period"></strong>. Data akan kembali aktif pada sistem operasional.
                    </p>

                    <form :action="'{{ url('admin/archives') }}/' + activeArchive.id + '/restore'" method="POST" class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 sm:gap-3">
                        @csrf
                        <button type="button" @click="restoreModal = false" class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition text-center">
                            Batal
                        </button>
                        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm text-center">
                            Ya, Pulihkan Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Hapus Permanen --}}
        <div x-show="purgeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="purgeModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="purgeModal = false"></div>
                <div x-show="purgeModal" x-transition.scale class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-md w-full p-6 border border-red-200 dark:border-red-900/40">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-red-600 dark:text-red-400">Hapus Permanen Data Arsip</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Tindakan ini menghapus data fisik secara permanen.</p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Anda akan menghapus permanen <strong x-text="activeArchive.count + ' data ' + activeArchive.type"></strong> periode <strong x-text="activeArchive.period"></strong> dari database.
                    </p>

                    <form :action="'{{ url('admin/archives') }}/' + activeArchive.id + '/purge'" method="POST" class="space-y-4">
                        @csrf
                        @method('DELETE')
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1.5">Ketik <span class="font-mono font-bold text-red-600">HAPUS PERMANEN</span> untuk konfirmasi:</label>
                            <input type="text" name="confirmation" x-model="purgeConfirmText" placeholder="HAPUS PERMANEN" required class="w-full h-11 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-red-500 focus:border-red-500 transition px-3.5">
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 sm:gap-3 pt-2">
                            <button type="button" @click="purgeModal = false" class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition text-center">
                                Batal
                            </button>
                            <button type="submit" :disabled="purgeConfirmText !== 'HAPUS PERMANEN'" class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm disabled:opacity-40 text-center">
                                Hapus Permanen Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Hapus Riwayat Sesi Arsip --}}
        <div x-show="deleteHistoryModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="deleteHistoryModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="deleteHistoryModal = false"></div>
                <div x-show="deleteHistoryModal" x-transition.scale class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0 text-gray-600 dark:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Hapus Riwayat Sesi Arsip?</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Apakah Anda yakin ingin menghapus riwayat sesi arsip ini?</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        <strong x-text="activeArchive.type"></strong> periode <strong x-text="activeArchive.period"></strong> akan dihapus dari daftar riwayat. Data operasional yang sudah diarsipkan tidak akan ikut terhapus.
                    </p>
                    <form :action="'{{ url('admin/archives') }}/' + activeArchive.id + '/destroy-history'" method="POST" class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 sm:gap-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="deleteHistoryModal = false" class="w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">Batal</button>
                        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm">Hapus</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Nama File Unduhan --}}
        <div x-show="downloadModal.show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @keydown.escape.window="downloadModal.show = false">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="downloadModal.show" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="downloadModal.show = false"></div>
                
                <div x-show="downloadModal.show" x-transition.scale.origin.center class="relative bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl sm:my-8 sm:max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                :class="downloadModal.format === 'pdf' ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'">
                                <template x-if="downloadModal.format === 'pdf'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </template>
                                <template x-if="downloadModal.format === 'excel'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </template>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="downloadModal.format === 'pdf' ? 'Unduh Laporan PDF' : 'Unduh Laporan Excel'"></h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'Modul: ' + downloadModal.typeLabel"></p>
                            </div>
                        </div>
                        <button type="button" @click="downloadModal.show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="executeDownload()" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                                Nama File
                            </label>
                            <div class="relative">
                                <input type="text" 
                                    x-ref="filenameInput"
                                    x-model="downloadModal.filename" 
                                    @input="downloadModal.error = ''" 
                                    required
                                    placeholder="Masukkan nama file..." 
                                    class="w-full h-11 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-xs focus:ring-2 focus:ring-[#0DA4CE]/20 focus:border-[#0DA4CE] transition px-3.5 outline-none font-medium"
                                    :class="downloadModal.error ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''">
                            </div>
                            
                            <p x-show="downloadModal.error" x-cloak class="text-xs text-red-600 dark:text-red-400 mt-1.5 flex items-center gap-1 font-medium">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span x-text="downloadModal.error"></span>
                            </p>

                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">
                                Ekstensi <span class="font-bold text-gray-600 dark:text-gray-300" x-text="downloadModal.extension"></span> akan ditambahkan secara otomatis.
                            </p>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 pt-2">
                            <button type="button" @click="downloadModal.show = false" class="w-full sm:w-auto px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition text-center">
                                Batal
                            </button>
                            <button type="submit" 
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white shadow-sm transition active:scale-95"
                                :class="downloadModal.format === 'pdf' ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span x-text="downloadModal.format === 'pdf' ? 'Unduh PDF' : 'Unduh Excel'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function archiveManager() {
            return {
                loading: false,
                previewModal: false,
                restoreModal: false,
                purgeModal: false,
                purgeConfirmText: '',
                openDownloadId: null,
                toggleDownloadMenu(id) {
                    this.openDownloadId = this.openDownloadId === id ? null : id;
                },
                closeDownloadMenu() {
                    this.openDownloadId = null;
                },
                downloadModal: {
                    show: false,
                    archiveId: '',
                    format: 'pdf',
                    dataType: 'attendance',
                    typeLabel: 'Presensi',
                    filename: '',
                    error: '',
                    extension: '.pdf',
                },
                openDownloadModal(id, format, dataType, typeLabel, startDate, endDate) {
                    this.openDownloadId = null;
                    
                    let labelSlug = 'Data';
                    if (dataType === 'attendance') labelSlug = 'Presensi';
                    else if (dataType === 'route') labelSlug = 'Rencana_Kunjungan_Sales';
                    else if (dataType === 'route_driver') labelSlug = 'Rencana_Pengiriman_Driver';
                    else if (dataType === 'visit') labelSlug = 'Kunjungan_Sales';
                    else if (dataType === 'visit_driver') labelSlug = 'Pengiriman_Driver';
                    else if (dataType === 'transaction') labelSlug = 'Transaksi';
                    else if (dataType === 'transaction_result') labelSlug = 'Hasil_Transaksi';
                    else if (dataType === 'receivable') labelSlug = 'Piutang';
                    else if (dataType === 'finance') labelSlug = 'Keuangan';
                    else if (dataType === 'all') labelSlug = 'Operasional';

                    const defaultFilename = `Arsip_${labelSlug}_${startDate}_sampai_${endDate}`;

                    this.downloadModal = {
                        show: true,
                        archiveId: id,
                        format: format,
                        dataType: dataType,
                        typeLabel: typeLabel,
                        filename: defaultFilename,
                        error: '',
                        extension: format === 'pdf' ? '.pdf' : '.xlsx',
                    };

                    this.$nextTick(() => {
                        if (this.$refs.filenameInput) {
                            this.$refs.filenameInput.focus();
                            this.$refs.filenameInput.select();
                        }
                    });
                },
                executeDownload() {
                    const rawName = this.downloadModal.filename.trim();
                    if (!rawName) {
                        this.downloadModal.error = 'Nama file wajib diisi.';
                        return;
                    }

                    const cleanName = rawName.replace(/[\\/:*?"<>|\r\n]+/g, '_').trim();
                    if (!cleanName) {
                        this.downloadModal.error = 'Nama file tidak valid.';
                        return;
                    }

                    const id = this.downloadModal.archiveId;
                    const format = this.downloadModal.format;
                    const dataType = this.downloadModal.dataType;
                    const action = format === 'pdf' ? 'export-pdf' : 'export-excel';

                    const url = `{{ url('admin/archives') }}/${id}/${action}?type=${dataType}&filename=${encodeURIComponent(cleanName)}`;

                    this.downloadModal.show = false;
                    window.location.href = url;
                },
                form: {
                    data_type: 'all',
                    start_date: '',
                    end_date: '',
                    notes: '',
                },
                previewData: {
                    total: 0,
                    period: '',
                    counts: {}
                },
                activeArchive: {
                    id: '',
                    type: '',
                    period: '',
                    count: 0
                },
                resetForm() {
                    this.form.data_type = 'all';
                    this.form.start_date = '';
                    this.form.end_date = '';
                    this.form.notes = '';
                },
                async checkPreview() {
                    if (!this.form.start_date || !this.form.end_date) {
                        alert('Silakan pilih tanggal mulai dan tanggal selesai.');
                        return;
                    }
                    this.loading = true;
                    try {
                        const response = await axios.post('{{ route('admin.archives.preview') }}', this.form);
                        if (response.data.status === 'success') {
                            this.previewData = response.data;
                            this.previewModal = true;
                        }
                    } catch (error) {
                        alert(error.response?.data?.message || 'Gagal memeriksa pratinjau data arsip.');
                    } finally {
                        this.loading = false;
                    }
                },
                openRestoreModal(id, type, period, count) {
                    this.activeArchive = { id, type, period, count };
                    this.restoreModal = true;
                },
                openPurgeModal(id, type, period, count) {
                    this.activeArchive = { id, type, period, count };
                    this.purgeConfirmText = '';
                    this.purgeModal = true;
                },
                deleteHistoryModal: false,
                openDeleteHistoryModal(id, type, period) {
                    this.activeArchive = { id, type, period, count: 0 };
                    this.deleteHistoryModal = true;
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
