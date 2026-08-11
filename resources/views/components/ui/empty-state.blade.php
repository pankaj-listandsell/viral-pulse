@props(['title' => 'Nothing here yet', 'description' => null, 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
        <x-icon :name="$icon" class="size-6" />
    </span>

    <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>

    @if($description)
        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
