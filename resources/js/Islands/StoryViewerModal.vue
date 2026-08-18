<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    stories: {
        type: Array,
        default: () => [],
    },
    signs: {
        type: Object,
        default: () => ({}),
    },
    todayHoroscopes: {
        type: Object,
        default: () => ({}),
    },
    pageUrl: {
        type: String,
        default: () => (typeof window !== 'undefined' ? window.location.href : ''),
    },
});

// Stories state
const isStoryOpen = ref(false);
const activeStoryIndex = ref(0);
const progress = ref(0);
let timer = null;
const duration = 5000;

// Horoscope state
const isHoroscopeOpen = ref(false);
const selectedHoroscope = ref(null);
const isLoopPaused = ref(false);

const currentStory = computed(() => props.stories[activeStoryIndex.value] || null);
const hasHoroscopes = computed(() => props.signs && Object.keys(props.signs).length > 0);

// Double signs array so the marquee loops seamlessly without any jump
const signsArray = computed(() => {
    if (!props.signs) return [];
    return Object.entries(props.signs).map(([slug, data]) => ({ slug, ...data }));
});

// Repeated list for continuous seamless auto-looping
const loopSigns = computed(() => {
    if (signsArray.value.length === 0) return [];
    return [...signsArray.value, ...signsArray.value];
});

// --- Story Modal Functions ---
function openStory(index = 0) {
    activeStoryIndex.value = index;
    isStoryOpen.value = true;
    startProgress();
}

function closeStory() {
    isStoryOpen.value = false;
    stopProgress();
}

function nextStory() {
    if (activeStoryIndex.value < props.stories.length - 1) {
        activeStoryIndex.value++;
        startProgress();
    } else {
        closeStory();
    }
}

