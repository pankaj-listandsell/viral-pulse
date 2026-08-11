@php
    use App\Enums\PostStatus;

    $editing = $post->exists;
    $editorProps = [
        'name' => 'content',
        'value' => old('content', $post->content ?? ''),
        'mediaEndpoint' => route('admin.media.index'),
        'uploadEndpoint' => route('admin.media.store'),
    ];
    $imageProps = [
        'name' => 'featured_image',
        'value' => old('featured_image', $post->featured_image),
        'mediaEndpoint' => route('admin.media.index'),
        'uploadEndpoint' => route('admin.media.store'),
    ];
    $tagProps = [
        'name' => 'tags',
        'options' => $tags->pluck('name'),
        'value' => old('tags', $selectedTags),
    ];
@endphp

<div class="grid gap-4 lg:grid-cols-3">

    {{-- Main column --}}
    <div class="space-y-4 lg:col-span-2">
        <x-ui.card>
            <div
                x-data="{
                    title: @js(old('title', $post->title ?? '')),
                    slug: @js(old('slug', $post->slug ?? '')),
                    touched: @js($editing || filled(old('slug'))),
                    slugify(value) {
                        return value.toLowerCase().trim()
                            .replace(/[^\w\s-]/g, '')
                            .replace(/[\s_]+/g, '-')
                            .replace(/^-+|-+$/g, '')
                            .slice(0, 180);
                    },
                }"
                x-effect="if (!touched) slug = slugify(title)"
                class="space-y-4"
            >
                <div>
                    <x-ui.label for="title" required>Title</x-ui.label>
                    <x-ui.input
                        id="title"
                        name="title"
                        x-model="title"
                        placeholder="What is this story about?"
                        required
                        autofocus
                        :invalid="$errors->has('title')"
                        class="text-base"
                    />
                    <x-ui.error for="title" />
                </div>

                <div>
                    <x-ui.label for="slug">URL slug</x-ui.label>
                    <div class="flex items-center gap-2">
                        <span class="shrink-0 text-sm text-gray-400">/post/</span>
                        <x-ui.input
                            id="slug"
                            name="slug"
                            x-model="slug"
                            @input="touched = true"
                            :invalid="$errors->has('slug')"
                        />
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Generated from the title. Edit it before publishing — changing a live URL loses its search ranking.
                    </p>
                    <x-ui.error for="slug" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Content">
            <div data-island="PostEditor" data-island-eager data-props="{{ json_encode($editorProps) }}">
                {{-- Fallback if JavaScript does not load: a plain textarea still works. --}}
                <textarea
                    name="content"
                    rows="18"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm
                           dark:border-gray-700 dark:bg-gray-900"
                >{{ old('content', $post->content ?? '') }}</textarea>
            </div>
            <x-ui.error for="content" class="mt-2" />
        </x-ui.card>

        <x-ui.card title="Excerpt" subtitle="Shown in listings and used as the fallback meta description.">
            <x-ui.textarea
                name="excerpt"
                rows="3"
                maxlength="500"
                placeholder="One or two sentences summarising the story."
                :invalid="$errors->has('excerpt')"
            >{{ old('excerpt', $post->excerpt) }}</x-ui.textarea>
            <x-ui.error for="excerpt" />
        </x-ui.card>

        @include('admin.posts.partials.seo-panel')
    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">
        <x-ui.card title="Publish">
            <div class="space-y-4" x-data="{ status: @js(old('status', ($post->status ?? PostStatus::Draft)->value)) }">
                <div>
                    <x-ui.label for="status" required>Status</x-ui.label>
                    <x-ui.select id="status" name="status" x-model="status" :invalid="$errors->has('status')">
                        @foreach(PostStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error for="status" />
                </div>

                <div x-show="status === 'scheduled'" x-cloak>
                    <x-ui.label for="scheduled_at" required>Publish at</x-ui.label>
                    <x-ui.input
                        id="scheduled_at"
                        name="scheduled_at"
                        type="datetime-local"
                        value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}"
                        :invalid="$errors->has('scheduled_at')"
                    />
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Times are {{ config('app.timezone') }}. The scheduler must be running for this to fire.
                    </p>
                    <x-ui.error for="scheduled_at" />
                </div>

                <div x-show="status === 'published'" x-cloak>
                    <x-ui.label for="published_at">Published date</x-ui.label>
                    <x-ui.input
                        id="published_at"
                        name="published_at"
                        type="datetime-local"
                        value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
                        :invalid="$errors->has('published_at')"
                    />
                    <x-ui.error for="published_at" />
                </div>

                <div class="space-y-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <x-ui.toggle
                        name="is_featured"
                        :checked="(bool) old('is_featured', $post->is_featured)"
                        label="Featured"
                        hint="Highlighted on the home page."
                    />
                    <x-ui.toggle
                        name="is_trending"
                        :checked="(bool) old('is_trending', $post->is_trending)"
                        label="Trending"
                        hint="Shown in the trending rail."
                    />
                </div>

                <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <x-ui.button type="submit" class="flex-1">
                        {{ $editing ? 'Save changes' : 'Create post' }}
                    </x-ui.button>
                    @if($editing)
                        <x-ui.button variant="secondary" :href="route('admin.posts.index')">Close</x-ui.button>
                    @endif
                </div>

                @if($editing && $post->ai_generated)
                    <p class="flex items-start gap-2 rounded-lg bg-violet-50 p-2.5 text-xs text-violet-800
                              dark:bg-violet-500/10 dark:text-violet-300">
                        <x-icon name="bot" class="mt-px size-4 shrink-0" />
                        AI generated. Read it through before publishing — you are responsible for what goes live.
                    </p>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card title="Category">
            <x-ui.label for="category_id" class="sr-only">Category</x-ui.label>
            <x-ui.select id="category_id" name="category_id" required :invalid="$errors->has('category_id')">
                <option value="">Choose a category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $post->category_id) === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </x-ui.select>
            <x-ui.error for="category_id" />

            <div class="mt-4">
                <x-ui.label for="language">Language</x-ui.label>
                <x-ui.select id="language" name="language" :invalid="$errors->has('language')">
                    @foreach(['en' => 'English', 'hi' => 'Hindi'] as $code => $label)
                        <option value="{{ $code }}" @selected(old('language', $post->language ?? 'en') === $code)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.error for="language" />
            </div>
        </x-ui.card>

        <x-ui.card title="Tags">
            <div data-island="TagInput" data-island-eager data-props="{{ json_encode($tagProps) }}">
                @foreach(old('tags', $selectedTags) as $tag)
                    <input type="hidden" name="tags[]" value="{{ $tag }}">
                @endforeach
                <p class="text-sm text-gray-500 dark:text-gray-400">Loading tag picker…</p>
            </div>
            <x-ui.error for="tags" />
        </x-ui.card>

        <x-ui.card title="Featured image">
            <div data-island="ImagePicker" data-island-eager data-props="{{ json_encode($imageProps) }}">
                <input type="hidden" name="featured_image" value="{{ old('featured_image', $post->featured_image) }}">
                <p class="text-sm text-gray-500 dark:text-gray-400">Loading image picker…</p>
            </div>

            <div class="mt-3">
                <x-ui.label for="featured_image_alt">Alt text</x-ui.label>
                <x-ui.input
                    id="featured_image_alt"
                    name="featured_image_alt"
                    value="{{ old('featured_image_alt', $post->featured_image_alt) }}"
                    placeholder="Describe the image for screen readers"
                    :invalid="$errors->has('featured_image_alt')"
                />
                <x-ui.error for="featured_image_alt" />
            </div>
        </x-ui.card>
    </div>
</div>
