<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ScheduledPostStatus;
use App\Http\Controllers\Controller;
use App\Models\ScheduledPost;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduledPostController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        return view('admin.scheduled.index', [
            'rows' => ScheduledPost::query()
                ->with('post:id,title,slug,status,category_id', 'post.category:id,name,color')
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->orderByRaw("FIELD(status, 'pending', 'processing', 'failed', 'published', 'cancelled')")
                ->orderBy('scheduled_at')
                ->paginate(25)
                ->withQueryString(),
            'counts' => ScheduledPost::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'statuses' => ScheduledPostStatus::cases(),
            'filters' => ['status' => $status],
            'nextDue' => ScheduledPost::due()->min('scheduled_at'),
        ]);
    }

    /**
     * Publishes ahead of schedule. The row is closed out the same way the
     * command would, so the queue does not try to publish it again.
     */
    public function publishNow(ScheduledPost $scheduled, PostService $posts): RedirectResponse
    {
        if ($scheduled->status !== ScheduledPostStatus::Pending || ! $scheduled->post) {
            return back()->with('error', 'That entry is no longer waiting to be published.');
        }

        $posts->publish($scheduled->post);

        $scheduled->forceFill([
            'status' => ScheduledPostStatus::Published,
            'published_at' => now(),
        ])->save();

        return back()->with('success', 'Published.');
    }

    public function cancel(ScheduledPost $scheduled, PostService $posts): RedirectResponse
    {
        if ($scheduled->status !== ScheduledPostStatus::Pending) {
            return back()->with('error', 'That entry is no longer waiting to be published.');
        }

        $scheduled->forceFill(['status' => ScheduledPostStatus::Cancelled])->save();

        // The post itself goes back to being a draft; leaving it "scheduled"
        // with nothing scheduled would be a lie on the posts screen.
        if ($scheduled->post) {
            $posts->unpublish($scheduled->post);
        }

        return back()->with('success', 'Cancelled. The post is back in drafts.');
    }
}
