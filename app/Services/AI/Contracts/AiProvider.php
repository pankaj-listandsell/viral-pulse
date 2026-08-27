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

    /**
     * One structured completion against a schema the caller supplies.
     *
     * generate() above is fixed to the article shape, which is right for the
     * thing it does and useless for anything else. This is the general form:
     * the horoscope writer asks for twelve readings in a specific shape and
     * gets back exactly that, already decoded.
     *
     * The schema is written in ordinary JSON Schema - lowercase types,
     * `properties`, `required`. A provider whose API wants a different dialect
     * translates it on the way out; callers should never have to know which
     * provider is configured.
     *
     * @param  array<string, mixed>  $schema
     * @return array{payload: array<string, mixed>, model: string, prompt_tokens: int, completion_tokens: int, raw: string}
     *
     * @throws AiGenerationException
     */
    public function generateJson(string $systemPrompt, string $userPrompt, array $schema, string $name = 'result'): array;

    public function name(): string;

    public function model(): string;
}
