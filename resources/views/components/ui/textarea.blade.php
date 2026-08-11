@props(['rows' => 3, 'invalid' => false])

<textarea
    rows="{{ $rows }}"
    {{ $attributes->merge([
        'class' => 'block w-full rounded-lg border px-3 py-2 text-sm shadow-xs transition
                    bg-white text-gray-900 placeholder:text-gray-400
                    dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500
                    focus:outline-none focus:ring-2 focus:ring-brand-500/40
                    ' . ($invalid
                        ? 'border-red-400 focus:border-red-500 dark:border-red-500/60'
                        : 'border-gray-300 focus:border-brand-500 dark:border-gray-700'),
    ]) }}
>{{ $slot }}</textarea>
