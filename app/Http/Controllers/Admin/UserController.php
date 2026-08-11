<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->withCount('comments')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $term = '%'.$request->string('search')->toString().'%';
                    $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
                })
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Suspending an account revokes admin access immediately, because
     * canAccessAdminPanel() requires is_active as well as is_admin.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $state = $user->is_active ? 'reactivated' : 'deactivated';
        $this->logger->log("user.{$state}", $user, "Account {$state}: {$user->email}");

        return back()->with('success', "Account {$state}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->posts()->exists()) {
            return back()->with('error', 'This user has written posts. Deactivate the account instead so the bylines stay intact.');
        }

        $email = $user->email;
        $user->delete();

        $this->logger->log('user.deleted', $user, "Deleted account {$email}");

        return back()->with('success', 'Account deleted.');
    }
}
