<script setup>
import { ref, onMounted } from 'vue';

const isOpen = ref(false);
const savedPosts = ref([]);

function loadSaved() {
    try {
        savedPosts.value = JSON.parse(localStorage.getItem('viral_saved_posts') || '[]');
    } catch {
        savedPosts.value = [];
    }
}

function removePost(id) {
    savedPosts.value = savedPosts.value.filter(p => String(p.id) !== String(id));
    localStorage.setItem('viral_saved_posts', JSON.stringify(savedPosts.value));
    window.dispatchEvent(new CustomEvent('viral-bookmarks-updated'));
}

function clearAll() {
    savedPosts.value = [];
    localStorage.removeItem('viral_saved_posts');
    window.dispatchEvent(new CustomEvent('viral-bookmarks-updated'));
}

onMounted(() => {
    loadSaved();
    window.addEventListener('viral-bookmarks-updated', loadSaved);
});
</script>

<template>
    <div class="relative">
        <!-- Bookmark Trigger Button in Header -->
        <button
            type="button"
            aria-label="View saved bookmarks"
            class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
            @click="isOpen = true"
        >
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
            </svg>
            <!-- Badge count -->
            <span
                v-if="savedPosts.length > 0"
                class="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-white shadow-xs"
            >
                {{ savedPosts.length }}
            </span>
        </button>

        <!-- Slide-over Drawer Modal -->
        <Teleport to="body">
            <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
                    @click="isOpen = false"
                ></div>

                <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                    <div class="w-screen max-w-sm bg-white shadow-2xl dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 flex flex-col">
                        <!-- Drawer Header -->
                        <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-800">
                            <div class="flex items-center gap-2">
                                <svg class="size-5 text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                                </svg>
                                <h3 class="font-bold text-gray-900 dark:text-white">Saved Stories</h3>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    {{ savedPosts.length }}
                                </span>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                @click="isOpen = false"
                            >
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Drawer Body -->
                        <div class="flex-1 overflow-y-auto p-4 divide-y divide-gray-100 dark:divide-gray-800/80">
                            <div v-if="savedPosts.length === 0" class="py-16 text-center text-gray-400">
                                <svg class="mx-auto size-12 stroke-1 text-gray-300 dark:text-gray-700 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                                </svg>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">No saved stories yet</p>
                                <p class="text-xs text-gray-500 mt-1">Click the "Save" button on any article to read it later.</p>
                            </div>

                            <div
                                v-for="post in savedPosts"
                                :key="post.id"
                                class="py-3 flex items-start justify-between gap-3 group"
                            >
                                <div class="flex-1 min-w-0">
                                    <span v-if="post.category" class="text-[10px] font-bold text-brand-600 uppercase tracking-wider block">
                                        {{ post.category }}
                                    </span>
                                    <a
                                        :href="post.url"
                                        class="text-sm font-semibold text-gray-900 hover:text-brand-600 dark:text-gray-100 dark:hover:text-brand-400 line-clamp-2 transition mt-0.5"
                                        @click="isOpen = false"
                                    >
                                        {{ post.title }}
                                    </a>
                                </div>

                                <button
                                    type="button"
                                    aria-label="Remove saved post"
                                    class="text-gray-400 hover:text-red-500 p-1 transition"
                                    @click="removePost(post.id)"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Drawer Footer -->
                        <div v-if="savedPosts.length > 0" class="border-t border-gray-200 p-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="w-full text-center text-xs font-semibold text-red-600 hover:text-red-700 py-1.5 transition"
                                @click="clearAll"
                            >
                                Clear all saved stories
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
