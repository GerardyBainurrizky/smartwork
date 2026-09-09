@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
        {{-- Mobile: info + Sebelumnya / Selanjutnya --}}
        <div class="flex flex-col gap-2 w-full sm:hidden">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                @if ($paginator->firstItem())
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->firstItem() }}</span>
                    - <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->lastItem() }}</span>
                @else
                    <span class="font-semibold text-gray-900 dark:text-white">0</span>
                @endif
                dari <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->total() }}</span> data
            </p>
            <div class="flex items-center justify-between w-full">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl cursor-default">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Sebelumnya
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-[#0DA4CE] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Sebelumnya
                    </a>
                @endif

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold text-[#0DA4CE] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition">
                        Selanjutnya
                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl cursor-default">
                        Selanjutnya
                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
        </div>

        {{-- Desktop: info + nomor halaman --}}
        <div class="hidden sm:flex sm:items-center gap-4 flex-wrap">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->firstItem() }}</span>
                - <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->lastItem() }}</span>
                dari <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->total() }}</span> data
            </p>
        </div>

        <div class="hidden sm:flex sm:items-center gap-1.5 flex-wrap justify-end">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center px-3 h-9 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg cursor-default">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    class="inline-flex items-center justify-center px-3 h-9 text-sm font-semibold text-[#0DA4CE] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Sebelumnya
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1.5 text-sm text-gray-400 dark:text-gray-500">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="inline-flex items-center justify-center w-9 h-9 text-sm font-bold text-white bg-[#0DA4CE] rounded-lg shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                                class="inline-flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-[#0DA4CE]/5 hover:text-[#0DA4CE] hover:border-[#0DA4CE]/40 transition">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    class="inline-flex items-center justify-center px-3 h-9 text-sm font-semibold text-[#0DA4CE] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition">
                    Selanjutnya
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="inline-flex items-center justify-center px-3 h-9 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg cursor-default">
                    Selanjutnya
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
