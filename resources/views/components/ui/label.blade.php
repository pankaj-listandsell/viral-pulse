@props(['for' => null, 'required' => false])

<label
    @if($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300']) }}
>
    {{ $slot }}
    @if($required)
        <span class="text-red-500" aria-hidden="true">*</span>
    @endif
</label>
