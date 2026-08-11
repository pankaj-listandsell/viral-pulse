<script setup>
import { ref } from 'vue';
import MediaPickerDialog from './partials/MediaPickerDialog.vue';

const props = defineProps({
    name: { type: String, required: true },
    value: { type: String, default: null },
    mediaEndpoint: { type: String, required: true },
    uploadEndpoint: { type: String, required: true },
});

const path = ref(props.value ?? '');
const preview = ref(props.value ? `/storage/${props.value}` : '');
const open = ref(false);

function choose(media) {
    path.value = media.path;
    preview.value = media.thumbnail;
    open.value = false;
}

function clear() {
    path.value = '';
    preview.value = '';
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

        <MediaPickerDialog
            v-if="open"
            :media-endpoint="mediaEndpoint"
            :upload-endpoint="uploadEndpoint"
            @select="choose"
            @close="open = false"
        />
    </div>
</template>
