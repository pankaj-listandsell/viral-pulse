<script setup>
import { computed, ref } from 'vue';

/*
 * The compatibility calculator. Scores are not computed here: the four pairing
 * types come in from the server as `types`, so the number this island shows
 * after a select changes is the same number the page title, the table and the
 * server-rendered reading show.
 */
const props = defineProps({
    signs: { type: Object, required: true },
    types: { type: Object, required: true },
    initialSign1: { type: String, default: 'aries' },
    initialSign2: { type: String, default: 'leo' },
    baseUrl: { type: String, default: '' },
});

const sign1 = ref(props.initialSign1);
const sign2 = ref(props.initialSign2);
const copied = ref(false);

const s1 = computed(() => props.signs[sign1.value] ?? Object.values(props.signs)[0]);
const s2 = computed(() => props.signs[sign2.value] ?? Object.values(props.signs)[1]);

const COMPLEMENTARY = [['Fire', 'Air'], ['Air', 'Fire'], ['Earth', 'Water'], ['Water', 'Earth']];

const typeKey = computed(() => {
    if (sign1.value === sign2.value) {
        return 'twin';
    }

    if (s1.value.element === s2.value.element) {
        return 'element';
    }

    const complementary = COMPLEMENTARY
        .some(([a, b]) => s1.value.element === a && s2.value.element === b);

    return complementary ? 'complementary' : 'contrast';
});

/** Same placeholders the server fills, so both sides read identically. */
function fill(text) {
    return text
        .replaceAll('{s1}', s1.value.name)
        .replaceAll('{s2}', s2.value.name)
        .replaceAll('{e1}', s1.value.element)
        .replaceAll('{e2}', s2.value.element);
}

const match = computed(() => {
    const rule = props.types[typeKey.value];

    return {
        score: rule.score,
        title: rule.title,
        summary: fill(rule.summary),
        advice: rule.advice,
        scores: rule.scores,
    };
});

/* Three headline numbers here; the full five are in the page's own reading. */
const headline = [
    { key: 'love', label: 'Love', icon: '💖' },
    { key: 'friendship', label: 'Friends', icon: '🤝' },
    { key: 'communication', label: 'Talk', icon: '💬' },
];

const isCurrentPair = computed(
    () => sign1.value === props.initialSign1 && sign2.value === props.initialSign2,
);

const tone = computed(() => {
    const score = match.value.score;

    if (score >= 94) return 'text-emerald-600 dark:text-emerald-400';
    if (score >= 90) return 'text-violet-600 dark:text-violet-400';
    if (score >= 85) return 'text-sky-600 dark:text-sky-400';

    return 'text-amber-600 dark:text-amber-400';
});

const pairUrl = computed(() => `${props.baseUrl}?sign1=${sign1.value}&sign2=${sign2.value}`);

/* The score ring, drawn as a single arc. */
const RING = 2 * Math.PI * 44;
const ringOffset = computed(() => RING - (RING * match.value.score) / 100);

function swap() {
    const held = sign1.value;
    sign1.value = sign2.value;
    sign2.value = held;
}

