<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\Exceptions\AiGenerationException;
use App\Services\AI\GenerationRequest;

interface AiProvider
{
    /**
     * Produce one article. Implementations return the decoded JSON payload the
     * model was asked for; parsing, sanitising and validation happen in
     * AiContentService so every provider is held to the same standard.
     *
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     *
     * @throws AiGenerationException
     */
    public function generate(GenerationRequest $request, string $systemPrompt, string $userPrompt): array;

    public function name(): string;

    public function model(): string;
}
