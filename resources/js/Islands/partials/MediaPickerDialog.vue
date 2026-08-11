<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    mediaEndpoint: { type: String, required: true },
    uploadEndpoint: { type: String, required: true },
});

const emit = defineEmits(['select', 'close']);

const items = ref([]);
const loading = ref(true);
const uploading = ref(false);
const error = ref('');
const search = ref('');
const nextPage = ref(null);

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function load(page = 1, replace = true) {
    loading.value = true;
    error.value = '';

    try {
        const url = new URL(props.mediaEndpoint, window.location.origin);
        url.searchParams.set('page', page);
        if (search.value) url.searchParams.set('search', search.value);

        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) throw new Error('Could not load the media library.');

        const payload = await response.json();
        items.value = replace ? payload.data : [...items.value, ...payload.data];
        nextPage.value = payload.next_page;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

async function upload(event) {
    const files = [...event.target.files];
    if (!files.length) return;

    uploading.value = true;
    error.value = '';

    const body = new FormData();
    files.forEach((file) => body.append('files[]', file));

    try {
        const response = await fetch(props.uploadEndpoint, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            body,
        });

        const payload = await response.json();

        if (!response.ok) {
            // Surface the server's own validation messages rather than a generic failure.
            error.value = payload.failed?.join(' ')
                ?? Object.values(payload.errors ?? {}).flat().join(' ')
                ?? 'Upload failed.';
            return;
        }

        if (payload.failed?.length) error.value = payload.failed.join(' ');

        items.value = [...payload.uploaded, ...items.value];
    } catch {
        error.value = 'Upload failed. Check your connection and try again.';
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

onMounted(() => load());
</script>

<template>
    <div
        class="fixed inset-0 z-50 grid place-items-center bg-gray-900/50 p-4 backdrop-blur-xs"
        @keydown.escape="emit('close')"
    >
        <div
            class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900"
            role="dialog"
            aria-modal="true"
            aria-label="Media library"
        >
            <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-4 dark:border-gray-800">
                <h2 class="text-sm font-semibold">Media library</h2>

                <input
                    v-model="search"
                    type="search"
                    placeholder="Search"
                    class="ml-auto w-40 rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                    @keydown.enter.prevent="load(1)"
                >

                <label class="cursor-pointer rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-brand-700">
                    <span v-if="uploading">Uploading…</span>
                    <span v-else>Upload</span>
                    <input type="file" class="sr-only" accept="image/*" multiple :disabled="uploading" @change="upload">
                </label>

                <button type="button" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                        aria-label="Close" @click="emit('close')">✕</button>
            </div>

            <p v-if="error" class="border-b border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                {{ error }}
            </p>

            <div class="flex-1 overflow-y-auto p-4">
                <p v-if="loading && !items.length" class="py-10 text-center text-sm text-gray-500">Loading…</p>

                <p v-else-if="!items.length" class="py-10 text-center text-sm text-gray-500">
                    No images yet. Upload one to get started.
                </p>

                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    <button
                        v-for="item in items"
                        :key="item.id"
                        type="button"
                        class="group overflow-hidden rounded-lg border border-gray-200 transition hover:border-brand-500 dark:border-gray-800"
                        @click="emit('select', item)"
                    >
                        <img :src="item.thumbnail" :alt="item.alt_text || item.name" loading="lazy"
                             class="aspect-4/3 w-full object-cover">
                        <span class="block truncate px-2 py-1 text-left text-xs text-gray-500 dark:text-gray-400">
                            {{ item.name }}
                        </span>
                    </button>
                </div>

                <div v-if="nextPage" class="mt-4 text-center">
                    <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-gray-700"
                            @click="load(nextPage, false)">Load more</button>
                </div>
            </div>
        </div>
    </div>
</template>
