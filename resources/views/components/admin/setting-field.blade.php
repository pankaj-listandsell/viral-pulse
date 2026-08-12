@props(['field', 'value' => null])

@php
    $key = $field['key'];
    $id = 'setting-' . $key;
    $help = $field['help'] ?? null;
    $current = old($key, $value);
@endphp

<div class="py-4 first:pt-0 last:pb-0">
    @if($field['input'] === 'boolean')
        <x-ui.toggle :name="$key" :checked="(bool) $current" :label="$field['label']" :hint="$help" />
    @else
        <x-ui.label :for="$id">{{ $field['label'] }}</x-ui.label>

        @switch($field['input'])
            @case('textarea')
                <x-ui.textarea :id="$id" :name="$key" rows="4" :invalid="$errors->has($key)">{{ $current }}</x-ui.textarea>
                @break

            @case('select')
                <x-ui.select :id="$id" :name="$key" :invalid="$errors->has($key)">
                    @foreach($field['options'] as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </x-ui.select>
                @break

            @case('timezone')
                <x-ui.select :id="$id" :name="$key" :invalid="$errors->has($key)">
                    @foreach(timezone_identifiers_list() as $zone)
                        <option value="{{ $zone }}" @selected($current === $zone)>{{ $zone }}</option>
                    @endforeach
                </x-ui.select>
                @break

            @case('image')
                @if($current)
                    <div class="mb-2 flex items-center gap-3">
                        <img
                            src="{{ Storage::disk(config('site.media.disk'))->url($current) }}"
                            alt="{{ $field['label'] }}"
                            class="h-12 w-auto max-w-[10rem] rounded-md border border-gray-200 bg-white object-contain p-1 dark:border-gray-700"
                        >
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <input type="checkbox" name="remove_{{ $key }}" value="1"
                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                            Remove
                        </label>
                    </div>
                @endif

                <input
                    type="file"
                    id="{{ $id }}"
                    name="{{ $key }}"
                    accept="image/*"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100
                           file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200
                           dark:text-gray-400 dark:file:bg-gray-800 dark:hover:file:bg-gray-700"
                >
                @break

            @default
                <x-ui.input
                    :id="$id"
                    :name="$key"
                    :type="$field['input'] === 'number' ? 'number' : ($field['input'] === 'email' ? 'email' : ($field['input'] === 'url' ? 'url' : 'text'))"
                    :value="$current"
                    :invalid="$errors->has($key)"
                />
        @endswitch

        @if($help)
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>
        @endif
    @endif

    <x-ui.error :for="$key" />
</div>
