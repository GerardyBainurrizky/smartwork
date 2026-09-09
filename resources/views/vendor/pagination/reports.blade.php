@php
    $total = $paginator->total();
    $first = $paginator->firstItem();
    $last = $paginator->lastItem();
    $current = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
@endphp

<nav role="navigation" aria-label="Pagination" class="mt-6">
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm px-4 sm:px-5 py-3.5">

        {{-- Mobile: info on top, compact buttons below --}}
        <div class="sm:hidden flex flex-col gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                Menampilkan
                @if($total > 0)
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $first }}</span>
                    - <span class="font-semibold text-gray-900 dark:text-white">{{ $last }}</span>
                @else
                    <span class="font-semibold text-gray-900 dark:text-white">0</span>
                @endif
                dari <span class="font-semibold text-gray-900 dark:text-white">{{ $total }}</span> data
            </p>
            <div class="flex items-center justify-center gap-2">
                @if($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center h-10 px-4 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/60 rounded-xl cursor-default">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Sebelumnya
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-[#0DA4CE] dark:text-[#3bc0e6] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition active:scale-95">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Sebelumnya
                    </a>
                @endif

                <span class="inline-flex items-center justify-center h-10 px-3 text-sm font-bold text-white bg-[#0DA4CE] rounded-xl shadow-sm shadow-[#0DA4CE]/30">{{ $current }}</span>

                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                        class="inline-flex items-center justify-center h-10 px-4 text-sm font-semibold text-[#0DA4CE] dark:text-[#3bc0e6] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition active:scale-95">
                        Selanjutnya
                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="inline-flex items-center justify-center h-10 px-4 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/60 rounded-xl cursor-default">
                        Selanjutnya
                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
        </div>

        {{-- Desktop: info left, numbered controls right --}}
        <div class="hidden sm:flex sm:items-center sm:justify-between gap-x-4 gap-y-3 flex-wrap">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                @if($total > 0)
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $first }}</span>
                    - <span class="font-semibold text-gray-900 dark:text-white">{{ $last }}</span>
                @else
                    <span class="font-semibold text-gray-900 dark:text-white">0</span>
                @endif
                dari <span class="font-semibold text-gray-900 dark:text-white">{{ $total }}</span> data
            </p>

            <div class="flex items-center gap-1.5">
                @if($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center h-9 px-3.5 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/60 rounded-xl cursor-default">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Sebelumnya
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        class="inline-flex items-center justify-center h-9 px-3.5 text-sm font-semibold text-[#0DA4CE] dark:text-[#3bc0e6] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition active:scale-95">
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
                            @if ($page == $current)
                                <span aria-current="page"
                                    class="inline-flex items-center justify-center w-9 h-9 text-sm font-bold text-white bg-[#0DA4CE] rounded-xl shadow-sm shadow-[#0DA4CE]/30 ring-2 ring-[#0DA4CE]/25">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                    class="inline-flex items-center justify-center w-9 h-9 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-[#0DA4CE]/5 hover:text-[#0DA4CE] hover:border-[#0DA4CE]/40 transition">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                        class="inline-flex items-center justify-center h-9 px-3.5 text-sm font-semibold text-[#0DA4CE] dark:text-[#3bc0e6] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:bg-[#0DA4CE]/5 hover:border-[#0DA4CE]/40 transition active:scale-95">
                        Selanjutnya
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="inline-flex items-center justify-center h-9 px-3.5 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/60 rounded-xl cursor-default">
                        Selanjutnya
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
        </div>
    </div>
</nav>