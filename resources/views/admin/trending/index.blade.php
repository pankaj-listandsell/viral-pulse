@extends('layouts.admin')

@php
    use App\Enums\TrendingTopicStatus;

    $badge = fn (TrendingTopicStatus $status) => match ($status) {
        TrendingTopicStatus::Generated, TrendingTopicStatus::Scheduled => 'green',
        TrendingTopicStatus::Failed => 'red',
        TrendingTopicStatus::Generating, TrendingTopicStatus::Queued => 'blue',
        TrendingTopicStatus::Ignored => 'gray',
        default => 'amber',
    };
@endphp

@section('title', 'Trending Topics')
@section('heading', 'Trending Topics')
@section('subheading', $lastFetch ? 'Last topic seen ' . \Illuminate\Support\Carbon::parse($lastFetch)->diffForHumans() : 'No topics fetched yet')

@section('content')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Waiting" :value="(int) ($counts[TrendingTopicStatus::New->value] ?? 0)" icon="flame" />
        <x-ui.stat-card label="In progress" :value="(int) ($counts[TrendingTopicStatus::Generating->value] ?? 0) + (int) ($counts[TrendingTopicStatus::Queued->value] ?? 0)" icon="loader-circle" />
        <x-ui.stat-card label="Written" :value="(int) ($counts[TrendingTopicStatus::Generated->value] ?? 0) + (int) ($counts[TrendingTopicStatus::Scheduled->value] ?? 0)" icon="check" />
        <x-ui.stat-card label="Held back" :value="(int) ($counts[TrendingTopicStatus::Ignored->value] ?? 0)" icon="x" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <x-ui.card :padded="false">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4 dark:border-gray-800">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <div class="w-full sm:w-48">
                            <x-ui.input name="q" value="{{ $filters['q'] }}" placeholder="Search topics..." class="w-full text-xs" />
                        </div>

                        <div class="w-full sm:w-36">
                            <x-ui.select name="status" class="w-full text-xs" onchange="this.form.submit()">
                                <option value="">Status: All</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="w-full sm:w-40">
                            <x-ui.select name="source" class="w-full text-xs" onchange="this.form.submit()">
                                <option value="">Source: All</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source->value }}" @selected($filters['source'] === $source->value)>{{ $source->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        @if(array_filter($filters))
                            <a href="{{ route('admin.trending.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <x-icon name="x" class="size-3" />
                                Reset
                            </a>
                        @endif
                    </form>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.trending.fetch') }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" size="sm">
                                <x-icon name="trending-up" class="size-4" />
                                Fetch now
                            </x-ui.button>
                        </form>

                        <form method="POST" action="{{ route('admin.trending.run') }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" :disabled="! $hasProvider">
                                <x-icon name="sparkles" class="size-4" />
                                Write the top topics
                            </x-ui.button>
                        </form>
                    </div>
                </div>

                @if($topics->isEmpty())
                    <x-ui.empty-state
                        icon="flame"
                        title="No topics yet"
                        description="Press “Fetch now” to pull the feeds, or add a topic by hand on the right."
                    />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-200 text-xs tracking-wide text-gray-500 uppercase dark:border-gray-800 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-medium">Topic</th>
                                    <th scope="col" class="px-5 py-3 font-medium">Score</th>
                                    <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Status</th>
                                    <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($topics as $topic)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="max-w-md px-5 py-3">
                                            <p class="truncate font-medium">{{ $topic->topic }}</p>
                                            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ $topic->source->label() }}</span>
                                                <span aria-hidden="true">·</span>
                                                <span>{{ $topic->detected_at?->diffForHumans() }}</span>
                                                @if($topic->category)
                                                    <span aria-hidden="true">·</span>
                                                    <span>{{ $topic->category->name }}</span>
                                                @endif
                                                @if($topic->source_url)
                                                    <a href="{{ $topic->source_url }}" target="_blank" rel="noopener nofollow"
                                                       class="inline-flex items-center gap-1 text-brand-600 hover:underline dark:text-brand-400">
                                                        source <x-icon name="external-link" class="size-3" />
                                                    </a>
                                                @endif
                                            </p>
                                        </td>

                                        <td class="px-5 py-3">
                                            <span @class([
                                                'font-semibold tabular-nums',
                                                'text-green-600 dark:text-green-400' => $topic->trend_score >= 70,
                                                'text-amber-600 dark:text-amber-400' => $topic->trend_score >= 45 && $topic->trend_score < 70,
                                                'text-gray-400' => $topic->trend_score < 45,
                                            ])>{{ $topic->trend_score }}</span>
                                        </td>

                                        <td class="hidden px-5 py-3 sm:table-cell">
                                            <x-ui.badge :color="$badge($topic->status)">{{ $topic->status->label() }}</x-ui.badge>
                                            @if($topic->post)
                                                <a href="{{ route('admin.posts.edit', $topic->post) }}"
                                                   class="mt-1 block text-xs text-brand-600 hover:underline dark:text-brand-400">Open post</a>
                                            @endif
                                        </td>

                                        <td class="px-5 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                @if($topic->status->isAvailableForGeneration())
                                                    <form method="POST" action="{{ route('admin.trending.generate', $topic) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" size="sm" :disabled="! $hasProvider">Write it</x-ui.button>
                                                    </form>

                                                    <form method="POST" action="{{ route('admin.trending.ignore', $topic) }}">
                                                        @csrf
                                                        <button type="submit"
                                                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                                                aria-label="Ignore topic">
                                                            <x-icon name="x" class="size-4" />
                                                        </button>
                                                    </form>
                                                @elseif($topic->status === TrendingTopicStatus::Ignored)
                                                    <form method="POST" action="{{ route('admin.trending.restore', $topic) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" size="sm" variant="secondary">Restore</x-ui.button>
                                                    </form>
                                                @endif

                                                <x-ui.confirm-form
                                                    :action="route('admin.trending.destroy', $topic)"
                                                    title="Delete this topic?"
                                                    message="It will come back on the next fetch if the feeds still carry it."
                                                >
                                                    <x-slot:trigger>
                                                        <button type="submit"
                                                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                                                aria-label="Delete topic">
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

                    @if($topics->hasPages())
                        <div class="border-t border-gray-100 p-4 dark:border-gray-800">{{ $topics->links() }}</div>
                    @endif
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-4">
            <x-ui.card title="Automation">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Scheduled writing</dt>
                        <dd>
                            <x-ui.badge :color="$automationEnabled ? 'green' : 'gray'">
                                {{ $automationEnabled ? 'On' : 'Off' }}
                            </x-ui.badge>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Auto-publish</dt>
                        <dd>
                            <x-ui.badge :color="$autoPublish ? 'green' : 'gray'">
                                {{ $autoPublish ? 'On' : 'Drafts only' }}
                            </x-ui.badge>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">Next publishing slot</dt>
                        <dd class="text-right font-medium">
                            {{ $nextSlot?->format('D H:i') ?? 'Schedule full' }}
                        </dd>
                    </div>
                </dl>

                <p class="mt-4 border-t border-gray-100 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    Articles are spaced across the publishing window rather than posted all at once — a burst of
                    identical-looking posts is exactly what Google's scaled-content policy looks for. Both switches
                    live in <code>.env</code>: <code>AUTO_GENERATE_ENABLED</code> and <code>AUTO_PUBLISH</code>.
                </p>
            </x-ui.card>

            <x-ui.card title="Add a topic">
                <form method="POST" action="{{ route('admin.trending.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-ui.label for="topic" required>Topic</x-ui.label>
                        <x-ui.textarea id="topic" name="topic" rows="2"
                                       placeholder="A headline-shaped topic works best"
                                       :invalid="$errors->has('topic')">{{ old('topic') }}</x-ui.textarea>
                        <x-ui.error for="topic" />
                    </div>

                    <div>
                        <x-ui.label for="description">Context</x-ui.label>
                        <x-ui.textarea id="description" name="description" rows="3"
                                       placeholder="Facts the article should be built on. The model looks nothing up."
                                       :invalid="$errors->has('description')">{{ old('description') }}</x-ui.textarea>
                        <x-ui.error for="description" />
                    </div>

                    <div>
                        <x-ui.label for="category_id">Category</x-ui.label>
                        <x-ui.select id="category_id" name="category_id" :invalid="$errors->has('category_id')">
                            <option value="">Decide automatically</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.error for="category_id" />
                    </div>

                    <x-ui.button type="submit" variant="secondary" class="w-full">Add topic</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card title="Where topics come from">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li>Google Trends and Google News RSS, plus any feeds in <code>TRENDING_RSS_FEEDS</code>.</li>
                    <li>Nothing scrapes HTML — published feeds and official APIs only.</li>
                    <li>The same story from several feeds becomes one topic with a higher score.</li>
                    <li>Tragedy, crime and adult topics are held back automatically; they can still be written by hand.</li>
                </ul>
            </x-ui.card>
        </div>
    </div>
@endsection
