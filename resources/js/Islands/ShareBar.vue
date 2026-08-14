<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    url: { type: String, required: true },
    title: { type: String, required: true },
});

const copied = ref(false);

const whatsappUrl = computed(() => {
    const text = encodeURIComponent(`🔥 *${props.title}*\n\nRead full story here:\n${props.url}`);
    return `https://api.whatsapp.com/send?text=${text}`;
});

const telegramUrl = computed(() => {
    const text = encodeURIComponent(props.title);
    const url = encodeURIComponent(props.url);
    return `https://t.me/share/url?url=${url}&text=${text}`;
});

const twitterUrl = computed(() => {
    const text = encodeURIComponent(props.title);
    const url = encodeURIComponent(props.url);
    return `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
});

const facebookUrl = computed(() => {
    const url = encodeURIComponent(props.url);
    return `https://www.facebook.com/sharer/sharer.php?u=${url}`;
});

const canShareNatively = typeof navigator !== 'undefined' && !!navigator.share;

async function shareNatively() {
    try {
        await navigator.share({ title: props.title, text: props.title, url: props.url });
    } catch {
        // Dismissed
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
    <div class="rounded-2xl border border-gray-200/80 bg-gray-50/50 p-4 sm:p-5 dark:border-gray-800 dark:bg-gray-900/40">
        <div class="flex items-center justify-between gap-2 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                <svg class="size-4 text-brand-600 dark:text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/>
                </svg>
                Share this viral story
            </span>
            <span v-if="copied" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 animate-fade-in">
                ✓ Link copied to clipboard!
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <!-- WhatsApp button -->
            <a
                :href="whatsappUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-white shadow-sm transition hover:bg-emerald-700 active:scale-95"
            >
                <svg class="size-4 fill-current" viewBox="0 0 24 24">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.044c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/>
                </svg>
                WhatsApp
            </a>

            <!-- Telegram button -->
            <a
                :href="telegramUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-xl bg-[#0088cc] px-3.5 py-2 text-white shadow-sm transition hover:bg-[#0077b5] active:scale-95"
            >
                <svg class="size-4 fill-current" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                </svg>
                Telegram
            </a>

            <!-- X / Twitter -->
            <a
                :href="twitterUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-gray-700 shadow-sm transition hover:border-gray-400 hover:text-black dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-gray-600"
            >
                <svg class="size-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                Share
            </a>

            <!-- Native Share Button (Mobile) -->
            <button
                v-if="canShareNatively"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-3.5 py-2 text-white shadow-sm transition hover:bg-brand-700"
                @click="shareNatively"
            >
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                More
            </button>

            <!-- Copy Link Button -->
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-gray-700 shadow-sm transition hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                @click="copy"
            >
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                {{ copied ? 'Copied' : 'Copy link' }}
            </button>
        </div>
    </div>
</template>

