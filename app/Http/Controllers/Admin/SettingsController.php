<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\SettingsService;
use App\Services\SettingsWriter;
use App\Support\SettingsSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SettingsWriter $writer,
        private readonly ActivityLogger $logger,
    ) {}

    public function edit(Request $request): View
    {
        $groups = SettingsSchema::groups();
        $tab = $request->string('tab')->toString();

        return view('admin.settings.edit', [
            'groups' => $groups,
            'active' => array_key_exists($tab, $groups) ? $tab : array_key_first($groups),
            'values' => $this->settings->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $group = $request->string('group')->toString();

        // The group comes from a hidden field, so it is checked rather than
        // trusted: an unknown one would silently validate against no rules.
        abort_unless(array_key_exists($group, SettingsSchema::groups()), 404);

        $this->writer->save($request, $group, $request->validate(SettingsSchema::rules($group)));

        return redirect()
            ->route('admin.settings.edit', ['tab' => $group])
            ->with('success', 'Settings saved.');
    }

    public function flushCaches(): RedirectResponse
    {
        $this->writer->flushCaches();

        $this->logger->log('settings.cache_flushed', null, 'Cleared the site caches');

        return back()->with('success', 'Caches cleared.');
    }
}
