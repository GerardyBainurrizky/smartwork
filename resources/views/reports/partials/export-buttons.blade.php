@php
    $exportType = $exportType ?? 'attendance';
    $suggestedFilename = $exportFilename ?? '';
    $placeholder = $exportPlaceholder ?? ($suggestedFilename !== '' ? $suggestedFilename : 'Laporan');
    $exportPdfUrl = route('admin.reports.export-pdf', array_merge(request()->query(), ['type' => $exportType]));
    $exportExcelUrl = route('admin.reports.export-excel', array_merge(request()->query(), ['type' => $exportType]));
@endphp

<div class="flex flex-col sm:flex-row sm:items-center gap-4 w-full sm:w-auto" x-data="reportDownload()" @keydown.escape.window="if (status !== 'loading') show = false">
    <button @click="openPdf()" class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-3 min-h-[3rem] bg-red-50 dark:bg-red-950 text-red-600 dark:text-red-300 rounded-xl text-sm font-semibold hover:bg-red-100 dark:hover:bg-red-900 transition">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        Unduh PDF
    </button>
    <button @click="openExcel()" class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-3 min-h-[3rem] bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-300 rounded-xl text-sm font-semibold hover:bg-green-100 dark:hover:bg-green-900 transition">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Unduh Excel
    </button>

    {{-- Modal Nama File Laporan --}}
    <div x-show="show" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="if (status !== 'loading') show = false"></div>
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-md p-6" @click.stop>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Nama File Laporan</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">File akan diunduh sebagai <span class="font-semibold" x-text="extension"></span></p>
                </div>
                <button type="button" @click="if (status !== 'loading') show = false" :disabled="status === 'loading'" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nama File</label>
                <input type="text" x-model="filename" @input="error = ''" :disabled="status === 'loading' || status === 'success'"
                    class="w-full border-2 border-gray-300 dark:border-gray-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 caret-[#0DA4CE] focus:outline-none focus:border-[#0DA4CE] focus:ring-2 focus:ring-[#0DA4CE]/30 transition disabled:opacity-60 disabled:cursor-not-allowed"
                    placeholder="{{ $placeholder }}">
            </div>

            {{-- Validation / Error Message --}}
            <p x-show="error" x-cloak x-transition class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400 mt-2 font-medium">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="error"></span>
            </p>

            {{-- Loading State --}}
            <div x-show="status === 'loading'" x-cloak x-transition class="mt-4 p-3 bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/60 rounded-xl flex items-center gap-3">
                <svg class="animate-spin h-5 w-5 text-[#0DA4CE] shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <div class="text-xs">
                    <p class="font-semibold text-cyan-900 dark:text-cyan-200">Sedang menyiapkan file...</p>
                    <p class="text-cyan-700 dark:text-cyan-400 text-[11px] mt-0.5">Mohon tunggu hingga file selesai diunduh.</p>
                </div>
            </div>

            {{-- Success State --}}
            <div x-show="status === 'success'" x-cloak x-transition class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl flex items-center gap-3">
                <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="text-xs">
                    <p class="font-semibold text-emerald-900 dark:text-emerald-200">Laporan berhasil diunduh.</p>
                    <p class="text-emerald-700 dark:text-emerald-400 text-[11px] mt-0.5">Modal akan segera ditutup otomatis.</p>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="show = false" :disabled="status === 'loading'" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                    Batal
                </button>
                <button type="button" @click="download()" :disabled="status === 'loading' || status === 'success'" class="inline-flex items-center justify-center px-4 py-2.5 bg-[#0DA4CE] text-white rounded-xl text-sm font-semibold hover:bg-[#097A99] transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="status === 'loading'">
                        <span class="inline-flex items-center">
                            <svg class="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Mengunduh...
                        </span>
                    </template>
                    <template x-if="status === 'error'">
                        <span>Coba Lagi</span>
                    </template>
                    <template x-if="status !== 'loading' && status !== 'error'">
                        <span>Unduh</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function reportDownload() {
        return {
            show: false,
            status: 'idle', // 'idle' | 'loading' | 'success' | 'error'
            filename: @js($suggestedFilename),
            placeholder: @js($placeholder),
            error: '',
            extension: '',
            pdfUrl: @js($exportPdfUrl),
            excelUrl: @js($exportExcelUrl),
            currentUrl: '',
            openPdf() {
                this.extension = '.pdf';
                this.currentUrl = this.pdfUrl;
                this.error = '';
                this.status = 'idle';
                this.show = true;
            },
            openExcel() {
                this.extension = '.xlsx';
                this.currentUrl = this.excelUrl;
                this.error = '';
                this.status = 'idle';
                this.show = true;
            },
            async download() {
                if (this.status === 'loading') {
                    return;
                }

                const name = this.filename.trim();

                if (name === '') {
                    this.error = 'Silakan masukkan nama file terlebih dahulu.';
                    return;
                }

                this.error = '';
                this.status = 'loading';

                const sep = this.currentUrl.includes('?') ? '&' : '?';
                const finalFilename = name.endsWith(this.extension) ? name.slice(0, -this.extension.length) : name;
                const targetUrl = this.currentUrl + sep + 'filename=' + encodeURIComponent(finalFilename);

                try {
                    const response = await fetch(targetUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': '*/*',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Gagal mengunduh laporan (Status: ' + response.status + ').');
                    }

                    const blob = await response.blob();
                    const objectUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.style.display = 'none';
                    link.href = objectUrl;
                    link.download = finalFilename + this.extension;
                    document.body.appendChild(link);
                    link.click();

                    setTimeout(() => {
                        window.URL.revokeObjectURL(objectUrl);
                        link.remove();
                    }, 1000);

                    this.status = 'success';

                    setTimeout(() => {
                        this.show = false;
                        this.status = 'idle';
                    }, 1200);
                } catch (err) {
                    this.status = 'error';
                    this.error = err.message || 'Terjadi kesalahan saat mengunduh laporan. Silakan coba lagi.';
                }
            },
        };
    }
</script>