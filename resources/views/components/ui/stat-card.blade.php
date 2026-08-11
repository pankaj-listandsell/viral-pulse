@props(['label', 'value', 'icon' => null, 'color' => 'brand', 'href' => null, 'hint' => null])

@php
    $tints = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'green' => 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-400',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400',
        'gray' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-xs transition
           dark:border-gray-800 dark:bg-gray-900
           {{ $href ? 'hover:border-brand-300 hover:shadow-sm dark:hover:border-brand-500/40' : '' }}"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">
                {{ $label }}
            </p>
            <p class="mt-2 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-50">
                {{ is_numeric($value) ? number_format($value) : $value }}
            </p>
            @if($hint)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            @endif
        </div>

        @if($icon)
            <span class="grid size-9 shrink-0 place-items-center rounded-lg {{ $tints[$color] ?? $tints['brand'] }}">
                <x-icon :name="$icon" class="size-5" />
            </span>
        @endif
    </div>
</{{ $tag }}>
