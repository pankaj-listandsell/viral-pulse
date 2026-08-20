<script setup>
import { ref } from 'vue';
import MediaPickerDialog from './partials/MediaPickerDialog.vue';

const props = defineProps({
    name: { type: String, required: true },
    value: { type: String, default: null },
    mediaEndpoint: { type: String, required: true },
    uploadEndpoint: { type: String, required: true },
    generateEndpoint: { type: String, default: null },
});

const path = ref(props.value ?? '');
const preview = ref(props.value ? `/storage/${props.value}` : '');
const open = ref(false);
const generating = ref(false);

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

        <div v-if="generateEndpoint" class="mt-3">
            <button
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
        </div>

        <MediaPickerDialog
            v-if="open"
            :media-endpoint="mediaEndpoint"
            :upload-endpoint="uploadEndpoint"
            @select="choose"
            @close="open = false"
        />
    </div>
</template>
