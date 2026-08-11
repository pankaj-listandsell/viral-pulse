@props([])

<input
    type="checkbox"
    {{ $attributes->merge([
        'class' => 'size-4 rounded border-gray-300 text-brand-600 shadow-xs
                    focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-0
                    dark:border-gray-600 dark:bg-gray-800',
    ]) }}
>
