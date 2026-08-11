<div class="grid max-w-4xl gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <x-ui.card>
            <div
                class="space-y-4"
                x-data="{
                    name: @js(old('name', $category->name ?? '')),
                    slug: @js(old('slug', $category->slug ?? '')),
                    touched: @js($category->exists || filled(old('slug'))),
                    slugify: (v) => v.toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/^-+|-+$/g, ''),
                }"
                x-effect="if (!touched) slug = slugify(name)"
            >
                <div>
                    <x-ui.label for="name" required>Name</x-ui.label>
                    <x-ui.input id="name" name="name" x-model="name" required autofocus :invalid="$errors->has('name')" />
                    <x-ui.error for="name" />
                </div>

                <div>
                    <x-ui.label for="slug">Slug</x-ui.label>
                    <x-ui.input id="slug" name="slug" x-model="slug" @input="touched = true" :invalid="$errors->has('slug')" />
                    <x-ui.error for="slug" />
                </div>

                <div>
                    <x-ui.label for="description">Description</x-ui.label>
                    <x-ui.textarea id="description" name="description" rows="3" :invalid="$errors->has('description')"
                    >{{ old('description', $category->description) }}</x-ui.textarea>
                    <x-ui.error for="description" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Search engine listing">
            <div class="space-y-4">
                <div>
                    <x-ui.label for="seo_title">SEO title</x-ui.label>
                    <x-ui.input id="seo_title" name="seo_title" value="{{ old('seo_title', $category->seo_title) }}"
                                :invalid="$errors->has('seo_title')" />
                    <x-ui.error for="seo_title" />
                </div>
                <div>
                    <x-ui.label for="seo_description">Meta description</x-ui.label>
                    <x-ui.textarea id="seo_description" name="seo_description" rows="2"
                                   :invalid="$errors->has('seo_description')"
                    >{{ old('seo_description', $category->seo_description) }}</x-ui.textarea>
                    <x-ui.error for="seo_description" />
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="space-y-4">
        <x-ui.card title="Settings">
            <div class="space-y-4">
                <div>
                    <x-ui.label for="parent_id">Parent category</x-ui.label>
                    <x-ui.select id="parent_id" name="parent_id" :invalid="$errors->has('parent_id')">
                        <option value="">None (top level)</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error for="parent_id" />
                </div>

                <div>
                    <x-ui.label for="color">Colour</x-ui.label>
                    <div class="flex gap-2">
                        <input
                            id="color"
                            name="color"
                            type="color"
                            value="{{ old('color', $category->color ?? '#ef4444') }}"
                            class="h-9 w-14 cursor-pointer rounded-lg border border-gray-300 bg-white p-1 dark:border-gray-700 dark:bg-gray-900"
                        >
                        <x-ui.input name="icon" value="{{ old('icon', $category->icon) }}" placeholder="Icon name (optional)" />
                    </div>
                    <x-ui.error for="color" />
                </div>

                <div>
                    <x-ui.label for="sort_order">Sort order</x-ui.label>
                    <x-ui.input id="sort_order" name="sort_order" type="number" min="0"
                                value="{{ old('sort_order', $category->sort_order ?? 0) }}"
                                :invalid="$errors->has('sort_order')" />
                    <x-ui.error for="sort_order" />
                </div>

                <div class="space-y-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <x-ui.toggle name="is_active" :checked="(bool) old('is_active', $category->is_active ?? true)"
                                 label="Active" hint="Hidden categories disappear from the site." />
                    <x-ui.toggle name="is_featured" :checked="(bool) old('is_featured', $category->is_featured)"
                                 label="Featured" hint="Shown on the home page." />
                </div>

                <div class="flex gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <x-ui.button type="submit" class="flex-1">
                        {{ $category->exists ? 'Save changes' : 'Create category' }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.categories.index')">Cancel</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
