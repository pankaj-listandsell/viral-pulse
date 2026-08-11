<script setup>
import { ref } from 'vue';

const props = defineProps({
    action: { type: String, required: true },
});

const email = ref('');
const website = ref('');
const state = ref('idle');
const message = ref('');

async function submit() {
    if (state.value === 'sending') return;

    state.value = 'sending';
    message.value = '';

    try {
        const response = await fetch(props.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ email: email.value, website: website.value }),
        });

        const payload = await response.json();

        if (!response.ok) {
            state.value = 'error';
            message.value = Object.values(payload.errors ?? {}).flat()[0] ?? 'Something went wrong.';
            return;
        }

        state.value = 'done';
        message.value = payload.message;
        email.value = '';
    } catch {
        state.value = 'error';
        message.value = 'Could not reach the server. Please try again.';
    }
}
</script>

<template>
    <div>
        <form v-if="state !== 'done'" class="space-y-2" @submit.prevent="submit">
            <label class="sr-only" for="newsletter-island-email">Email address</label>
            <input
                id="newsletter-island-email"
                v-model="email"
                type="email"
                required
                placeholder="you@example.com"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
            >

            <!-- Honeypot -->
            <div class="hidden" aria-hidden="true">
                <input v-model="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <button
                type="submit"
                :disabled="state === 'sending'"
                class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700 disabled:opacity-60"
            >
                {{ state === 'sending' ? 'Subscribing…' : 'Subscribe' }}
            </button>
        </form>

        <p
            v-if="message"
            class="mt-2 text-sm"
            :class="state === 'error' ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400'"
            role="status"
        >
            {{ message }}
        </p>
    </div>
</template>
