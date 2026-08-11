<script setup>
import { ref } from 'vue';

const props = defineProps({
    endpoint: { type: String, required: true },
    count: { type: Number, default: 0 },
});

const total = ref(props.count);
const liked = ref(false);
const busy = ref(false);

async function toggle() {
    if (busy.value) return;

    busy.value = true;

    // Optimistic, then corrected by the server's authoritative count.
    liked.value = !liked.value;
    total.value += liked.value ? 1 : -1;

    try {
        const response = await fetch(props.endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        });

        if (!response.ok) throw new Error();

        const payload = await response.json();
        liked.value = payload.liked;
        total.value = payload.count;
    } catch {
        liked.value = !liked.value;
        total.value += liked.value ? 1 : -1;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition"
        :class="liked
            ? 'border-brand-300 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/15 dark:text-brand-400'
            : 'border-gray-200 text-gray-500 hover:border-brand-300 hover:text-brand-600 dark:border-gray-800'"
        :aria-pressed="liked"
        :disabled="busy"
        @click="toggle"
    >
        <svg class="size-4" viewBox="0 0 24 24" :fill="liked ? 'currentColor' : 'none'" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
        </svg>
        {{ total.toLocaleString() }}
        <span class="sr-only">likes</span>
    </button>
</template>
