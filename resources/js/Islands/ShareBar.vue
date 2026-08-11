<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    url: { type: String, required: true },
    title: { type: String, required: true },
});

const copied = ref(false);

const targets = computed(() => {
    const url = encodeURIComponent(props.url);
    const title = encodeURIComponent(props.title);

    return [
        { label: 'X', href: `https://twitter.com/intent/tweet?url=${url}&text=${title}` },
        { label: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${url}` },
        { label: 'WhatsApp', href: `https://api.whatsapp.com/send?text=${title}%20${url}` },
        { label: 'LinkedIn', href: `https://www.linkedin.com/sharing/share-offsite/?url=${url}` },
    ];
});

const canShareNatively = typeof navigator !== 'undefined' && !!navigator.share;

async function shareNatively() {
    try {
        await navigator.share({ title: props.title, url: props.url });
    } catch {
        // The user dismissed the sheet; nothing to report.
    }
}

async function copy() {
    try {
        await navigator.clipboard.writeText(props.url);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        copied.value = false;
    }
}
</script>

<template>
    <div>
        <p class="mb-2 text-sm font-medium">Share this story</p>

        <div class="flex flex-wrap gap-2 text-sm">
            <button
                v-if="canShareNatively"
                type="button"
                class="rounded-lg bg-brand-600 px-3 py-1.5 font-medium text-white transition hover:bg-brand-700"
                @click="shareNatively"
            >Share</button>

            <a
                v-for="target in targets"
                :key="target.label"
                :href="target.href"
                target="_blank"
                rel="noopener noreferrer"
                class="rounded-lg border border-gray-200 px-3 py-1.5 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800"
            >{{ target.label }}</a>

            <button
                type="button"
                class="rounded-lg border border-gray-200 px-3 py-1.5 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800"
                @click="copy"
            >{{ copied ? 'Copied' : 'Copy link' }}</button>
        </div>
    </div>
</template>
