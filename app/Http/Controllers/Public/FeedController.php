<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\FeedService;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __construct(private readonly FeedService $feeds) {}

    public function index(): Response
    {
        return $this->xml($this->feeds->site());
    }

    public function category(Category $category): Response
    {
        abort_unless($category->is_active, 404);

        return $this->xml($this->feeds->category($category));
    }

    private function xml(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
