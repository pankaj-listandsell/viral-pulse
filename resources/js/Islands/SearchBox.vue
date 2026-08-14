<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    action: { type: String, required: true },
    value: { type: String, default: '' },
});

const term = ref(props.value ?? '');
const focused = ref(false);
const input = ref(null);
const results = ref([]);
const loading = ref(false);
const isOpen = ref(false);

let debounceTimer = null;

async function fetchLiveResults() {
    const q = term.value.trim();
    if (q.length < 2) {
        results.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    try {
        const res = await fetch(`/search/live?q=${encodeURIComponent(q)}`);
        if (res.ok) {
            results.value = await res.json();
            isOpen.value = true;
        }
    } catch {
        results.value = [];
    } finally {
        loading.value = false;
    }
}

watch(term, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchLiveResults, 250);
});

function onFocus() {
    focused.value = true;
    if (results.value.length > 0 && term.value.trim().length >= 2) {
        isOpen.value = true;
    }
}

function onBlur() {
    focused.value = false;
    // Delay closing so clicks on links can register
    setTimeout(() => {
        isOpen.value = false;
    }, 200);
}

function onKeydown(event) {
    if (event.key === '/' && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
        event.preventDefault();
        input.value?.focus();
    } else if (event.key === 'Escape') {
        isOpen.value = false;
        input.value?.blur();
    }
}

if (typeof window !== 'undefined') {
    window.addEventListener('keydown', onKeydown);
}
</script>

<template>
    <div class="relative">
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
                autocomplete="off"
                placeholder="Search..."
                class="w-48 rounded-xl border border-gray-300 py-1.5 pr-8 pl-8 text-sm transition focus:w-64 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 shadow-xs"
                @focus="onFocus"
                @blur="onBlur"
            >

            <div v-if="loading" class="pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-brand-600">
                <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
            </div>

            <kbd
                v-else-if="!focused && !term"
                class="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 rounded border border-gray-300 px-1 text-[0.65rem] text-gray-400 dark:border-gray-700"
            >/</kbd>
        </form>

        <!-- Live Results Dropdown -->
        <div
            v-if="isOpen && results.length > 0"
            class="absolute top-full right-0 sm:right-auto sm:left-0 z-50 mt-2 w-80 sm:w-96 rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl dark:border-gray-800 dark:bg-gray-900 animate-in fade-in slide-in-from-top-2 duration-150"
        >
            <div class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <span>Top Results</span>
                <span>Press Enter for all</span>
            </div>

            <div class="max-h-80 overflow-y-auto py-1 divide-y divide-gray-50 dark:divide-gray-800/40">
                <a
                    v-for="item in results"
                    :key="item.id"
                    :href="item.url"
                    class="flex flex-col gap-0.5 rounded-xl px-3 py-2 transition hover:bg-brand-50/60 dark:hover:bg-brand-950/30 group"
                >
                    <div class="flex items-center gap-2 text-[10px] font-semibold text-gray-400">
                        <span v-if="item.category" class="font-bold text-brand-600 dark:text-brand-400">
                            {{ item.category }}
                        </span>
                        <span v-if="item.published_at">&middot; {{ item.published_at }}</span>
                        <span v-if="item.reading_time">&middot; {{ item.reading_time }} min read</span>
                    </div>
                    <span class="text-xs font-semibold text-gray-800 group-hover:text-brand-600 dark:text-gray-200 dark:group-hover:text-brand-400 line-clamp-2 transition">
                        {{ item.title }}
                    </span>
                </a>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 pt-1 text-center">
                <a
                    :href="`${action}?q=${encodeURIComponent(term)}`"
                    class="block rounded-lg py-1.5 text-xs font-bold text-brand-600 hover:bg-gray-50 dark:text-brand-400 dark:hover:bg-gray-800/60 transition"
                >
                    View all results for "{{ term }}" &rarr;
                </a>
            </div>
        </div>
    </div>
</template>
