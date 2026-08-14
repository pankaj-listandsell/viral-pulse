<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    stories: {
        type: Array,
        default: () => [],
    },
});

const isOpen = ref(false);
const activeIndex = ref(0);
const progress = ref(0);
let timer = null;
const duration = 5000; // 5 seconds per story

const currentStory = computed(() => props.stories[activeIndex.value] || null);

function openStory(index = 0) {
    activeIndex.value = index;
    isOpen.value = true;
    startProgress();
}

function closeStory() {
    isOpen.value = false;
    stopProgress();
}

function nextStory() {
    if (activeIndex.value < props.stories.length - 1) {
        activeIndex.value++;
        startProgress();
    } else {
        closeStory();
    }
}

function prevStory() {
    if (activeIndex.value > 0) {
        activeIndex.value--;
        startProgress();
    }
}

function startProgress() {
    stopProgress();
    progress.value = 0;
    const startTime = Date.now();

    timer = setInterval(() => {
        const elapsed = Date.now() - startTime;
        progress.value = Math.min(100, (elapsed / duration) * 100);
        if (elapsed >= duration) {
            nextStory();
        }
    }, 50);
}

function stopProgress() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function handleTap(event) {
    const screenWidth = window.innerWidth;
    const clickX = event.clientX;
    if (clickX < screenWidth * 0.35) {
        prevStory();
    } else {
        nextStory();
    }
}

function onKeydown(event) {
    if (!isOpen.value) return;
    if (event.key === 'Escape') closeStory();
    if (event.key === 'ArrowRight') nextStory();
    if (event.key === 'ArrowLeft') prevStory();
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    stopProgress();
});
</script>

<template>
    <div v-if="stories.length > 0">
        <!-- Visual Story Carousel Row -->
        <div class="mb-10 overflow-hidden">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex size-2 rounded-full bg-pink-500 animate-ping"></span>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-gray-900 dark:text-white flex items-center gap-1.5">
                        <span>⚡ Web Stories</span>
                    </h3>
                </div>
                <span class="text-xs font-semibold text-gray-400">Tap to watch</span>
            </div>

            <div class="flex items-center gap-3.5 overflow-x-auto pb-2 scrollbar-none">
                <div
                    v-for="(story, idx) in stories"
                    :key="story.id"
                    class="group relative flex-shrink-0 cursor-pointer flex flex-col items-center select-none"
                    @click="openStory(idx)"
                >
                    <!-- Gradient Story Ring around Avatar / Image -->
                    <div class="relative size-16 sm:size-20 rounded-full p-[2.5px] bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 transition-transform duration-300 group-hover:scale-105 shadow-md">
                        <div class="size-full overflow-hidden rounded-full border-2 border-white dark:border-gray-950 bg-gray-100 dark:bg-gray-800">
                            <img
                                v-if="story.image"
                                :src="story.image"
                                :alt="story.title"
                                class="size-full object-cover"
                                loading="lazy"
                            >
                            <div v-else class="size-full flex items-center justify-center font-bold text-gray-400 text-xs">
                                ⚡
                            </div>
                        </div>
                    </div>

                    <!-- Story Caption -->
                    <span class="mt-1.5 w-16 sm:w-20 truncate text-center text-[11px] font-semibold text-gray-700 dark:text-gray-300 group-hover:text-brand-600 dark:group-hover:text-brand-400">
                        {{ story.title }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Fullscreen Visual Story Modal -->
        <Teleport to="body">
            <div v-if="isOpen && currentStory" class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 select-none">
                <!-- Close Button -->
                <button
                    type="button"
                    aria-label="Close story"
                    class="absolute top-4 right-4 z-50 rounded-full bg-black/50 p-2 text-white/90 hover:bg-black/80 transition"
                    @click.stop="closeStory"
                >
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>

                <!-- Main Vertical Story Card (9:16 aspect ratio) -->
                <div
                    class="relative w-full max-w-sm h-full max-h-[85vh] sm:rounded-3xl overflow-hidden shadow-2xl flex flex-col justify-between bg-gray-950 text-white cursor-pointer"
                    @click="handleTap"
                >
                    <!-- Story Background Image with dark gradient -->
                    <img
                        v-if="currentStory.image"
                        :src="currentStory.image"
                        :alt="currentStory.title"
                        class="absolute inset-0 size-full object-cover opacity-80"
                    >
                    <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/90"></div>

                    <!-- Top Segmented Progress Bars -->
                    <div class="relative z-20 flex items-center gap-1.5 p-3.5 pt-4">
                        <div
                            v-for="(s, i) in stories"
                            :key="s.id"
                            class="h-1 flex-1 overflow-hidden rounded-full bg-white/30"
                        >
                            <div
                                class="h-full bg-white transition-all duration-75"
                                :style="{
                                    width: i < activeIndex ? '100%' : (i === activeIndex ? `${progress}%` : '0%')
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- Story Top Header (Category & Time) -->
                    <div class="relative z-20 px-4 flex items-center justify-between text-xs">
                        <span v-if="currentStory.category" class="rounded-full bg-brand-600/90 px-3 py-0.5 font-bold uppercase tracking-wider text-[10px]">
                            {{ currentStory.category }}
                        </span>
                        <span v-if="currentStory.reading_time" class="font-medium text-white/70">
                            {{ currentStory.reading_time }} min read
                        </span>
                    </div>

                    <!-- Story Bottom Content & CTA -->
                    <div class="relative z-20 p-6 space-y-4">
                        <h2 class="text-xl sm:text-2xl font-extrabold leading-tight tracking-tight drop-shadow-md">
                            {{ currentStory.title }}
                        </h2>

                        <p v-if="currentStory.excerpt" class="text-xs sm:text-sm text-white/80 line-clamp-3 leading-relaxed drop-shadow-xs">
                            {{ currentStory.excerpt }}
                        </p>

                        <div class="pt-2">
                            <a
                                :href="currentStory.url"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-xs font-bold text-gray-900 shadow-xl transition hover:bg-gray-100 active:scale-95"
                                @click.stop
                            >
                                <span>Read Full Story</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
