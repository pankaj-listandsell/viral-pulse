<x-ui.card title="Search engine listing" subtitle="Leave blank to fall back to the title and excerpt.">
    <div
        class="space-y-4"
        x-data="{
            title: @js(old('seo_title', $post->seo_title ?? '')),
            description: @js(old('seo_description', $post->seo_description ?? '')),
            fallbackTitle: @js($post->title ?? ''),
            fallbackDescription: @js(Str::limit(strip_tags($post->excerpt ?? ''), 160)),
            get shownTitle() { return this.title || this.fallbackTitle || 'Your post title'; },
            get shownDescription() { return this.description || this.fallbackDescription || 'Your excerpt appears here.'; },
        }"
    >
        {{-- A live preview of the result Google will show, so the length limits
             mean something concrete rather than being abstract counters. --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                {{ rtrim(config('app.url'), '/') }}/post/{{ $post->slug ?? '…' }}
            </p>
            <p class="mt-0.5 truncate text-base text-blue-700 dark:text-blue-400" x-text="shownTitle"></p>
            <p class="mt-0.5 line-clamp-2 text-sm text-gray-600 dark:text-gray-300" x-text="shownDescription"></p>
        </div>

        <div>
            <div class="flex items-baseline justify-between">
                <x-ui.label for="seo_title">SEO title</x-ui.label>
                <span class="text-xs tabular-nums" :class="title.length > 60 ? 'text-amber-600' : 'text-gray-400'">
                    <span x-text="title.length"></span>/60
                </span>
            </div>
            <x-ui.input id="seo_title" name="seo_title" x-model="title" maxlength="255" :invalid="$errors->has('seo_title')" />
            <x-ui.error for="seo_title" />
        </div>

        <div>
            <div class="flex items-baseline justify-between">
                <x-ui.label for="seo_description">Meta description</x-ui.label>
                <span class="text-xs tabular-nums" :class="description.length > 160 ? 'text-amber-600' : 'text-gray-400'">
                    <span x-text="description.length"></span>/160
                </span>
            </div>
            <x-ui.textarea id="seo_description" name="seo_description" x-model="description" rows="2" maxlength="500"
                           :invalid="$errors->has('seo_description')" />
            <x-ui.error for="seo_description" />
        </div>

        <div>
            <x-ui.label for="seo_keywords">Keywords</x-ui.label>
            <x-ui.input id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords', $post->seo_keywords) }}"
                        placeholder="comma, separated" :invalid="$errors->has('seo_keywords')" />
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                Google ignores this tag. It is kept for other crawlers and for your own filtering.
            </p>
            <x-ui.error for="seo_keywords" />
        </div>

        <div>
            <x-ui.label for="canonical_url">Canonical URL</x-ui.label>
            <x-ui.input id="canonical_url" name="canonical_url" type="url"
                        value="{{ old('canonical_url', $post->canonical_url) }}"
                        placeholder="https://example.com/original-article"
                        :invalid="$errors->has('canonical_url')" />
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                Only set this if the article was first published elsewhere.
            </p>
            <x-ui.error for="canonical_url" />
        </div>
    </div>
</x-ui.card>
