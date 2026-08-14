<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    postId: { type: [Number, String], required: true },
});

const reactions = ref([
    { id: 'fire', emoji: '🔥', label: 'Trending', count: 0 },
    { id: 'insight', emoji: '💡', label: 'Insightful', count: 0 },
    { id: 'shock', emoji: '😲', label: 'Shocking', count: 0 },
    { id: 'love', emoji: '❤️', label: 'Love', count: 0 },
]);

const userReaction = ref(null);
const busy = ref(false);
const particles = ref([]);

async function loadReactions() {
    try {
        const res = await fetch(`/post/${props.postId}/reactions`, {
            headers: { Accept: 'application/json' }
        });
        if (res.ok) {
            const data = await res.json();
            if (data.reactions) {
                reactions.value.forEach(r => {
                    if (data.reactions[r.id] !== undefined) {
                        r.count = data.reactions[r.id];
                    }
                });
            }
            userReaction.value = data.userReaction || null;
        }
    } catch {
        // Fallback
    }
}

function spawnEmojiBurst(targetEl, emoji) {
    if (!targetEl) return;
    const rect = targetEl.getBoundingClientRect();
    const originX = rect.left + rect.width / 2;
    const originY = rect.top + rect.height / 3;

    const sparkles = ['✨', '⭐', '💥', '🎉'];
    const burstId = Date.now();
    const count = 16;

    for (let i = 0; i < count; i++) {
        const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.5;
        const distance = 80 + Math.random() * 120;
        const targetX = Math.cos(angle) * distance;
        const targetY = Math.sin(angle) * distance - 80;
        const particleEmoji = i % 3 === 0 ? sparkles[i % sparkles.length] : emoji;
        const rot = (Math.random() - 0.5) * 90;
        const duration = 0.7 + Math.random() * 0.4;
        const size = i % 2 === 0 ? 'text-2xl' : 'text-lg';

        const p = {
            id: `${burstId}-${i}`,
            emoji: particleEmoji,
            x: originX,
            y: originY,
            tx: targetX,
            ty: targetY,
            rot,
            duration,
            size,
        };

        particles.value.push(p);

        setTimeout(() => {
            particles.value = particles.value.filter(item => item.id !== p.id);
        }, duration * 1000 + 100);
    }
}

async function react(event, reactionId) {
    if (busy.value) return;
    busy.value = true;

    const targetObj = reactions.value.find(r => r.id === reactionId);
    const prevReaction = userReaction.value;

    // Optimistic UI update
    if (prevReaction === reactionId) {
        if (targetObj && targetObj.count > 0) targetObj.count--;
        userReaction.value = null;
    } else {
        if (prevReaction) {
            const prevObj = reactions.value.find(r => r.id === prevReaction);
            if (prevObj && prevObj.count > 0) prevObj.count--;
        }
        if (targetObj) targetObj.count++;
        userReaction.value = reactionId;
        if (targetObj) {
            spawnEmojiBurst(event.currentTarget, targetObj.emoji);
        }
    }

    try {
        const res = await fetch(`/post/${props.postId}/react`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ reaction: reactionId }),
        });

        if (res.ok) {
            const data = await res.json();
            if (data.reactions) {
                reactions.value.forEach(r => {
                    if (data.reactions[r.id] !== undefined) {
                        r.count = data.reactions[r.id];
                    }
                });
            }
            userReaction.value = data.userReaction;
        }
    } catch {
        // Revert on failure
        loadReactions();
    } finally {
        busy.value = false;
    }
}

onMounted(() => {
    loadReactions();
});
</script>

<template>
    <div class="relative my-8 rounded-2xl border border-gray-200/80 bg-white/80 p-5 shadow-sm backdrop-blur-xs dark:border-gray-800 dark:bg-gray-900/60">
        <div class="flex items-center justify-between gap-2 mb-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="text-base">💬</span>
                <span>What's your reaction to this story?</span>
            </h3>
            <span v-if="userReaction" class="text-[11px] font-bold text-brand-600 dark:text-brand-400 animate-pulse">
                You reacted!
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <button
                v-for="r in reactions"
                :key="r.id"
                type="button"
                :aria-pressed="userReaction === r.id"
                :disabled="busy"
                :class="[
                    'relative overflow-hidden flex flex-col items-center justify-center p-3.5 rounded-xl border transition-all duration-200 group active:scale-90 focus:outline-none select-none',
                    userReaction === r.id
                        ? 'border-brand-500 bg-brand-50/90 dark:border-brand-500 dark:bg-brand-950/50 shadow-md scale-102 ring-2 ring-brand-500/30'
                        : 'border-gray-200/90 bg-white hover:border-brand-300 hover:bg-brand-50/30 hover:scale-103 dark:border-gray-800 dark:bg-gray-900/80 dark:hover:border-brand-700 dark:hover:bg-gray-800/60'
                ]"
                @click="react($event, r.id)"
            >
                <span
                    :class="[
                        'text-3xl mb-1.5 transition-transform duration-200 group-hover:scale-125 inline-block',
                        userReaction === r.id ? 'animate-bounce' : ''
                    ]"
                >
                    {{ r.emoji }}
                </span>
                <span :class="['text-xs font-bold', userReaction === r.id ? 'text-brand-700 dark:text-brand-300' : 'text-gray-700 dark:text-gray-300']">
                    {{ r.label }}
                </span>
                <span class="text-[11px] font-mono font-semibold text-gray-400 dark:text-gray-500 mt-0.5">
                    {{ r.count.toLocaleString() }}
                </span>
            </button>
        </div>

        <!-- Flying Floating Emoji Particles Overlay -->
        <Teleport to="body">
            <div class="fixed inset-0 pointer-events-none z-50 overflow-hidden">
                <div
                    v-for="p in particles"
                    :key="p.id"
                    :class="['fixed select-none emoji-particle font-bold', p.size]"
                    :style="{
                        left: `${p.x}px`,
                        top: `${p.y}px`,
                        '--tx': `${p.tx}px`,
                        '--ty': `${p.ty}px`,
                        '--rot': `${p.rot}deg`,
                        '--dur': `${p.duration}s`,
                    }"
                >
                    {{ p.emoji }}
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes emojiFloatBurst {
    0% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(0.6) rotate(0deg);
    }
    50% {
        opacity: 1;
        transform: translate(calc(-50% + var(--tx) * 0.7), calc(-50% + var(--ty) * 0.7)) scale(1.4) rotate(var(--rot));
    }
    100% {
        opacity: 0;
        transform: translate(calc(-50% + var(--tx)), calc(-50% + var(--ty))) scale(0.8) rotate(calc(var(--rot) * 1.5));
    }
}

.emoji-particle {
    animation: emojiFloatBurst var(--dur) cubic-bezier(0.22, 1, 0.36, 1) forwards;
    will-change: transform, opacity;
}
</style>
