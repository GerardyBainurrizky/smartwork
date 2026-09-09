@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="w-full">
        <div class="flex items-center justify-center gap-1.5 flex-wrap">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700/50 rounded-lg cursor-not-allowed">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:border-[#0DA4CE]/40 transition-colors">Sebelumnya</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="px-2 py-1 text-xs text-gray-400 dark:text-gray-500">&hellip;</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex items-center justify-center w-8 h-8 text-xs font-semibold text-white bg-[#0DA4CE] rounded-lg">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:border-[#0DA4CE]/40 transition-colors">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/40 hover:border-[#0DA4CE]/40 transition-colors">Berikutnya</a>
            @else
                <span aria-disabled="true" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700/50 rounded-lg cursor-not-allowed">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif