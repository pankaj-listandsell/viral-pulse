<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    pending: { type: Array, default: () => [] },
});

const watching = ref([...props.pending]);
let timer = null;

/**
 * Polls only the generations that were still running when the page rendered.
 * Once every one of them finishes, the timer stops — there is nothing left to
 * ask about, and an idle admin tab should not keep hitting the server.
 */
async function poll() {
    const stillRunning = [];

    for (const item of watching.value) {
        try {
            const response = await fetch(item.url, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                stillRunning.push(item);
                continue;
            }

            const state = await response.json();
            paint(state);

            if (!state.finished) {
                stillRunning.push(item);
            } else {
                // The row's actions change once it finishes, and those are
                // rendered server-side — reload rather than rebuild them here.
                setTimeout(() => window.location.reload(), 800);
            }
        } catch {
            stillRunning.push(item);
        }
    }

    watching.value = stillRunning;

    if (watching.value.length === 0 && timer) {
        clearInterval(timer);
        timer = null;
    }
}

function paint(state) {
    const cell = document
        .querySelector(`[data-generation="${state.id}"]`)
        ?.querySelector('[data-status-cell]');

    if (!cell) return;

    const tone = state.status === 'completed'
        ? 'bg-green-100 text-green-700 dark:bg-green-500/15 dark:text-green-400'
        : state.status === 'failed'
            ? 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400'
            : 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400';

    cell.innerHTML = `<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${tone}">${state.label}</span>`;
}

onMounted(() => {
    if (!watching.value.length) return;
    poll();
    timer = setInterval(poll, 4000);
});

onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <div v-if="watching.length" class="mb-3 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        Generating {{ watching.length }} {{ watching.length === 1 ? 'article' : 'articles' }}…
    </div>
</template>
