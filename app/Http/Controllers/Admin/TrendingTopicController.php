<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TrendingSource;
use App\Enums\TrendingTopicStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrendingTopicRequest;
use App\Models\Category;
use App\Models\TrendingTopic;
use App\Services\AI\AiProviderManager;
use App\Services\SlugService;
use App\Services\Trending\PublishWindow;
use App\Services\Trending\TrendingContentPlanner;
use App\Services\Trending\TrendingTopicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrendingTopicController extends Controller
{
    public function __construct(
        private readonly TrendingTopicService $topics,
        private readonly TrendingContentPlanner $planner,
        private readonly PublishWindow $window,
        private readonly AiProviderManager $providers,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $source = $request->string('source')->toString();
        $search = $request->string('q')->toString();

        return view('admin.trending.index', [
            'topics' => TrendingTopic::query()
                ->with(['category:id,name,color', 'post:id,title,status'])
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($source !== '', fn ($query) => $query->where('source', $source))
                ->when($search !== '', fn ($query) => $query->where('topic', 'like', '%'.addcslashes($search, '%_').'%'))
                ->highestScoring()
                ->paginate(20)
                ->withQueryString(),
            'counts' => TrendingTopic::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'statuses' => TrendingTopicStatus::cases(),
            'sources' => TrendingSource::cases(),
            'categories' => Category::active()->ordered()->get(['id', 'name']),
            'filters' => ['status' => $status, 'source' => $source, 'q' => $search],
            'automationEnabled' => (bool) config('trending.automation.enabled'),
            'autoPublish' => (bool) config('site.content.auto_publish'),
            'hasProvider' => $this->providers->hasAnyProvider(),
            'nextSlot' => $this->window->nextSlot(),
            'lastFetch' => TrendingTopic::max('created_at'),
        ]);
    }

    /**
     * Pulls the feeds on demand. Runs inline rather than on the queue so the
     * admin sees the result of the button they just pressed.
     */
    public function fetch(): RedirectResponse
    {
        $result = $this->topics->ingest($this->topics->fetchAll());

        if ($result['created'] === 0 && $result['updated'] === 0) {
            return back()->with('error', 'No new topics. Either the feeds have nothing fresh, or outbound HTTP is blocked — check the log.');
        }

        return back()->with('success', sprintf(
            '%d new topic(s), %d refreshed, %d held back by the safety list.',
            $result['created'],
            $result['updated'],
            $result['blocked'],
        ));
    }

    public function store(StoreTrendingTopicRequest $request, SlugService $slugs): RedirectResponse
    {
        $data = $request->validated();
        $hash = TrendingTopic::hashTopic($data['topic']);

        if (TrendingTopic::where('topic_hash', $hash)->exists()) {
            return back()->with('error', 'That topic is already in the list.');
        }

        TrendingTopic::create([
            'topic' => $data['topic'],
            'topic_hash' => $hash,
            'slug' => $slugs->normalise($data['topic']),
            'description' => $data['description'] ?? null,
            'source' => TrendingSource::Manual,
            'category_id' => $data['category_id'] ?? null,
            // A topic an admin typed in by hand is by definition worth writing
            // about, so it starts above the automation floor.
            'trend_score' => 80,
            'region' => config('trending.region'),
            'language' => config('trending.language', 'en'),
            'detected_at' => now(),
            'status' => TrendingTopicStatus::New,
        ]);

        return back()->with('success', 'Topic added.');
    }

    public function generate(Request $request, TrendingTopic $topic): RedirectResponse
    {
        if (! $this->providers->hasAnyProvider()) {
            return back()->with('error', 'No AI provider is configured. Add a key to your .env file first.');
        }

        if (! $topic->status->isAvailableForGeneration()) {
            return back()->with('error', 'That topic has already been written about.');
        }

        $this->planner->dispatchFor($topic, $request->user());

        return back()->with('success', 'Generating. The queue worker must be running.');
    }

    /**
     * Runs the automated batch by hand, ignoring the on/off switch. Useful for
     * proving the pipeline works before leaving it to the scheduler.
     */
    public function runBatch(): RedirectResponse
    {
        if (! $this->providers->hasAnyProvider()) {
            return back()->with('error', 'No AI provider is configured. Add a key to your .env file first.');
        }

        $result = $this->planner->run();

        if ($result['queued'] === 0) {
            return back()->with('error', $result['reason'] ?? 'Nothing queued.');
        }

        return back()->with('success', "Queued {$result['queued']} article(s), spaced across the publishing window.");
    }

    public function ignore(TrendingTopic $topic): RedirectResponse
    {
        $topic->forceFill(['status' => TrendingTopicStatus::Ignored])->save();

        return back()->with('success', 'Topic ignored.');
    }

    public function restore(TrendingTopic $topic): RedirectResponse
    {
        $topic->forceFill(['status' => TrendingTopicStatus::New])->save();

        return back()->with('success', 'Topic is back in the queue.');
    }

    public function destroy(TrendingTopic $topic): RedirectResponse
    {
        $topic->delete();

        return back()->with('success', 'Topic deleted.');
    }
}
