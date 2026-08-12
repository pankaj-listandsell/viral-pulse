<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\SettingsWriter;
use App\Support\SettingsSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SEO defaults get their own screen rather than a tab: they are the settings
 * most often revisited, and burying them behind a tab makes them easy to miss.
 */
class SeoSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SettingsWriter $writer,
    ) {}

    public function edit(): View
    {
        return view('admin.settings.seo', [
            'group' => SettingsSchema::seo(),
            'values' => $this->settings->all(),
            'endpoints' => [
                'Sitemap index' => route('sitemap.index'),
                'robots.txt' => route('robots'),
                'RSS feed' => route('feed.index'),
                'ads.txt' => route('ads'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->writer->save($request, 'seo', $request->validate(SettingsSchema::rules('seo')));

        return redirect()
            ->route('admin.seo.edit')
            ->with('success', 'SEO settings saved.');
    }
}
