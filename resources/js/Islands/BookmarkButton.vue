<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    post: {
        type: Object,
        required: true,
        // expects { id, title, slug, image, category, reading_time }
    },
});

const isSaved = ref(false);

function getSavedList() {
    try {
        return JSON.parse(localStorage.getItem('viral_saved_posts') || '[]');
    } catch {
        return [];
    }
}

function checkSaved() {
    const list = getSavedList();
    isSaved.value = list.some(item => String(item.id) === String(props.post.id));
}

function toggle() {
    let list = getSavedList();
    if (isSaved.value) {
        list = list.filter(item => String(item.id) !== String(props.post.id));
        isSaved.value = false;
    } else {
        list.unshift({
            id: props.post.id,
            title: props.post.title,
            slug: props.post.slug,
            image: props.post.image || null,
            category: props.post.category || null,
            url: props.post.url || `/post/${props.post.slug}`,
            saved_at: new Date().toISOString(),
        });
        isSaved.value = true;
    }

    localStorage.setItem('viral_saved_posts', JSON.stringify(list));
    window.dispatchEvent(new CustomEvent('viral-bookmarks-updated'));
}

onMounted(() => {
    checkSaved();
    window.addEventListener('viral-bookmarks-updated', checkSaved);
});
</script>

<template>
    <button
        type="button"
        :aria-label="isSaved ? 'Remove from saved' : 'Save article for later'"
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition active:scale-95',
            isSaved
                ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-950/40 dark:text-brand-300'
                : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:border-gray-800 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-200'
        ]"
        @click="toggle"
    >
        <svg
            class="size-4 transition-colors"
            :class="isSaved ? 'fill-brand-600 text-brand-600 dark:fill-brand-400 dark:text-brand-400' : 'text-current'"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
        </svg>
        <span>{{ isSaved ? 'Saved' : 'Save' }}</span>
    </button>
</template>
