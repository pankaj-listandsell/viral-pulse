<script setup>
import { ref, onMounted } from 'vue';

const isVisible = ref(false);
const isSupported = typeof window !== 'undefined' && 'Notification' in window;
const isSubscribed = ref(false);

function checkPermission() {
    if (!isSupported) return;

    if (Notification.permission === 'granted') {
        isSubscribed.value = true;
        isVisible.value = false;
    } else if (Notification.permission === 'default') {
        const dismissedAt = localStorage.getItem('viral_push_dismissed_at');
        const now = Date.now();
        // Prompt again only after 24 hours if dismissed
        if (!dismissedAt || now - parseInt(dismissedAt, 10) > 24 * 60 * 60 * 1000) {
            setTimeout(() => {
                isVisible.value = true;
            }, 4000);
        }
    }
}

async function subscribe() {
    if (!isSupported) return;

    try {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            isSubscribed.value = true;
            isVisible.value = false;
            localStorage.setItem('viral_push_subscribed', 'true');

            // Send a welcome notification
            new Notification('ViralPlush ⚡', {
                body: 'Thanks for subscribing! You will receive instant breaking news updates.',
                icon: '/favicon.ico',
            });
        } else {
            dismiss();
        }
    } catch {
        dismiss();
    }
}

function dismiss() {
    isVisible.value = false;
    localStorage.setItem('viral_push_dismissed_at', String(Date.now()));
}

onMounted(() => {
    checkPermission();
});
</script>

<template>
    <div
        v-if="isSupported && isVisible && !isSubscribed"
        class="fixed bottom-6 left-6 z-50 max-w-sm rounded-2xl border border-gray-200/90 bg-white p-4 shadow-2xl backdrop-blur-md dark:border-gray-800 dark:bg-gray-900/95 transition-all duration-300 animate-in fade-in slide-in-from-bottom-4"
    >
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-400">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <h4 class="text-xs font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                    <span>⚡ Breaking News Alerts</span>
                </h4>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                    Get instant notifications on your device when viral stories break.
                </p>

                <div class="mt-3 flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-brand-700 active:scale-95"
                        @click="subscribe"
                    >
                        Allow Alerts
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:text-gray-700 dark:border-gray-800 dark:text-gray-400 dark:hover:text-gray-200 transition"
                        @click="dismiss"
                    >
                        Later
                    </button>
                </div>
            </div>

            <button
                type="button"
                aria-label="Close"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                @click="dismiss"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</template>
