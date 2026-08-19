<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapService $sitemap) {}

    public function index(): Response
    {
        return $this->xml($this->sitemap->index());
    }

    public function posts(int $page): Response
    {
        // Out-of-range pages 404 rather than serving an empty sitemap, so a
        // crawler is told the URL is gone instead of being handed nothing.
        abort_if($page < 1 || $page > max(1, $this->sitemap->postPageCount()), 404);

        return $this->xml($this->sitemap->posts($page));
    }

    public function categories(): Response
    {
        return $this->xml($this->sitemap->categories());
    }

    public function tags(): Response
    {
        return $this->xml($this->sitemap->tags());
    }

    public function pages(): Response
    {
        return $this->xml($this->sitemap->pages());
    }

    /**
     * No X-Robots-Tag here, deliberately.
     *
     * It used to send `noindex`, meaning to keep the raw XML out of search
     * results. Google reads that header on a sitemap as an instruction to
     * ignore the sitemap itself: Search Console reported "Couldn't fetch"
     * against a file that was answering 200 in under a second. Sitemap XML
     * does not turn up in results anyway, so the header bought nothing and
     * cost the site its entire submitted index.
     */
    private function xml(string $body): Response
    {
        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
