@props(['name', 'checked' => false, 'label' => null, 'hint' => null])

<label class="flex cursor-pointer items-start gap-3" x-data="{ on: {{ $checked ? 'true' : 'false' }} }">
    {{-- Paired hidden input so an "off" toggle still posts a value. --}}
    <input type="hidden" name="{{ $name }}" value="0">
    <input
        type="checkbox"
        name="{{ $name }}"
        value="1"
        class="peer sr-only"
        x-model="on"
        {{ $checked ? 'checked' : '' }}
        {{ $attributes }}
    >

    <span
        class="relative mt-0.5 inline-flex h-5 w-9 shrink-0 rounded-full transition
               peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40 peer-focus-visible:ring-offset-2"
        :class="on ? 'bg-brand-600' : 'bg-gray-300 dark:bg-gray-700'"
        aria-hidden="true"
    >
        <span
            class="absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow-sm transition-transform"
            :class="on && 'translate-x-4'"
        ></span>
    </span>

    @if($label)
        <span class="min-w-0">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
            @if($hint)
                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</span>
            @endif
        </span>
    @endif
</label>
