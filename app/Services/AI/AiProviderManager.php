<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\SettingsService;
use Illuminate\Contracts\Container\Container;

class AiProviderManager
{
    private ?AiProvider $override = null;

    public function __construct(
        private readonly Container $container,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Used by the test suite to bind the fake provider, so no test can reach a
     * real API even by accident.
     */
    public function swap(AiProvider $provider): void
    {
        $this->override = $provider;
    }

    public function resolve(?string $name = null): AiProvider
    {
        if ($this->override) {
            return $this->override;
        }

        $name ??= $this->current();
        $config = config("ai.providers.{$name}");

        if (! $config) {
            throw AiGenerationException::permanent("Unknown AI provider \"{$name}\".");
        }

        if (blank($config['key'])) {
            throw AiGenerationException::permanent(
                "No API key configured for {$config['label']}. Add it to your .env file."
            );
        }

        $config['model'] = $this->settings->get("ai_model_{$name}") ?: $config['model'];

        return $this->container->make($config['driver'], [
            'config' => $config,
        ]);
    }

    /**
     * The selected provider, falling back to the first configured one so a bad
     * setting never leaves the site unable to generate anything.
     */
    public function current(): string
    {
        $selected = $this->settings->get('ai_provider') ?: config('ai.provider');

        if ($this->isConfigured($selected)) {
            return $selected;
        }

        return array_key_first($this->configured()) ?? $selected;
    }

    public function isConfigured(?string $name): bool
    {
        return $name !== null && filled(config("ai.providers.{$name}.key"));
    }

    /**
     * Providers with a key present. Anything without one is never offered in
     * the admin, so the settings screen cannot select a dead provider.
     *
     * @return array<string, array<string, mixed>>
     */
    public function configured(): array
    {
        return collect(config('ai.providers'))
            ->filter(fn (array $provider) => filled($provider['key']))
            ->all();
    }

    public function hasAnyProvider(): bool
    {
        return $this->configured() !== [];
    }

    /**
     * Gets a fallback configured provider if the primary provider fails.
     */
    public function fallback(?string $primary = null): ?AiProvider
    {
        if ($this->override) {
            return null;
        }

        $primary ??= $this->current();
        $configured = array_keys($this->configured());

        foreach ($configured as $name) {
            if ($name !== $primary) {
                try {
                    return $this->resolve($name);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }
}
