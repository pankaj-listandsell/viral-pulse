<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => $this->dashboard->stats(),
            'postsPerDay' => $this->dashboard->postsPerDay(),
            'viewsPerDay' => $this->dashboard->viewsPerDay(),
            'topCategories' => $this->dashboard->topCategories(),
            'topPosts' => $this->dashboard->topPosts(),
            'recentPosts' => $this->dashboard->recentPosts(),
        ]);
    }
}
