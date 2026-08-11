<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Controller;
use App\Services\AI\AiProviderManager;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiSettingsController extends Controller
{
    public function __construct(
        private readonly AiProviderManager $providers,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Only the provider choice and the model live in the database. API keys
     * stay in .env - a key stored here would appear in every database dump and
     * backup, and on this page.
     */
    public function update(Request $request): RedirectResponse
    {
        $configured = array_keys($this->providers->configured());

        $validated = $request->validate([
            'ai_provider' => ['required', Rule::in($configured)],
            'ai_model' => ['nullable', 'string', 'max:100'],
            'auto_publish' => ['boolean'],
        ], [
            'ai_provider.in' => 'That provider has no API key configured in .env.',
        ]);

        $provider = $validated['ai_provider'];
        $models = array_keys(config("ai.providers.{$provider}.models", []));

        if (! empty($validated['ai_model']) && ! in_array($validated['ai_model'], $models, true)) {
            return back()->withErrors(['ai_model' => 'That model is not available for the selected provider.']);
        }

        $this->settings->set('ai_provider', $provider, SettingType::String, 'ai');

        if (! empty($validated['ai_model'])) {
            $this->settings->set("ai_model_{$provider}", $validated['ai_model'], SettingType::String, 'ai');
        }

        $this->settings->set('ai_auto_publish', $request->boolean('auto_publish'), SettingType::Boolean, 'ai');

        return back()->with('success', 'AI settings saved.');
    }
}
