<script setup>
import { ref } from 'vue';

const props = defineProps({
    action: { type: String, required: true },
    value: { type: String, default: '' },
});

const term = ref(props.value ?? '');
const focused = ref(false);
const input = ref(null);

/** "/" focuses search, the way most content sites behave. */
function onKeydown(event) {
    if (event.key === '/' && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
        event.preventDefault();
        input.value?.focus();
    }
}

if (typeof window !== 'undefined') {
    window.addEventListener('keydown', onKeydown);
}
</script>

<template>
    <form :action="action" role="search" class="relative">
        <label for="search-island" class="sr-only">Search</label>

        <svg class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-gray-400"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" />
        </svg>

        <input
            id="search-island"
            ref="input"
            v-model="term"
            type="search"
            name="q"
            placeholder="Search"
            class="w-44 rounded-lg border border-gray-300 py-1.5 pr-8 pl-8 text-sm transition focus:w-56 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900"
            @focus="focused = true"
            @blur="focused = false"
        >

        <kbd
            v-show="!focused && !term"
            class="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 rounded border border-gray-300 px-1 text-[0.65rem] text-gray-400 dark:border-gray-700"
        >/</kbd>
    </form>
</template>
