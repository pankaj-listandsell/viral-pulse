@props(['label', 'value', 'icon' => null, 'color' => 'brand', 'href' => null, 'hint' => null])

@php
    /**
     * The colour is the fastest thing to read on a wall of numbers, so it sits
     * in a bar across the top rather than only inside a small icon tile: the
     * cards become scannable in peripheral vision instead of one at a time.
     */
    $accents = [
        'brand' => ['bar' => 'bg-brand-500', 'tint' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400'],
        'green' => ['bar' => 'bg-emerald-500', 'tint' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400'],
        'amber' => ['bar' => 'bg-amber-500', 'tint' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400'],
        'blue' => ['bar' => 'bg-blue-500', 'tint' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400'],
        'violet' => ['bar' => 'bg-violet-500', 'tint' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400'],
        'gray' => ['bar' => 'bg-gray-300 dark:bg-gray-700', 'tint' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'],
    ];
    $accent = $accents[$color] ?? $accents['brand'];
    $tag = $href ? 'a' : 'div';

    // A zero is not news. Muting it keeps "Drafts 0" from shouting as loudly as
    // the numbers that actually moved.
    $isZero = is_numeric($value) && (int) $value === 0;
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition duration-200 dark:border-gray-800 dark:bg-gray-900 '
        .($href ? 'hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md dark:hover:border-gray-700' : '')]) }}
>
    <span class="absolute inset-x-0 top-0 h-1 {{ $accent['bar'] }} {{ $isZero ? 'opacity-30' : '' }}" aria-hidden="true"></span>

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ $label }}
            </p>

            <p @class([
                'mt-2 text-3xl font-black tabular-nums tracking-tight',
                'text-gray-900 dark:text-gray-50' => ! $isZero,
                'text-gray-300 dark:text-gray-700' => $isZero,
            ])>
                {{ is_numeric($value) ? number_format($value) : $value }}
            </p>

            @if($hint)
                <p class="mt-1.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            @endif
        </div>

        @if($icon)
            <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $accent['tint'] }} transition group-hover:scale-105">
                <x-icon :name="$icon" class="size-5" />
            </span>
        @endif
    </div>
</{{ $tag }}>
