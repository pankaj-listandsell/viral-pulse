<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentTone;
use App\Enums\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateAiContentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'min:8', 'max:250'],
            'content_type' => ['required', Rule::enum(ContentType::class)],
            'tone' => ['required', Rule::enum(ContentTone::class)],
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'language' => ['required', 'string', 'size:2'],
            'audience' => ['nullable', 'string', 'max:150'],
            'target_words' => ['required', 'integer', 'min:300', 'max:3000'],
            'extra_context' => ['nullable', 'string', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'topic.min' => 'Give the model more to work with — a few words at least.',
            'target_words.max' => 'Anything past 3000 words tends to get cut off mid-article.',
        ];
    }
}
