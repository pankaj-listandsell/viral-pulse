<script setup>
import { computed, onMounted, ref } from 'vue';

/*
 * The picker on the horoscope page. Everything it shows is also rendered as
 * plain HTML further down the page, so this island is pure convenience: find
 * your sign from a birth date, read it without scrolling, share it.
 */
const props = defineProps({
    signs: { type: Object, required: true },
    todayHoroscopes: { type: Object, required: true },
    pageUrl: { type: String, default: () => (typeof window !== 'undefined' ? window.location.href : '') },
    compatibilityUrl: { type: String, default: '' },
    today: { type: String, default: '' },
});

const STORAGE_KEY = 'vp:zodiac-sign';

const selected = ref(null);
const element = ref('all');
const birthDate = ref('');
const finderError = ref('');
const copied = ref(false);

const elements = [
    { key: 'all', label: 'All 12', icon: '✦' },
    { key: 'Fire', label: 'Fire', icon: '🔥' },
    { key: 'Earth', label: 'Earth', icon: '🌿' },
    { key: 'Air', label: 'Air', icon: '💨' },
    { key: 'Water', label: 'Water', icon: '💧' },
];

const areas = [
    { key: 'love', label: 'Love', icon: '💖', bar: 'bg-rose-500' },
    { key: 'career', label: 'Career', icon: '💼', bar: 'bg-indigo-500' },
    { key: 'money', label: 'Money', icon: '💰', bar: 'bg-amber-500' },
    { key: 'health', label: 'Health', icon: '🌿', bar: 'bg-emerald-500' },
];

const visibleSigns = computed(() => Object.entries(props.signs)
    .filter(([, sign]) => element.value === 'all' || sign.element === element.value));

const reading = computed(() => (selected.value ? props.todayHoroscopes[selected.value] ?? null : null));
const sign = computed(() => (selected.value ? props.signs[selected.value] ?? null : null));

/* Circumference of the energy ring, so the dash offset is a plain percentage. */
const RING = 2 * Math.PI * 42;
const ringOffset = computed(() => RING - (RING * (reading.value?.score ?? 0)) / 100);

/**
 * Sun sign from a birth date. Ranges are 'MM-DD' strings, which compare
 * correctly as text; Capricorn is the one that wraps across the new year.
 */
function signFromDate(value) {
    const monthDay = value.slice(5);

    for (const [slug, entry] of Object.entries(props.signs)) {
        const [start, end] = entry.range ?? [];

        if (!start || !end) {
            continue;
        }

        const inRange = start <= end
            ? monthDay >= start && monthDay <= end
            : monthDay >= start || monthDay <= end;

        if (inRange) {
            return slug;
        }
    }

    return null;
}

function findFromBirthDate() {
    finderError.value = '';

    if (!birthDate.value) {
        finderError.value = 'Pick your date of birth first.';
        return;
    }

    const found = signFromDate(birthDate.value);

    if (!found) {
        finderError.value = 'That date did not match a sign — pick yours below.';
        return;
    }

    select(found);
}

function select(slug) {
    selected.value = slug;
    copied.value = false;

    try {
        window.localStorage.setItem(STORAGE_KEY, slug);
    } catch {
        // Private browsing: remembering the sign is a nicety, not a feature.
    }
}

function clear() {
    selected.value = null;
    finderError.value = '';
}

function shareText() {
    const h = reading.value;

    return `🔮 ${sign.value.name} horoscope for ${props.today || h.date}\n\n${h.overview}\n\n`
        + `🔢 Lucky number: ${h.lucky_number}\n🎨 Lucky colour: ${h.lucky_color}\n💫 Energy: ${h.score}%\n\n`
        + `Read yours: ${props.pageUrl}`;
}

