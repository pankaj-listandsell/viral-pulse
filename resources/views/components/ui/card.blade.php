@props(['title' => null, 'subtitle' => null, 'padded' => true])

<div {{ $attributes->merge([
    'class' => 'rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900',
]) }}>
    @if($title || isset($actions))
        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div>
                @if($title)
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padded ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</div>
