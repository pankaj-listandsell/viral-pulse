@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Are you sure?',
    'message' => 'This cannot be undone.',
    'confirmLabel' => 'Delete',
    'variant' => 'danger',
])

{{--
    A destructive action always goes through a modal: the form only submits
    after an explicit confirmation, so nothing is lost to a stray click.
--}}
<form
    method="POST"
    action="{{ $action }}"
    x-data="{ open: false }"
    x-ref="form"
    @submit.prevent="open = true"
    class="inline"
>
    @csrf
    @method($method)

    {{ $trigger }}

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-50 grid place-items-center bg-gray-900/50 p-4 backdrop-blur-xs"
            x-cloak
            @keydown.escape.window="open = false"
        >
            <div
                class="w-full max-w-sm rounded-xl border border-gray-200 bg-white p-5 shadow-xl
                       dark:border-gray-800 dark:bg-gray-900"
                @click.outside="open = false"
                role="alertdialog"
                aria-modal="true"
            >
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <x-ui.button variant="secondary" size="sm" @click="open = false" type="button">
                        Cancel
                    </x-ui.button>
                    <x-ui.button :variant="$variant" size="sm" type="button" @click="$refs.form.submit()">
                        {{ $confirmLabel }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </template>
</form>
