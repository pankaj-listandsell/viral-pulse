<script setup>
import { onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import MediaPickerDialog from './partials/MediaPickerDialog.vue';

const props = defineProps({
    name: { type: String, default: 'content' },
    value: { type: String, default: '' },
    mediaEndpoint: { type: String, required: true },
    uploadEndpoint: { type: String, required: true },
});

const html = ref(props.value);
const pickerOpen = ref(false);
const linkOpen = ref(false);
const linkHref = ref('');
const sourceOpen = ref(false);
const sourceHtml = ref('');

const editor = useEditor({
    content: props.value,
    extensions: [
        StarterKit.configure({
            // h1 belongs to the page title, not the body.
            heading: { levels: [2, 3, 4] },
            link: false,
        }),
        Link.configure({
            openOnClick: false,
            autolink: true,
            protocols: ['http', 'https', 'mailto'],
            HTMLAttributes: { rel: 'nofollow noopener', target: '_blank' },
        }),
        Image.configure({ HTMLAttributes: { class: 'rounded-lg' } }),
        Placeholder.configure({ placeholder: 'Write the story…' }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm dark:prose-invert max-w-none min-h-96 px-4 py-3 focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        html.value = editor.getHTML();
    },
});

onBeforeUnmount(() => editor.value?.destroy());

const wordCount = shallowRef(0);
watch(html, (value) => {
    const text = value.replace(/<[^>]*>/g, ' ').trim();
    wordCount.value = text ? text.split(/\s+/).length : 0;
}, { immediate: true });

function insertImage(media) {
    editor.value?.chain().focus().setImage({ src: media.url, alt: media.alt_text || media.name }).run();
    pickerOpen.value = false;
}

function openLink() {
    linkHref.value = editor.value?.getAttributes('link').href ?? '';
    linkOpen.value = true;
}

/*
 * Source mode.
 *
 * The articles on this site arrive as HTML - the AI writer emits it, and so
 * does anything pasted out of a code block. Dropped into the editor as plain
 * text, TipTap does the correct thing for a text paste and escapes it, so the
 * article publishes with <p> printed on the page instead of forming it. There
 * was no way in, which made that the easy mistake to make.
 */
function openSource() {
    sourceHtml.value = editor.value?.getHTML() ?? '';
    sourceOpen.value = true;
}

function applySource() {
    // Parsed as HTML, not inserted as text: the second argument tells TipTap
    // to run it through the schema, which is the whole point of this box.
    editor.value?.commands.setContent(sourceHtml.value, true);
    html.value = editor.value?.getHTML() ?? '';
    sourceOpen.value = false;
}

function applyLink() {
    const chain = editor.value?.chain().focus().extendMarkRange('link');

    if (!linkHref.value) {
        chain?.unsetLink().run();
    } else {
        chain?.setLink({ href: linkHref.value }).run();
    }

    linkOpen.value = false;
}

const buttons = [
    { key: 'bold', label: 'Bold', icon: 'B', cls: 'font-bold', run: (e) => e.chain().focus().toggleBold().run() },
    { key: 'italic', label: 'Italic', icon: 'I', cls: 'italic', run: (e) => e.chain().focus().toggleItalic().run() },
    { key: 'strike', label: 'Strikethrough', icon: 'S', cls: 'line-through', run: (e) => e.chain().focus().toggleStrike().run() },
];

const blocks = [
    { key: 'heading', attrs: { level: 2 }, label: 'H2', run: (e) => e.chain().focus().toggleHeading({ level: 2 }).run() },
    { key: 'heading', attrs: { level: 3 }, label: 'H3', run: (e) => e.chain().focus().toggleHeading({ level: 3 }).run() },
    { key: 'bulletList', label: '• List', run: (e) => e.chain().focus().toggleBulletList().run() },
    { key: 'orderedList', label: '1. List', run: (e) => e.chain().focus().toggleOrderedList().run() },
    { key: 'blockquote', label: 'Quote', run: (e) => e.chain().focus().toggleBlockquote().run() },
    { key: 'codeBlock', label: 'Code', run: (e) => e.chain().focus().toggleCodeBlock().run() },
];
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700">
        <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 p-1.5 dark:border-gray-800 dark:bg-gray-800/50">
            <button
                v-for="button in buttons"
                :key="button.key"
                type="button"
                :title="button.label"
                :aria-label="button.label"
                :aria-pressed="editor?.isActive(button.key) ?? false"
                class="size-8 rounded text-sm transition hover:bg-gray-200 dark:hover:bg-gray-700"
                :class="[button.cls, editor?.isActive(button.key) && 'bg-gray-200 dark:bg-gray-700']"
                @click="editor && button.run(editor)"
            >{{ button.icon }}</button>

            <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-700" />

            <button
                v-for="block in blocks"
                :key="block.label"
                type="button"
                class="rounded px-2 py-1 text-xs font-medium transition hover:bg-gray-200 dark:hover:bg-gray-700"
                :class="editor?.isActive(block.key, block.attrs) && 'bg-gray-200 dark:bg-gray-700'"
                @click="editor && block.run(editor)"
            >{{ block.label }}</button>

            <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-700" />

            <button type="button" class="rounded px-2 py-1 text-xs font-medium transition hover:bg-gray-200 dark:hover:bg-gray-700"
                    :class="editor?.isActive('link') && 'bg-gray-200 dark:bg-gray-700'" @click="openLink">Link</button>
            <button type="button" class="rounded px-2 py-1 text-xs font-medium transition hover:bg-gray-200 dark:hover:bg-gray-700"
                    @click="pickerOpen = true">Image</button>

            <span class="mx-1 h-5 w-px bg-gray-300 dark:bg-gray-700" />

            <button
                type="button"
                title="Paste or edit the article as HTML"
                class="rounded px-2 py-1 text-xs font-bold transition hover:bg-gray-200 dark:hover:bg-gray-700"
                :class="sourceOpen && 'bg-gray-200 dark:bg-gray-700'"
                @click="sourceOpen ? (sourceOpen = false) : openSource()"
            >&lt;/&gt; HTML</button>

            <span class="ml-auto pr-1.5 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                {{ wordCount }} words
            </span>
        </div>

        <div v-if="sourceOpen" class="border-b border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                Paste HTML here and press Apply — it is parsed, not printed. Pasting HTML straight into the editor
                above stores the tags as text, which is how <code>&lt;p&gt;</code> ends up visible on the published page.
            </p>

            <textarea
                v-model="sourceHtml"
                rows="14"
                spellcheck="false"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 font-mono text-xs leading-relaxed text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                placeholder="&lt;p&gt;Your article…&lt;/p&gt;"
            ></textarea>

            <div class="mt-2 flex items-center gap-2">
                <button type="button"
                        class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-brand-700"
                        @click="applySource">Apply</button>
                <button type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        @click="sourceOpen = false">Cancel</button>
            </div>
        </div>

        <div v-if="linkOpen" class="flex items-center gap-2 border-b border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-gray-900">
            <input
                v-model="linkHref"
                type="url"
                placeholder="https://example.com"
                class="flex-1 rounded-lg border border-gray-300 px-2.5 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                @keydown.enter.prevent="applyLink"
            >
            <button type="button" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white" @click="applyLink">
                Apply
            </button>
            <button type="button" class="rounded-lg px-2 py-1.5 text-sm text-gray-500" @click="linkOpen = false">Cancel</button>
        </div>

        <EditorContent :editor="editor" class="bg-white dark:bg-gray-900" />

        <!-- The value the form actually posts. Server-side sanitising still
             runs on it: nothing produced here is trusted. -->
        <input type="hidden" :name="name" :value="html">

        <MediaPickerDialog
            v-if="pickerOpen"
            :media-endpoint="mediaEndpoint"
            :upload-endpoint="uploadEndpoint"
            @select="insertImage"
            @close="pickerOpen = false"
        />
    </div>
</template>