function prevStory() {
    if (activeStoryIndex.value > 0) {
        activeStoryIndex.value--;
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

// --- Horoscope Modal Functions ---
function openHoroscope(slug) {
    const h = props.todayHoroscopes[slug] || null;
    if (h) {
        selectedHoroscope.value = h;
        isHoroscopeOpen.value = true;
    }
}

function closeHoroscope() {
    isHoroscopeOpen.value = false;
}

function shareHoroscope(h) {
    if (!h) return;
    const text = encodeURIComponent(`🔮 *Today's Horoscope for ${h.sign.name} (${h.sign.vedic})*:\n\n✨ ${h.overview}\n\n🔢 Lucky Number: *${h.lucky_number}*\n🎨 Lucky Color: *${h.lucky_color}*\n💫 Energy: *${h.score}%*\n\n👉 Read full prediction here:\n${props.pageUrl || window.location.href}`);
    window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        if (isStoryOpen.value) closeStory();
        if (isHoroscopeOpen.value) closeHoroscope();
    }
    if (isStoryOpen.value) {
        if (event.key === 'ArrowRight') nextStory();
        if (event.key === 'ArrowLeft') prevStory();
    }
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
    <div class="mb-10 space-y-8 select-none">
        
        <!-- 1. AUTO-LOOPING DAILY HOROSCOPE & ZODIAC CAROUSEL -->
        <section v-if="hasHoroscopes" class="overflow-hidden" aria-label="Daily Horoscope Carousel">
            <div class="mb-3.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex size-7 items-center justify-center rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white text-sm shadow-sm">
                        🔮
                    </span>
                    <div>
                        <h3 class="text-sm sm:text-base font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                            <span>Daily Horoscope & Zodiac</span>
                            <span class="rounded-full bg-purple-100 dark:bg-purple-950/60 px-2.5 py-0.5 text-[10px] font-extrabold text-purple-700 dark:text-purple-300">
                                Today
                            </span>
                        </h3>
                    </div>
                </div>

                <a
                    v-if="pageUrl"
                    :href="pageUrl"
                    class="inline-flex items-center gap-1 text-xs font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 group"
                >
                    <span>Full Forecast</span>
                    <span class="transition-transform group-hover:translate-x-0.5">&rarr;</span>
                </a>
            </div>

            <!-- Auto-Looping Marquee Track Container with Smooth Fade Edges -->
            <div
                class="relative w-full overflow-hidden py-1 before:pointer-events-none before:absolute before:left-0 before:top-0 before:z-10 before:h-full before:w-6 sm:before:w-10 before:bg-gradient-to-r before:from-white dark:before:from-gray-950 after:pointer-events-none after:absolute after:right-0 after:top-0 after:z-10 after:h-full after:w-6 sm:after:w-10 after:bg-gradient-to-l after:from-white dark:after:from-gray-950"
                @mouseenter="isLoopPaused = true"
                @mouseleave="isLoopPaused = false"
                @touchstart.passive="isLoopPaused = true"
                @touchend.passive="isLoopPaused = false"
            >
                <div
                    class="horoscope-auto-track flex items-center gap-4 sm:gap-5 pb-2"
                    :class="{ 'is-paused': isLoopPaused }"
                >
                    <div
                        v-for="(sign, idx) in loopSigns"
                        :key="`${sign.slug}-${idx}`"
                        class="group relative flex-shrink-0 cursor-pointer flex flex-col items-center select-none"
                        @click="openHoroscope(sign.slug)"
                    >
                        <!-- Glowing Gradient Ring with Sign Illustration -->
                        <div
                            class="relative size-20 sm:size-24 rounded-full p-[2.5px] transition-all duration-300 group-hover:scale-105 group-hover:shadow-xl shadow-md"
                            :style="{ background: `linear-gradient(135deg, ${sign.color}, #a855f7, #6366f1)` }"
                        >
                            <div class="relative size-full overflow-hidden rounded-full border border-white/20 bg-[#0d0d21]">
                                <img
                                    :src="sign.image"
                                    :alt="sign.name"
                                    class="size-full object-cover transition duration-300 group-hover:scale-110"
                                    loading="lazy"
                                >
                            </div>
                        </div>

                        <!-- Sign Name & Vedic Title -->
                        <span class="mt-2 text-center text-xs sm:text-sm font-black text-gray-800 dark:text-gray-200 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition">
                            {{ sign.name }}
                        </span>
                        <span class="text-[10px] sm:text-[11px] font-bold text-gray-400 dark:text-gray-500">
                            {{ sign.vedic.split(' ')[0] }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. WEB STORIES CAROUSEL ROW -->
        <section v-if="stories.length > 0" class="overflow-hidden" aria-label="Visual Web Stories">
            <div class="mb-3.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex size-2 rounded-full bg-pink-500 animate-ping"></span>
                    <h3 class="text-sm sm:text-base font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-1.5">
                        <span>⚡ Web Stories</span>
                    </h3>
                </div>
                <span class="text-xs font-semibold text-gray-400">Tap to watch</span>
            </div>

            <div class="flex items-center gap-3.5 sm:gap-4 overflow-x-auto pb-3 pt-1 scrollbar-none">
                <div
                    v-for="(story, idx) in stories"
                    :key="story.id"
                    class="group relative flex-shrink-0 cursor-pointer flex flex-col items-center"
                    @click="openStory(idx)"
                >
                    <div class="relative size-16 sm:size-20 rounded-full p-[2.5px] bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 transition-transform duration-300 group-hover:scale-105 shadow-sm">
                        <div class="relative size-full overflow-hidden rounded-full border-2 border-white dark:border-gray-950 bg-gradient-to-br from-brand-600 to-purple-800 flex items-center justify-center text-white">
                            <img
                                v-if="story.image"
                                :src="story.image"
                                :alt="story.title"
                                class="absolute inset-0 size-full object-cover opacity-60 blur-[1px]"
                                loading="lazy"
                            >
                            <span class="relative z-10 text-xs font-black uppercase tracking-wider drop-shadow-md">
                                {{ (story.category || '⚡').slice(0, 3) }}
                            </span>
                        </div>
                    </div>

                    <span class="mt-1.5 w-16 sm:w-20 truncate text-center text-[11px] font-semibold text-gray-700 dark:text-gray-300 group-hover:text-brand-600 dark:group-hover:text-brand-400">
                        {{ story.title }}
                    </span>
                </div>
            </div>
        </section>

        <!-- FULLSCREEN WEB STORY MODAL -->
        <Teleport to="body">
            <div v-if="isStoryOpen && currentStory" class="fixed inset-0 z-50 flex items-center justify-center bg-black/95 select-none p-4">
                <button
                    type="button"
                    aria-label="Close story"
                    class="absolute top-4 right-4 z-50 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 transition backdrop-blur-md"
                    @click.stop="closeStory"
                >
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>

                <div
                    class="relative w-full max-w-sm h-full max-h-[88vh] rounded-3xl overflow-hidden shadow-2xl flex flex-col justify-between bg-gray-950 text-white cursor-pointer border border-white/10"
                    @click="handleTap"
                >
                    <img
                        v-if="currentStory.image"
                        :src="currentStory.image"
                        :alt="currentStory.title"
                        class="absolute inset-0 size-full object-cover blur-2xl scale-125 opacity-30 brightness-50"
                    >
                    <div class="absolute inset-0 bg-gradient-to-b from-gray-950/80 via-gray-950/60 to-gray-950"></div>

                    <div class="relative z-20 flex items-center gap-1.5 p-4 pt-4">
                        <div
                            v-for="(s, i) in stories"
                            :key="s.id"
                            class="h-1 flex-1 overflow-hidden rounded-full bg-white/20"
                        >
                            <div
                                class="h-full bg-white transition-all duration-75"
                                :style="{ width: i < activeStoryIndex ? '100%' : (i === activeStoryIndex ? `${progress}%` : '0%') }"
                            ></div>
                        </div>
                    </div>

                    <div class="relative z-20 px-5 flex items-center justify-between text-xs">
                        <span v-if="currentStory.category" class="rounded-full bg-brand-500/80 backdrop-blur-md px-3 py-1 font-extrabold uppercase tracking-wider text-[10px] text-white shadow-sm">
                            {{ currentStory.category }}
                        </span>
                        <span v-if="currentStory.reading_time" class="font-medium text-white/60 text-xs">
                            {{ currentStory.reading_time }} min read
                        </span>
                    </div>

                    <div class="relative z-20 my-auto flex flex-col items-center justify-center p-6 text-center">
                        <div class="size-16 rounded-2xl bg-gradient-to-tr from-brand-600 to-purple-600 p-0.5 shadow-xl">
                            <div class="size-full rounded-[14px] bg-gray-900/90 flex items-center justify-center text-2xl">
                                ⚡
                            </div>
                        </div>
                    </div>

                    <div class="relative z-20 p-6 space-y-4 bg-gradient-to-t from-black via-black/80 to-transparent pt-8">
                        <h2 class="text-xl sm:text-2xl font-black leading-tight tracking-tight text-white drop-shadow-md">
                            {{ currentStory.title }}
                        </h2>
                        <p v-if="currentStory.excerpt" class="text-xs sm:text-sm text-gray-300 line-clamp-3 leading-relaxed">
                            {{ currentStory.excerpt }}
                        </p>
                        <div class="pt-2">
                            <a
                                :href="currentStory.url"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3.5 text-xs font-bold text-gray-900 shadow-xl transition hover:bg-gray-100 active:scale-95"
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

        <!-- LUXURY CELESTIAL ZODIAC STORY MODAL -->
        <Teleport to="body">
            <div
                v-if="isHoroscopeOpen && selectedHoroscope"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4 overflow-y-auto"
                @click.self="closeHoroscope"
            >
                <div class="relative w-full max-w-lg rounded-3xl border border-purple-500/30 bg-[#0d0d21] p-6 sm:p-8 text-white shadow-2xl animate-scaleUp my-8">
                    
                    <button
                        type="button"
                        aria-label="Close"
                        class="absolute top-5 right-5 rounded-full bg-white/10 p-2 text-gray-400 hover:bg-white/20 hover:text-white transition"
                        @click="closeHoroscope"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>

                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="relative size-24 sm:size-28 shrink-0 overflow-hidden rounded-2xl p-1 border-2 shadow-2xl"
                            :style="{ borderColor: selectedHoroscope.sign.color }"
                        >
                            <img
                                :src="selectedHoroscope.sign.image"
                                :alt="selectedHoroscope.sign.name"
                                class="size-full object-cover rounded-xl"
                            >
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-2xl font-black">{{ selectedHoroscope.sign.name }}</h3>
                                <span class="rounded-full bg-purple-500/20 px-2.5 py-0.5 text-xs font-bold text-purple-300 border border-purple-400/30">
                                    {{ selectedHoroscope.sign.element }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1 font-medium">
                                {{ selectedHoroscope.sign.vedic }} &middot; {{ selectedHoroscope.sign.dates }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-purple-500/30 bg-white/[0.04] p-4 sm:p-5 mb-5 shadow-inner">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-black uppercase tracking-wider text-purple-300 flex items-center gap-1">
                                <span>✨</span> Today's Horoscope
                            </span>
                            <span class="text-[11px] font-bold text-gray-400">{{ selectedHoroscope.date }}</span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-200 leading-relaxed">
                            {{ selectedHoroscope.overview }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5 text-center">
                        <div class="rounded-xl border border-white/10 bg-white/[0.04] p-2.5">
                            <span class="text-[10px] font-black uppercase text-gray-400">Lucky No</span>
                            <div class="text-base font-black text-purple-400 mt-0.5">#{{ selectedHoroscope.lucky_number }}</div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.04] p-2.5">
                            <span class="text-[10px] font-black uppercase text-gray-400">Lucky Color</span>
                            <div class="text-xs font-black text-amber-300 mt-1 truncate">{{ selectedHoroscope.lucky_color }}</div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.04] p-2.5">
                            <span class="text-[10px] font-black uppercase text-gray-400">Energy</span>
                            <div class="text-base font-black text-emerald-400 mt-0.5">{{ selectedHoroscope.score }}%</div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/[0.04] p-2.5">
                            <span class="text-[10px] font-black uppercase text-gray-400">Planet</span>
                            <div class="text-xs font-black text-cyan-300 mt-1 truncate">{{ selectedHoroscope.sign.planet }}</div>
                        </div>
                    </div>

                    <div class="space-y-2.5 mb-6 text-xs">
                        <div class="flex gap-3 rounded-xl border border-pink-500/20 bg-pink-500/10 p-3">
                            <span class="text-base">💖</span>
                            <div>
                                <strong class="font-bold text-pink-300">Love:</strong>
                                <p class="text-gray-300 mt-0.5">{{ selectedHoroscope.love }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3 rounded-xl border border-indigo-500/20 bg-indigo-500/10 p-3">
                            <span class="text-base">💼</span>
                            <div>
                                <strong class="font-bold text-indigo-300">Career:</strong>
                                <p class="text-gray-300 mt-0.5">{{ selectedHoroscope.career }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-3 text-xs font-black transition shadow-lg active:scale-95"
                            @click="shareHoroscope(selectedHoroscope)"
                        >
                            <span>📲 Share on WhatsApp</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl border border-white/20 bg-white/5 px-5 py-3 text-xs font-bold text-gray-300 hover:bg-white/10 hover:text-white transition"
                            @click="closeHoroscope"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes marqueeScroll {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.horoscope-auto-track {
    display: flex;
    width: max-content;
    animation: marqueeScroll 28s linear infinite;
}

.horoscope-auto-track:hover,
.horoscope-auto-track.is-paused {
    animation-play-state: paused;
}

@keyframes scaleUp {
    from { opacity: 0; transform: scale(0.94); }
    to { opacity: 1; transform: scale(1); }
}
.animate-scaleUp {
    animation: scaleUp 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
