<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrendingTopicRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'min:10', 'max:240'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'topic.min' => 'A headline-shaped topic works best — a couple of words is not enough to write from.',
        ];
    }
}
