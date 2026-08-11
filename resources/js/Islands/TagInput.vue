<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    name: { type: String, default: 'tags' },
    options: { type: Array, default: () => [] },
    value: { type: Array, default: () => [] },
    max: { type: Number, default: 15 },
});

const selected = ref([...props.value]);
const draft = ref('');

const suggestions = computed(() => {
    const term = draft.value.trim().toLowerCase();
    if (!term) return [];

    return props.options
        .filter((option) => option.toLowerCase().includes(term) && !selected.value.includes(option))
        .slice(0, 6);
});

function add(tag) {
    const name = (tag ?? draft.value).trim();

    if (!name || selected.value.length >= props.max) return;
    // Case-insensitive so "Cricket" and "cricket" do not both get created.
    if (selected.value.some((existing) => existing.toLowerCase() === name.toLowerCase())) {
        draft.value = '';
        return;
    }

    selected.value.push(name);
    draft.value = '';
}

function remove(index) {
    selected.value.splice(index, 1);
}

function onBackspace() {
    if (!draft.value && selected.value.length) {
        selected.value.pop();
    }
}
</script>

<template>
    <div>
        <div class="flex flex-wrap gap-1.5 rounded-lg border border-gray-300 p-2 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/40 dark:border-gray-700">
            <span
                v-for="(tag, index) in selected"
                :key="tag"
                class="inline-flex items-center gap-1 rounded-full bg-gray-100 py-0.5 pr-1 pl-2 text-xs font-medium dark:bg-gray-800"
            >
                {{ tag }}
                <button type="button" class="rounded-full px-1 text-gray-400 hover:text-red-600"
                        :aria-label="`Remove ${tag}`" @click="remove(index)">✕</button>
            </span>

            <input
                v-model="draft"
                type="text"
                :placeholder="selected.length ? '' : 'Type and press Enter'"
                class="min-w-24 flex-1 border-0 bg-transparent px-1 py-0.5 text-sm focus:outline-none"
                :disabled="selected.length >= max"
                @keydown.enter.prevent="add()"
                @keydown.,.prevent="add()"
                @keydown.backspace="onBackspace"
            >
        </div>

        <ul v-if="suggestions.length" class="mt-1.5 flex flex-wrap gap-1.5">
            <li v-for="option in suggestions" :key="option">
                <button
                    type="button"
                    class="rounded-full border border-gray-200 px-2 py-0.5 text-xs transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-700"
                    @click="add(option)"
                >{{ option }}</button>
            </li>
        </ul>

        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            {{ selected.length }}/{{ max }} · new tags are created automatically
        </p>

        <input v-for="tag in selected" :key="`field-${tag}`" type="hidden" :name="`${name}[]`" :value="tag">
    </div>
</template>
