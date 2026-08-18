<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
});

const isPlaying = ref(false);
const isPaused = ref(false);
const speed = ref(1.0);
const progress = ref(0);
const supported = typeof window !== 'undefined' && 'speechSynthesis' in window;

let chunks = [];
let currentChunkIndex = 0;
let totalLength = 0;
let processedLength = 0;
let selectedVoice = null;
let isStopping = false;

function loadVoice() {
    if (!supported) return;
    const voices = window.speechSynthesis.getVoices();
    if (!voices || voices.length === 0) return;

    selectedVoice = voices.find(v => (v.lang === 'en-IN' || v.lang === 'en_IN') && (v.name.includes('Natural') || v.name.includes('Google') || v.name.includes('Online') || v.name.includes('Heera') || v.name.includes('Neerja') || v.name.includes('Ravi') || v.name.includes('Priya') || v.name.includes('Prabhat')))
        || voices.find(v => v.lang === 'en-IN' || v.lang === 'en_IN')
        || voices.find(v => v.lang.startsWith('en') && (v.name.includes('Natural') || v.name.includes('Google') || v.name.includes('Premium')))
        || voices.find(v => v.lang.startsWith('en'))
        || voices[0];
}

onMounted(() => {
    if (supported) {
        loadVoice();
        if (window.speechSynthesis.onvoiceschanged !== undefined) {
            window.speechSynthesis.onvoiceschanged = loadVoice;
        }
    }
});

function getArticleText() {
    const articleEl = document.querySelector('.prose');
    if (!articleEl) return props.title;
    return `${props.title}. ` + articleEl.innerText.replace(/\s+/g, ' ').trim();
}

function splitIntoChunks(text) {
    // Split into sentences so each utterance is short (< 150 chars) for instant audio start
    const rawSentences = text.match(/[^.!?\n]+[.!?\n]+|[^.!?\n]+$/g) || [text];
    const cleanChunks = [];
    
    for (const s of rawSentences) {
        const trimmed = s.trim();
        if (trimmed.length > 0) {
            cleanChunks.push(trimmed);
        }
    }
    return cleanChunks;
}

function speakNextChunk() {
    if (isStopping || !isPlaying.value) return;

    if (currentChunkIndex >= chunks.length) {
        isPlaying.value = false;
        isPaused.value = false;
        progress.value = 100;
        return;
    }

    const chunk = chunks[currentChunkIndex];
    const utterance = new SpeechSynthesisUtterance(chunk);
    utterance.rate = speed.value;
    utterance.lang = 'en-IN';

    if (selectedVoice) {
        utterance.voice = selectedVoice;
    }

    utterance.onend = () => {
        if (isStopping || !isPlaying.value) return;
        processedLength += chunk.length;
        if (totalLength > 0) {
            progress.value = Math.min(99, Math.round((processedLength / totalLength) * 100));
        }
        currentChunkIndex++;
        speakNextChunk();
    };

    utterance.onerror = (e) => {
        if (e.error === 'interrupted' || e.error === 'canceled') return;
        currentChunkIndex++;
        speakNextChunk();
    };

    window.speechSynthesis.speak(utterance);
}

function startSpeaking() {
    if (!supported) return;

    if (isPaused.value) {
        window.speechSynthesis.resume();
        isPlaying.value = true;
        isPaused.value = false;
        return;
    }

    isStopping = true;
    window.speechSynthesis.cancel();
    isStopping = false;

    if (!selectedVoice) {
        loadVoice();
    }

    const fullText = getArticleText();
    chunks = splitIntoChunks(fullText);
    totalLength = chunks.reduce((acc, c) => acc + c.length, 0);
    processedLength = 0;
    currentChunkIndex = 0;
    progress.value = 0;
    isPlaying.value = true;
    isPaused.value = false;

    speakNextChunk();
}

function pauseSpeaking() {
    if (supported && isPlaying.value) {
        window.speechSynthesis.pause();
        isPlaying.value = false;
        isPaused.value = true;
    }
}

function togglePlay() {
    if (isPlaying.value) {
        pauseSpeaking();
    } else {
        startSpeaking();
    }
}

function toggleSpeed() {
    const speeds = [1.0, 1.25, 1.5];
    const nextIdx = (speeds.indexOf(speed.value) + 1) % speeds.length;
    speed.value = speeds[nextIdx];
    if (isPlaying.value) {
        // Restart from current chunk at new speed
        isStopping = true;
        window.speechSynthesis.cancel();
        isStopping = false;
        isPlaying.value = true;
        isPaused.value = false;
        speakNextChunk();
    }
}

onBeforeUnmount(() => {
    if (supported) {
        isStopping = true;
        window.speechSynthesis.cancel();
    }
});
</script>

<template>
    <div v-if="supported" class="mt-4 flex items-center justify-between gap-3 rounded-2xl border border-brand-200/70 bg-gradient-to-r from-brand-50/40 via-white to-gray-50/40 p-3 sm:px-4 dark:border-brand-900/40 dark:from-brand-950/20 dark:via-gray-900/60 dark:to-gray-950/20 shadow-xs">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <!-- Play/Pause circle button -->
            <button
                type="button"
                :aria-label="isPlaying ? 'Pause reading' : 'Listen to article'"
                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-md transition hover:bg-brand-700 active:scale-95 focus:outline-none focus:ring-2 focus:ring-brand-500/50"
                @click="togglePlay"
            >
                <svg v-if="!isPlaying" class="size-4 fill-current ml-0.5" viewBox="0 0 24 24">
                    <polygon points="5 3 19 12 5 21 5 3"/>
                </svg>
                <svg v-else class="size-4 fill-current" viewBox="0 0 24 24">
                    <rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>
                </svg>
            </button>

            <!-- Text & Progress description -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-gray-800 dark:text-gray-200 flex items-center gap-1.5 truncate">
                        <span>{{ isPlaying ? 'Playing audio article...' : (isPaused ? 'Audio paused' : 'Listen to this article') }}</span>
                        <!-- Animated wave -->
                        <span v-if="isPlaying" class="inline-flex items-center gap-0.5">
                            <span class="size-1 rounded-full bg-brand-600 animate-bounce"></span>
                            <span class="size-1 rounded-full bg-brand-600 animate-bounce [animation-delay:0.15s]"></span>
                            <span class="size-1 rounded-full bg-brand-600 animate-bounce [animation-delay:0.3s]"></span>
                        </span>
                    </span>
                    <span v-if="progress > 0" class="text-gray-500 font-mono text-[11px] ml-2">
                        {{ progress }}%
                    </span>
                </div>

                <!-- Progress bar track -->
                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                    <div
                        class="h-full bg-brand-600 transition-all duration-300 dark:bg-brand-500"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Speed controller button -->
        <button
            type="button"
            class="shrink-0 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-bold text-gray-700 shadow-xs transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
            @click="toggleSpeed"
        >
            {{ speed }}x
        </button>
    </div>
</template>
