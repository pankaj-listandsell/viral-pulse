<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    postId: { type: [Number, String], required: true },
    title: { type: String, default: '' },
    question: { type: String, default: '' },
});

const pollQuestion = computed(() => {
    if (props.question) return props.question;
    if (props.title) return `What is your take on "${props.title}"?`;
    return 'What is your opinion on this story?';
});

const options = ref([
    { id: 'yes', text: '👍 Yes, Agree', count: 0, percent: 0 },
    { id: 'no', text: '👎 No, Disagree', count: 0, percent: 0 },
    { id: 'neutral', text: '🤔 Neutral / Need More Info', count: 0, percent: 0 },
]);

const selectedOption = ref(null);
const totalVotes = ref(0);
const busy = ref(false);

const storageKey = computed(() => `poll_vote_${props.postId}`);

function calculatePercents() {
    const total = totalVotes.value;
    options.value = options.value.map(opt => ({
        ...opt,
        percent: total > 0 ? Math.round((opt.count / total) * 100) : 0,
    }));
}

async function loadPoll() {
    // Check localStorage first for instant display
    try {
        const localSaved = localStorage.getItem(storageKey.value);
        if (localSaved) {
            selectedOption.value = localSaved;
        }
    } catch {
        // Ignore localStorage errors
    }

    try {
        const res = await fetch(`/post/${props.postId}/poll`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (res.ok) {
            const data = await res.json();
            totalVotes.value = data.total || 0;
            if (data.options) {
                options.value = data.options;
            }
            if (data.userVote) {
                selectedOption.value = data.userVote;
                try {
                    localStorage.setItem(storageKey.value, data.userVote);
                } catch {}
            }
        }
    } catch {
        // Fallback
    }
}

async function vote(optionId) {
    if (selectedOption.value || busy.value) return;
    busy.value = true;

    // Instant Optimistic UI Update
    selectedOption.value = optionId;
    totalVotes.value++;
    options.value = options.value.map(opt => {
        if (opt.id === optionId) {
            return { ...opt, count: opt.count + 1 };
        }
        return opt;
    });
    calculatePercents();

    try {
        localStorage.setItem(storageKey.value, optionId);
    } catch {}

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value
            || '';

        const res = await fetch(`/post/${props.postId}/poll/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ option: optionId }),
        });

        if (res.ok) {
            const data = await res.json();
            totalVotes.value = data.total || 0;
            if (data.options) {
                options.value = data.options;
            }
            if (data.userVote) {
                selectedOption.value = data.userVote;
            }
        }
    } catch {
        // Server sync fallback
    } finally {
        busy.value = false;
    }
}

onMounted(() => {
    loadPoll();
});
</script>

<template>
    <div class="my-8 rounded-2xl border border-brand-200/80 bg-gradient-to-br from-brand-50/50 via-white to-gray-50/50 p-5 sm:p-6 shadow-sm dark:border-brand-900/40 dark:from-brand-950/20 dark:via-gray-900/60 dark:to-gray-950/20">
        <!-- Poll Header -->
        <div class="flex items-center justify-between gap-2 border-b border-gray-200/60 pb-3.5 dark:border-gray-800">
            <div class="flex items-center gap-2 font-bold text-sm text-gray-900 dark:text-white">
                <span class="flex size-7 items-center justify-center rounded-lg bg-brand-600 text-white text-xs shadow-xs">
                    🗳️
                </span>
                <span>Community Pulse &bull; Quick Poll</span>
            </div>

            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                {{ totalVotes.toLocaleString() }} {{ totalVotes === 1 ? 'vote' : 'votes' }}
            </span>
        </div>

        <!-- Poll Question -->
        <h4 class="mt-3.5 text-base sm:text-lg font-bold text-gray-900 dark:text-white leading-snug">
            {{ pollQuestion }}
        </h4>

        <!-- Poll Options -->
        <div class="mt-4 space-y-2.5">
            <div
                v-for="opt in options"
                :key="opt.id"
                class="relative overflow-hidden rounded-xl border transition-all duration-200 select-none"
                :class="[
                    selectedOption
                        ? (selectedOption === opt.id
                            ? 'border-brand-500 bg-brand-50/70 dark:border-brand-500 dark:bg-brand-950/40 ring-1 ring-brand-500/30'
                            : 'border-gray-200/80 bg-white/70 dark:border-gray-800 dark:bg-gray-900/60 opacity-80')
                        : 'border-gray-200 bg-white hover:border-brand-400 hover:bg-brand-50/30 dark:border-gray-800 dark:bg-gray-900 cursor-pointer active:scale-98'
                ]"
                @click="vote(opt.id)"
            >
                <!-- Percentage Fill Bar (when voted) -->
                <div
                    v-if="selectedOption"
                    class="absolute inset-y-0 left-0 bg-brand-200/50 dark:bg-brand-900/40 transition-all duration-700 ease-out"
                    :style="{ width: `${opt.percent}%` }"
                ></div>

                <!-- Option Text & Percentage Label -->
                <div class="relative z-10 flex items-center justify-between p-3.5 px-4 text-xs sm:text-sm font-semibold">
                    <span class="flex items-center gap-2 text-gray-800 dark:text-gray-200">
                        <span v-if="selectedOption === opt.id" class="text-brand-600 dark:text-brand-400 font-bold">✓</span>
                        <span>{{ opt.text }}</span>
                    </span>

                    <span v-if="selectedOption" class="font-mono text-xs font-bold text-brand-700 dark:text-brand-300">
                        {{ opt.percent }}% ({{ opt.count }})
                    </span>
                </div>
            </div>
        </div>

        <!-- Poll Footer -->
        <div class="mt-3.5 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
            <span>{{ selectedOption ? 'Thanks for voting!' : 'Tap an option above to cast your vote.' }}</span>
            <span v-if="selectedOption" class="font-semibold text-emerald-600 dark:text-emerald-400">
                ✓ Recorded in live database
            </span>
        </div>
    </div>
</template>
