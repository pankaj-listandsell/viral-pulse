{{--
    Flash messages are handed to the Alpine store once on load, then rendered
    from there so dismissals and follow-up toasts share one queue.
--}}
<div
    x-data
    x-init="
        @foreach (['success', 'error', 'warning', 'info'] as $type)
            @if (session($type))
                $store.toasts.push('{{ $type }}', @js(session($type)));
            @endif
        @endforeach
        @if (session('status'))
            $store.toasts.push('info', @js(session('status')));
        @endif
    "
    class="pointer-events-none fixed inset-x-0 top-4 z-100 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
    role="status"
    aria-live="polite"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-transition.opacity.duration.200ms
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border p-3 shadow-lg backdrop-blur"
            :class="{
                'border-green-200 bg-green-50/95 text-green-900 dark:border-green-500/30 dark:bg-green-950/90 dark:text-green-200': toast.type === 'success',
                'border-red-200 bg-red-50/95 text-red-900 dark:border-red-500/30 dark:bg-red-950/90 dark:text-red-200': toast.type === 'error',
                'border-amber-200 bg-amber-50/95 text-amber-900 dark:border-amber-500/30 dark:bg-amber-950/90 dark:text-amber-200': toast.type === 'warning',
                'border-gray-200 bg-white/95 text-gray-900 dark:border-gray-700 dark:bg-gray-900/95 dark:text-gray-100': toast.type === 'info',
            }"
        >
            <p class="flex-1 text-sm" x-text="toast.message"></p>

            <button
                type="button"
                class="shrink-0 rounded p-0.5 opacity-60 transition hover:opacity-100"
                @click="$store.toasts.dismiss(toast.id)"
                aria-label="Dismiss"
            >
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
