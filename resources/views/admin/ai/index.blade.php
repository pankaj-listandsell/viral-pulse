@extends('layouts.admin')

@php
    use App\Enums\AiGenerationStatus;

    $pending = $generations->getCollection()
        ->filter(fn ($g) => ! $g->status->isFinished())
        ->map(fn ($g) => ['id' => $g->id, 'url' => route('admin.ai.status', $g)])
        ->values();
@endphp

@section('title', 'AI Generator')
@section('heading', 'AI Generator')
@section('subheading', $usedToday . ' of ' . $dailyLimit . ' generations used today')

@section('content')
    @unless($hasProvider)
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-amber-900 dark:text-amber-200">
                <x-icon name="triangle-alert" class="size-4" />
                No AI provider configured
            </h2>
            <p class="mt-2 text-sm text-amber-900 dark:text-amber-200">
                Add <code class="rounded bg-amber-100 px-1 dark:bg-amber-500/20">GEMINI_API_KEY</code> or
                <code class="rounded bg-amber-100 px-1 dark:bg-amber-500/20">OPENAI_API_KEY</code> to your
                <code class="rounded bg-amber-100 px-1 dark:bg-amber-500/20">.env</code> file, then run
                <code class="rounded bg-amber-100 px-1 dark:bg-amber-500/20">php artisan config:clear</code>.
                Keys are deliberately not editable here — a key stored in the database would end up in every backup.
            </p>
        </div>
    @endunless

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.card title="Generate an article">
                <form method="POST" action="{{ route('admin.ai.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-ui.label for="topic" required>Topic</x-ui.label>
                        <x-ui.textarea id="topic" name="topic" rows="2" required
                                       placeholder="What should the article be about? Be specific — a headline-shaped topic works best."
                                       :invalid="$errors->has('topic')">{{ old('topic') }}</x-ui.textarea>
                        <x-ui.error for="topic" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="content_type" required>Content type</x-ui.label>
                            <x-ui.select id="content_type" name="content_type" :invalid="$errors->has('content_type')">
                                @foreach($contentTypes as $type)
                                    <option value="{{ $type->value }}" @selected(old('content_type') === $type->value)>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error for="content_type" />
                        </div>

                        <div>
                            <x-ui.label for="category_id" required>Category</x-ui.label>
                            <x-ui.select id="category_id" name="category_id" :invalid="$errors->has('category_id')">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error for="category_id" />
                        </div>

                        <div>
                            <x-ui.label for="tone" required>Tone</x-ui.label>
                            <x-ui.select id="tone" name="tone" :invalid="$errors->has('tone')">
                                @foreach($tones as $tone)
                                    <option value="{{ $tone->value }}" @selected(old('tone', 'informative') === $tone->value)>
                                        {{ $tone->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error for="tone" />
                        </div>

                        <div>
                            <x-ui.label for="language" required>Language</x-ui.label>
                            <x-ui.select id="language" name="language" :invalid="$errors->has('language')">
                                <option value="en" @selected(old('language', 'en') === 'en')>English</option>
                                <option value="hi" @selected(old('language') === 'hi')>Hindi</option>
                            </x-ui.select>
                            <x-ui.error for="language" />
                        </div>

                        <div>
                            <x-ui.label for="target_words" required>Approximate length</x-ui.label>
                            <x-ui.select id="target_words" name="target_words" :invalid="$errors->has('target_words')">
                                @foreach([500 => 'Short (~500 words)', 900 => 'Standard (~900 words)', 1400 => 'Long (~1400 words)', 2000 => 'In depth (~2000 words)'] as $value => $label)
                                    <option value="{{ $value }}" @selected((int) old('target_words', 900) === $value)>{{ $label }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error for="target_words" />
                        </div>

                        <div>
                            <x-ui.label for="audience">Audience</x-ui.label>
                            <x-ui.input id="audience" name="audience" value="{{ old('audience') }}"
                                        placeholder="e.g. first-time travellers" :invalid="$errors->has('audience')" />
                            <x-ui.error for="audience" />
                        </div>
                    </div>

                    <div>
                        <x-ui.label for="extra_context">Extra context</x-ui.label>
                        <x-ui.textarea id="extra_context" name="extra_context" rows="3"
                                       placeholder="Facts, angles, or source notes the article should be built on. The model will not look anything up."
                                       :invalid="$errors->has('extra_context')">{{ old('extra_context') }}</x-ui.textarea>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            The model has no live web access. Anything time-sensitive should be pasted here.
                        </p>
                        <x-ui.error for="extra_context" />
                    </div>

                    <div class="flex items-center gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <x-ui.button type="submit" :disabled="! $hasProvider || $usedToday >= $dailyLimit">
                            <x-icon name="sparkles" class="size-4" />
                            Generate
                        </x-ui.button>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Runs on the queue — <code>php artisan queue:work</code> must be running.
                        </p>
                    </div>
                </form>
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card title="Provider" subtitle="API keys live in .env, never in the database.">
                @php
                    $providerModels = [];
                    foreach ($providers as $pKey => $pVal) {
                        $providerModels[$pKey] = $pVal['models'] ?? [];
                    }
                    $savedModel = app(\App\Services\SettingsService::class)->get("ai_model_{$currentProvider}") ?: '';
                @endphp

                <form method="POST" action="{{ route('admin.ai.settings') }}" class="space-y-4"
                      x-data="{
                          selectedProvider: '{{ $currentProvider }}',
                          selectedModel: '{{ $savedModel }}',
                          providerModels: {{ json_encode($providerModels) }}
                      }">
                    @csrf

                    @if($providers)
                        <div>
                            <x-ui.label for="ai_provider">Active provider</x-ui.label>
                            <x-ui.select id="ai_provider" name="ai_provider" x-model="selectedProvider" :invalid="$errors->has('ai_provider')">
                                @foreach($providers as $key => $provider)
                                    <option value="{{ $key }}">{{ $provider['label'] }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.error for="ai_provider" />
                        </div>

                        <div>
                            <x-ui.label for="ai_model">Model</x-ui.label>
                            <x-ui.select id="ai_model" name="ai_model" x-model="selectedModel" :invalid="$errors->has('ai_model')">
                                <option value="">Provider default</option>
                                <template x-for="(label, id) in (providerModels[selectedProvider] || {})" :key="id">
                                    <option :value="id" x-text="label" :selected="selectedModel === id"></option>
                                </template>
                            </x-ui.select>
                            <x-ui.error for="ai_model" />
                        </div>

                        <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                            <x-ui.toggle
                                name="auto_publish"
                                :checked="$autoPublish"
                                label="Auto-publish scheduled content"
                                hint="Only applies to the scheduler, and only when the article clears the quality gate. Manual generations always become drafts."
                            />
                        </div>

                        <x-ui.button type="submit" variant="secondary" class="w-full">Save AI settings</x-ui.button>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Nothing to configure until a provider key is present in <code>.env</code>.
                        </p>
                    @endif
                </form>
            </x-ui.card>

            <x-ui.card title="How this works">
                <ol class="space-y-2.5 text-sm text-gray-600 dark:text-gray-400">
                    <li><strong class="text-gray-900 dark:text-gray-100">1.</strong> The article is generated on the queue and logged with its tokens and cost.</li>
                    <li><strong class="text-gray-900 dark:text-gray-100">2.</strong> The HTML is sanitised — model output is never trusted.</li>
                    <li><strong class="text-gray-900 dark:text-gray-100">3.</strong> A quality check scores it for length, truncation, placeholders and duplicate titles.</li>
                    <li><strong class="text-gray-900 dark:text-gray-100">4.</strong> You read it and approve. One click creates the post.</li>
                </ol>
            </x-ui.card>
        </div>
    </div>

    <div class="mt-6">
        {{-- The island only owns this strip. Mounting replaces the element's
             contents, so it must never wrap the table below it. --}}
        @if($pending->isNotEmpty())
            <div data-island="GenerationStatus" data-island-eager
                 data-props="{{ json_encode(['pending' => $pending]) }}"></div>
        @endif

        <x-ui.card title="Recent generations" :padded="false">
            @if($generations->isEmpty())
                <x-ui.empty-state icon="sparkles" title="Nothing generated yet"
                                  description="Your generations, their cost and their quality score will appear here." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase dark:border-gray-800 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-medium">Topic</th>
                                <th scope="col" class="px-5 py-3 font-medium">Status</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Quality</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium lg:table-cell">Tokens</th>
                                <th scope="col" class="hidden px-5 py-3 font-medium lg:table-cell">Cost</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($generations as $generation)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50" data-generation="{{ $generation->id }}">
                                    <td class="max-w-sm px-5 py-3">
                                        <p class="truncate font-medium">{{ $generation->topic }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $generation->provider }} · {{ $generation->model }} · {{ $generation->created_at->diffForHumans() }}
                                        </p>
                                        @if($generation->error_message)
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $generation->error_message }}</p>
                                        @endif
                                    </td>

                                    <td class="px-5 py-3" data-status-cell>
                                        @php
                                            $color = match ($generation->status) {
                                                AiGenerationStatus::Completed => 'green',
                                                AiGenerationStatus::Failed, AiGenerationStatus::Rejected => 'red',
                                                AiGenerationStatus::Processing => 'blue',
                                                default => 'amber',
                                            };
                                        @endphp
                                        <x-ui.badge :color="$color">{{ $generation->status->label() }}</x-ui.badge>
                                    </td>

                                    <td class="hidden px-5 py-3 sm:table-cell">
                                        @if($generation->quality_score !== null)
                                            <span @class([
                                                'font-medium tabular-nums',
                                                'text-green-600 dark:text-green-400' => $generation->quality_score >= 70,
                                                'text-amber-600 dark:text-amber-400' => $generation->quality_score < 70,
                                            ])>{{ $generation->quality_score }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="hidden px-5 py-3 tabular-nums text-gray-500 lg:table-cell dark:text-gray-400">
                                        {{ $generation->totalTokens() ? number_format($generation->totalTokens()) : '—' }}
                                    </td>

                                    <td class="hidden px-5 py-3 tabular-nums text-gray-500 lg:table-cell dark:text-gray-400">
                                        {{ $generation->cost ? '$'.number_format((float) $generation->cost, 4) : '—' }}
                                    </td>

                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($generation->post_id)
                                                <x-ui.button size="sm" variant="secondary" :href="route('admin.posts.edit', $generation->post_id)">
                                                    Open post
                                                </x-ui.button>
                                            @elseif($generation->status === AiGenerationStatus::Completed)
                                                <x-ui.button size="sm" :href="route('admin.ai.show', $generation)">Review</x-ui.button>
                                            @elseif($generation->status === AiGenerationStatus::Failed)
                                                <form method="POST" action="{{ route('admin.ai.retry', $generation) }}">
                                                    @csrf
                                                    <x-ui.button size="sm" variant="secondary" type="submit">Retry</x-ui.button>
                                                </form>
                                            @endif

                                            <x-ui.confirm-form
                                                :action="route('admin.ai.destroy', $generation)"
                                                title="Delete this record?"
                                                message="The generation log is removed. Any post it created stays."
                                            >
                                                <x-slot:trigger>
                                                    <button type="submit"
                                                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                                            aria-label="Delete record">
                                                        <x-icon name="trash-2" class="size-4" />
                                                    </button>
                                                </x-slot:trigger>
                                            </x-ui.confirm-form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        @if($generations->hasPages())
            <div class="mt-4">{{ $generations->links() }}</div>
        @endif
    </div>
@endsection
