<script setup>
import { ref } from 'vue';
import MediaPickerDialog from './partials/MediaPickerDialog.vue';

const props = defineProps({
    name: { type: String, required: true },
    value: { type: String, default: null },
    mediaEndpoint: { type: String, required: true },
    uploadEndpoint: { type: String, required: true },
    generateEndpoint: { type: String, default: null },
    pexelsSearchEndpoint: { type: String, default: null },
    pexelsSelectEndpoint: { type: String, default: null },
});

const path = ref(props.value ?? '');
const preview = ref(props.value ? `/storage/${props.value}` : '');
const open = ref(false);
const generating = ref(false);

const pexelsOpen = ref(false);
const pexelsSearching = ref(false);
const pexelsPhotos = ref([]);
const pexelsQuery = ref('');
const pexelsSelecting = ref(null);

function choose(media) {
    path.value = media.path;
    preview.value = media.thumbnail;
    open.value = false;
}

function clear() {
    path.value = '';
    preview.value = '';
}

async function generateImage() {
    if (generating.value || !props.generateEndpoint) return;
    generating.value = true;

    const titleEl = document.getElementById('title');
    const title = titleEl ? titleEl.value : '';

    const tagEls = document.querySelectorAll('input[name="tags[]"]');
    const tags = Array.from(tagEls).map(el => el.value);

    try {
        const response = await fetch(props.generateEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ title, tags })
        });

        const data = await response.json();
        if (response.ok && data.success) {
            path.value = data.path;
            preview.value = data.url;
        } else {
            alert(data.error || 'Failed to generate image');
        }
    } catch (error) {
        alert('An error occurred while generating the image.');
    } finally {
        generating.value = false;
    }
}

async function openPexelsSearch() {
    pexelsOpen.value = true;
    pexelsPhotos.value = [];
    pexelsQuery.value = '';
    
    const titleEl = document.getElementById('title');
    const title = titleEl ? titleEl.value : '';

    const tagEls = document.querySelectorAll('input[name="tags[]"]');
    const tags = Array.from(tagEls).map(el => el.value);

    pexelsSearching.value = true;
    try {
        const url = new URL(props.pexelsSearchEndpoint, window.location.origin);
        url.searchParams.set('title', title);
        tags.forEach(t => url.searchParams.append('tags[]', t));

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            }
        });
        const data = await response.json();
        if (response.ok && data.success) {
            pexelsPhotos.value = data.photos;
            pexelsQuery.value = data.query;
        }
    } catch (error) {
        console.error('Failed to pre-fetch Pexels images', error);
    } finally {
        pexelsSearching.value = false;
    }
}

async function runPexelsSearch() {
    if (pexelsSearching.value) return;
    pexelsSearching.value = true;

    try {
        const url = new URL(props.pexelsSearchEndpoint, window.location.origin);
        url.searchParams.set('query', pexelsQuery.value);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            }
        });
        const data = await response.json();
        if (response.ok && data.success) {
            pexelsPhotos.value = data.photos;
        } else {
            alert(data.error || 'Failed to search Pexels');
        }
    } catch (error) {
        alert('An error occurred during search.');
    } finally {
        pexelsSearching.value = false;
    }
}

async function selectPexelsPhoto(photo) {
    if (pexelsSelecting.value) return;
    pexelsSelecting.value = photo.id;

    try {
        const response = await fetch(props.pexelsSelectEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ photo })
        });

        const data = await response.json();
        if (response.ok && data.success) {
            path.value = data.path;
            preview.value = data.url;
            pexelsOpen.value = false;
        } else {
            alert(data.error || 'Failed to select image');
        }
    } catch (error) {
        alert('An error occurred while saving the selected image.');
    } finally {
        pexelsSelecting.value = null;
    }
}
</script>

<template>
    <div>
        <div v-if="preview" class="group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
            <img :src="preview" alt="Featured image preview" class="aspect-16/9 w-full object-cover">

            <div class="absolute inset-0 flex items-center justify-center gap-2 bg-gray-900/60 opacity-0 transition group-hover:opacity-100">
                <button type="button" class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-900" @click="open = true">
                    Replace
                </button>
                <button type="button" class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white" @click="clear">
                    Remove
                </button>
            </div>
        </div>

        <button
            v-else
            type="button"
            class="flex w-full flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed border-gray-300 px-4 py-8 text-sm text-gray-500 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-700 dark:text-gray-400"
            @click="open = true"
        >
            <span class="text-2xl leading-none">+</span>
            Choose an image
        </button>

        <input type="hidden" :name="name" :value="path">

        <div v-if="generateEndpoint || pexelsSearchEndpoint" class="mt-3 flex flex-col gap-2">
            <!-- AI Generator Button -->
            <button
                v-if="generateEndpoint"
                type="button"
                :disabled="generating"
                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:opacity-60 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/80"
                @click="generateImage"
            >
                <svg v-if="generating" class="animate-spin size-3.5 text-current" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="size-3.5 text-purple-600 dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z" />
                    <path d="M20 2v4" />
                    <path d="M22 4h-4" />
                    <circle cx="4" cy="20" r="2" />
                </svg>
                <span>{{ generating ? 'Generating AI Image...' : 'Generate AI Image' }}</span>
            </button>

            <!-- Pexels Search Button -->
            <button
                v-if="pexelsSearchEndpoint"
                type="button"
                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/80"
                @click="openPexelsSearch"
            >
                <svg class="size-3.5 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <span>Search Stock Photo (Pexels)</span>
            </button>
        </div>

        <MediaPickerDialog
            v-if="open"
            :media-endpoint="mediaEndpoint"
            :upload-endpoint="uploadEndpoint"
            @select="choose"
            @close="open = false"
        />

        <!-- Pexels Search Dialog -->
        <div v-if="pexelsOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Search Pexels Images</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" @click="pexelsOpen = false">
                        <span class="sr-only">Close</span>
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="mt-4 flex gap-2">
                    <input
                        v-model="pexelsQuery"
                        type="text"
                        placeholder="Search stock photos..."
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500/20 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        @keyup.enter="runPexelsSearch"
                    >
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 transition focus:outline-none"
                        @click="runPexelsSearch"
                    >
                        Search
                    </button>
                </div>

                <!-- Photos Grid -->
                <div class="mt-4 max-h-96 overflow-y-auto pr-1">
                    <div v-if="pexelsSearching" class="flex items-center justify-center py-12">
                        <svg class="animate-spin size-8 text-brand-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div v-else-if="pexelsPhotos.length === 0" class="text-center py-12 text-sm text-gray-500 dark:text-gray-400">
                        No images found. Try a different search term.
                    </div>

                    <div v-else class="grid grid-cols-3 gap-3">
                        <div
                            v-for="photo in pexelsPhotos"
                            :key="photo.id"
                            class="group relative aspect-video cursor-pointer overflow-hidden rounded-lg border border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-950"
                            @click="selectPexelsPhoto(photo)"
                        >
                            <img :src="photo.url" :alt="photo.alt" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            
                            <!-- Photographer Credit Overlay -->
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-gray-950/70 via-gray-950/40 to-transparent p-1.5 text-[10px] text-white opacity-0 transition group-hover:opacity-100">
                                by {{ photo.photographer }}
                            </div>

                            <!-- Downloading Overlay -->
                            <div v-if="pexelsSelecting === photo.id" class="absolute inset-0 flex items-center justify-center bg-gray-900/60">
                                <svg class="animate-spin size-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/80"
                        @click="pexelsOpen = false"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