function shareWhatsApp() {
    const m = match.value;
    const text = `💞 ${s1.value.name} + ${s2.value.name}: ${m.score}% match (${m.title})\n\n`
        + `💖 Love ${m.scores.love}%  🤝 Friendship ${m.scores.friendship}%  💬 Communication ${m.scores.communication}%\n\n`
        + `${m.summary}\n\nCheck your own match: ${pairUrl.value}`;

    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`, '_blank', 'noopener');
}

async function copyLink() {
    try {
        await navigator.clipboard.writeText(pairUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        copied.value = false;
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900/50">

        <!-- Selectors -->
        <div class="grid gap-4 border-b border-gray-200 bg-gray-50/80 p-5 sm:p-6 md:grid-cols-[1fr_auto_1fr] md:items-end dark:border-gray-800 dark:bg-gray-900/60">
            <div>
                <label :for="'sign1-select'" class="mb-2 block text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Your sign
                </label>
                <div class="flex items-center gap-3">
                    <img
                        :src="s1.image"
                        :alt="s1.name"
                        class="size-12 shrink-0 rounded-full border-2 object-cover"
                        :style="{ borderColor: s1.color + '80' }"
                        width="48"
                        height="48"
                    >
                    <select
                        id="sign1-select"
                        v-model="sign1"
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                        <option v-for="(entry, slug) in signs" :key="slug" :value="slug">
                            {{ entry.symbol }} {{ entry.name }} · {{ entry.element }}
                        </option>
                    </select>
                </div>
            </div>

            <button
                type="button"
                class="mx-auto flex size-11 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-500 transition hover:border-violet-400 hover:text-violet-600 active:scale-95 md:mb-1 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400"
                title="Swap the two signs"
                @click="swap"
            >
                <span aria-hidden="true">⇄</span>
                <span class="sr-only">Swap the two signs</span>
            </button>

            <div>
                <label :for="'sign2-select'" class="mb-2 block text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Their sign
                </label>
                <div class="flex items-center gap-3">
                    <img
                        :src="s2.image"
                        :alt="s2.name"
                        class="size-12 shrink-0 rounded-full border-2 object-cover"
                        :style="{ borderColor: s2.color + '80' }"
                        width="48"
                        height="48"
                    >
                    <select
                        id="sign2-select"
                        v-model="sign2"
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 shadow-sm focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                        <option v-for="(entry, slug) in signs" :key="slug" :value="slug">
                            {{ entry.symbol }} {{ entry.name }} · {{ entry.element }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Result -->
        <div class="grid gap-8 p-5 sm:p-7 lg:grid-cols-[auto_1fr]">
            <div class="flex flex-col items-center">
                <div class="relative size-40">
                    <svg class="size-full -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                        <circle cx="50" cy="50" r="44" fill="none" stroke="currentColor" stroke-width="7" class="text-gray-200 dark:text-gray-800" />
                        <circle
                            cx="50" cy="50" r="44" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round"
                            class="transition-all duration-500"
                            :class="tone"
                            :stroke-dasharray="277"
                            :stroke-dashoffset="ringOffset"
                        />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-gray-900 dark:text-white">{{ match.score }}%</span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Match</span>
                    </div>
                </div>
                <p class="mt-2 text-center text-sm font-black" :class="tone">{{ match.title }}</p>
                <p class="mt-0.5 text-center text-xs text-gray-500 dark:text-gray-400">
                    {{ s1.name }} &amp; {{ s2.name }}
                </p>
            </div>

            <div class="flex flex-col justify-center">
                <p class="text-base leading-relaxed text-gray-700 dark:text-gray-300">{{ match.summary }}</p>

                <dl class="mt-5 grid grid-cols-3 gap-3 border-y border-gray-100 py-4 dark:border-gray-800">
                    <div v-for="meter in headline" :key="meter.key">
                        <dt class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <span aria-hidden="true">{{ meter.icon }}</span> {{ meter.label }}
                        </dt>
                        <dd class="mt-1 text-xl font-black text-gray-900 dark:text-white">{{ match.scores[meter.key] }}%</dd>
                    </div>
                </dl>

                <!-- The detailed breakdown lives in the page itself, so this
                     points at it rather than repeating it. -->
                <p v-if="!isCurrentPair" class="mt-4 text-xs font-semibold text-amber-700 dark:text-amber-400">
                    You are previewing {{ s1.name }} &amp; {{ s2.name }} — open the full reading for the complete breakdown.
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <a
                        :href="isCurrentPair ? '#result-heading' : pairUrl"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-4 py-2.5 text-xs font-black text-white transition hover:bg-violet-600 active:scale-95 dark:bg-white dark:text-gray-900 dark:hover:bg-violet-500 dark:hover:text-white"
                    >
                        <template v-if="isCurrentPair">Full breakdown <span aria-hidden="true">↓</span></template>
                        <template v-else>Open {{ s1.name }} &amp; {{ s2.name }} <span aria-hidden="true">→</span></template>
                    </a>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-emerald-500 active:scale-95"
                        @click="shareWhatsApp"
                    >
                        <span aria-hidden="true">📲</span> Share on WhatsApp
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-bold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="copyLink"
                    >
                        {{ copied ? '✓ Link copied' : 'Copy link' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
