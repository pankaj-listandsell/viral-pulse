<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    post: {
        type: Object,
        default: null,
    },
});

const isVisible = ref(false);
const isDismissed = ref(false);

function handleScroll() {
    if (isDismissed.value || !props.post) return;

    const scrollTotal = document.documentElement.scrollHeight - window.innerHeight;
    if (scrollTotal <= 0) return;

    const scrollPercentage = (window.scrollY / scrollTotal) * 100;
    if (scrollPercentage > 65) {
        isVisible.value = true;
    } else {
        isVisible.value = false;
    }
}

function dismiss() {
    isVisible.value = false;
    isDismissed.value = true;
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div
        v-if="post && isVisible && !isDismissed"
        class="fixed bottom-6 right-6 z-40 max-w-xs sm:max-w-sm rounded-2xl border border-gray-200/90 bg-white/95 p-4 shadow-xl backdrop-blur-md transition-all duration-300 transform translate-y-0 opacity-100 dark:border-gray-800 dark:bg-gray-900/95"
    >
        <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-1.5 text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-wider">
                <span>⚡</span>
                <span>Up Next</span>
            </div>

            <button
                type="button"
                aria-label="Dismiss"
                class="rounded-md p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                @click="dismiss"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <h4 class="mt-2 text-sm font-semibold leading-snug text-gray-900 line-clamp-2 dark:text-white">
            <a :href="post.url" class="hover:text-brand-600 dark:hover:text-brand-400 transition">
                {{ post.title }}
            </a>
        </h4>

        <div class="mt-3 flex items-center justify-between">
            <span v-if="post.reading_time" class="text-[11px] font-medium text-gray-500">
                {{ post.reading_time }} min read
            </span>
            <a
                :href="post.url"
                class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-700 dark:text-brand-400"
            >
                Read Story &rarr;
            </a>
        </div>
    </div>
</template>
