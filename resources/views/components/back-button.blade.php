@props(['href' => '#', 'label' => 'Kembali'])

@php
    $backButtonClass = [
        'inline-flex', 'items-center', 'gap-2',
        'rounded-xl',
        'border', 'border-gray-200', 'dark:border-gray-700/60',
        'bg-white', 'dark:bg-gray-800',
        'px-3.5', 'py-2',
        'text-sm', 'font-medium',
        'text-gray-600', 'dark:text-gray-300',
        'shadow-sm', 'transition',
        'hover:border-gray-300', 'dark:hover:border-gray-600',
        'hover:bg-gray-50', 'dark:hover:bg-gray-700/50',
        'hover:text-gray-900', 'dark:hover:text-gray-100',
        'focus-visible:outline-none',
        'focus-visible:ring-2', 'focus-visible:ring-[#0DA4CE]/40',
        'focus-visible:border-[#0DA4CE]',
    ];
@endphp

<a href="{{ $href }}" {{ $attributes->class($backButtonClass) }}>
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
    </svg>
    {{ $label }}
</a>