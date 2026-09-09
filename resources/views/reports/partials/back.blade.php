<a href="{{ route('admin.reports.index') }}"
    onclick="if (sessionStorage.getItem('admin_reports_last_card')) { this.href = '{{ route('admin.reports.index') }}#' + sessionStorage.getItem('admin_reports_last_card'); }"
    class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Kembali ke Daftar Laporan
</a>