function shareWhatsApp() {
    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(shareText())}`, '_blank', 'noopener');
}

async function copyReading() {
    try {
        await navigator.clipboard.writeText(shareText());
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        copied.value = false;
    }
}

onMounted(() => {
    // ?sign=leo opens straight on that reading, which is what a shared link
    // should do. The canonical stays parameter-free, so nothing is duplicated
    // in the index.
    const requested = new URLSearchParams(window.location.search).get('sign');

    if (requested && props.signs[requested]) {
        selected.value = requested;
        return;
    }

    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        if (stored && props.signs[stored]) {
            selected.value = stored;
        }
    } catch {
        // Ignore: no stored sign simply means the grid opens instead.
    }
});
</script>

<template>
    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900/50">

        <!-- Finder bar -->
        <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50/80 p-5 sm:flex-row sm:items-end sm:justify-between sm:p-6 dark:border-gray-800 dark:bg-gray-900/60">
            <div>
                <h3 class="text-lg font-black tracking-tight text-gray-900 dark:text-white">
                    Find your sign in one tap
                </h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Enter your date of birth, or pick a sign from the wheel below.
                </p>
            </div>

            <form class="flex flex-wrap items-center gap-2" @submit.prevent="findFromBirthDate">
                <label class="sr-only" for="zodiac-dob">Your date of birth</label>
                <input
                    id="zodiac-dob"
                    v-model="birthDate"
                    type="date"
                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                >
                <button
                    type="submit"
                    class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-black text-white transition hover:bg-violet-600 active:scale-95 dark:bg-white dark:text-gray-900 dark:hover:bg-violet-500 dark:hover:text-white"
                >
                    Find my sign
                </button>
            </form>
        </div>

        <p v-if="finderError" class="border-b border-amber-200 bg-amber-50 px-5 py-2.5 text-xs font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
            {{ finderError }}
        </p>

        <!-- Selected reading -->
        <div v-if="reading && sign" class="relative overflow-hidden p-5 sm:p-7">
            <div
                class="pointer-events-none absolute -right-24 -top-24 size-72 rounded-full opacity-20 blur-3xl"
                :style="{ backgroundColor: sign.color }"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img
                        :src="sign.image"
                        :alt="sign.name"
                        class="size-16 rounded-2xl border object-cover sm:size-20"
                        :style="{ borderColor: sign.color + '66' }"
                        width="80"
                        height="80"
                    >
                    <div>
                        <h3 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                            {{ sign.name }}
                        </h3>
                        <p class="mt-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ sign.vedic }} · {{ sign.dates }}
                        </p>
                        <p class="mt-2 flex flex-wrap gap-1.5">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ sign.element }}</span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ sign.quality }}</span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ sign.planet }}</span>
                        </p>
                    </div>
                </div>

                <!-- Energy ring -->
                <div class="relative size-24 shrink-0">
                    <svg class="size-full -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="8" class="text-gray-200 dark:text-gray-800" />
                        <circle
                            cx="50" cy="50" r="42" fill="none" stroke-width="8" stroke-linecap="round"
                            :stroke="sign.color"
                            :stroke-dasharray="264"
                            :stroke-dashoffset="ringOffset"
                        />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-black text-gray-900 dark:text-white">{{ reading.score }}%</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-gray-400">Energy</span>
                    </div>
                </div>
            </div>

            <p class="relative mt-5 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                {{ reading.overview }}
            </p>

            <!-- Life areas -->
            <dl class="relative mt-5 grid gap-3 sm:grid-cols-2">
                <div
                    v-for="area in areas"
                    :key="area.key"
                    class="rounded-2xl border border-gray-100 bg-gray-50/70 p-3.5 dark:border-gray-800 dark:bg-gray-900/60"
                >
                    <dt class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200">
                            <span aria-hidden="true">{{ area.icon }}</span> {{ area.label }}
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="h-1.5 w-14 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800" aria-hidden="true">
                                <span class="block h-full rounded-full transition-all duration-500" :class="area.bar" :style="{ width: reading.scores[area.key] + '%' }"></span>
                            </span>
                            <span class="w-8 text-right text-xs font-black text-gray-900 dark:text-white">{{ reading.scores[area.key] }}%</span>
                        </span>
                    </dt>
                    <dd class="mt-1.5 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ reading[area.key] }}</dd>
                </div>
            </dl>

            <!-- Lucky details -->
            <dl class="relative mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-gray-200 bg-gray-200 sm:grid-cols-4 dark:border-gray-800 dark:bg-gray-800">
                <div class="bg-white px-3 py-2.5 dark:bg-gray-900">
                    <dt class="text-[10px] font-black uppercase tracking-wider text-gray-400">Lucky number</dt>
                    <dd class="mt-0.5 text-sm font-black text-gray-900 dark:text-white">#{{ reading.lucky_number }}</dd>
                </div>
                <div class="bg-white px-3 py-2.5 dark:bg-gray-900">
                    <dt class="text-[10px] font-black uppercase tracking-wider text-gray-400">Lucky colour</dt>
                    <dd class="mt-0.5 truncate text-sm font-black text-gray-900 dark:text-white">{{ reading.lucky_color }}</dd>
                </div>
                <div class="bg-white px-3 py-2.5 dark:bg-gray-900">
                    <dt class="text-[10px] font-black uppercase tracking-wider text-gray-400">Lucky time</dt>
                    <dd class="mt-0.5 truncate text-sm font-black text-gray-900 dark:text-white">{{ reading.lucky_time }}</dd>
                </div>
                <div class="bg-white px-3 py-2.5 dark:bg-gray-900">
                    <dt class="text-[10px] font-black uppercase tracking-wider text-gray-400">Mood</dt>
                    <dd class="mt-0.5 truncate text-sm font-black text-gray-900 dark:text-white">{{ reading.mood }}</dd>
                </div>
            </dl>

            <!-- Actions -->
            <div class="relative mt-5 flex flex-wrap items-center gap-2">
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
                    @click="copyReading"
                >
                    {{ copied ? '✓ Copied' : 'Copy reading' }}
                </button>
                <a
                    v-if="compatibilityUrl"
                    :href="`${compatibilityUrl}?sign1=${sign.slug}&sign2=${sign.best_matches?.[0] ?? 'leo'}`"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 px-4 py-2.5 text-xs font-bold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10"
                >
                    <span aria-hidden="true">💖</span> Check love match
                </a>
                <button
                    type="button"
                    class="ml-auto text-xs font-bold text-gray-500 underline-offset-4 transition hover:text-violet-600 hover:underline dark:text-gray-400 dark:hover:text-violet-400"
                    @click="clear"
                >
                    Pick another sign
                </button>
            </div>
        </div>

        <!-- Sign grid -->
        <div v-else class="p-5 sm:p-6">
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <button
                    v-for="item in elements"
                    :key="item.key"
                    type="button"
                    class="rounded-full border px-3.5 py-1.5 text-xs font-bold transition"
                    :class="element === item.key
                        ? 'border-violet-500 bg-violet-600 text-white shadow-sm'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-violet-300 hover:text-violet-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:text-violet-400'"
                    @click="element = item.key"
                >
                    <span aria-hidden="true">{{ item.icon }}</span> {{ item.label }}
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <button
                    v-for="[slug, entry] in visibleSigns"
                    :key="slug"
                    type="button"
                    class="group relative flex flex-col items-center gap-2 overflow-hidden rounded-2xl border border-gray-200 bg-white p-3.5 text-center transition duration-300 hover:-translate-y-1 hover:border-violet-400 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-violet-500"
                    @click="select(slug)"
                >
                    <span class="absolute inset-x-0 top-0 h-1 opacity-70 transition group-hover:opacity-100" :style="{ backgroundColor: entry.color }" aria-hidden="true"></span>
                    <img
                        :src="entry.image"
                        :alt="entry.name"
                        class="size-16 rounded-full border border-gray-100 object-cover transition duration-300 group-hover:scale-105 dark:border-gray-800"
                        width="64"
                        height="64"
                        loading="lazy"
                    >
                    <span class="text-sm font-black text-gray-900 dark:text-white">{{ entry.name }}</span>
                    <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400">{{ entry.dates }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
