<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'action' => $request->string('action')->toString(),
            'user' => $request->integer('user'),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ];

        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['user'], fn ($query) => $query->where('user_id', $filters['user']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';

                $query->where('description', 'like', $like);
            })
            ->when($this->date($filters['from']), fn ($query, $date) => $query->where('created_at', '>=', $date->startOfDay()))
            ->when($this->date($filters['to']), fn ($query, $date) => $query->where('created_at', '<=', $date->endOfDay()))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity.index', [
            'logs' => $logs,
            'filters' => $filters,
            // Built from what has actually been recorded, so the filter never
            // offers an action that would return nothing.
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
            'retentionDays' => (int) config('site.retention.activity_log_days', 180),
        ]);
    }

    private function date(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
