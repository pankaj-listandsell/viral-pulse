<?php

namespace App\Http\Requests\Admin;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePostRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $this->slugUniqueRule()],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'min:20'],
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'tags' => ['nullable', 'array', 'max:15'],
            'tags.*' => ['nullable', 'string', 'max:60'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'language' => ['required', 'string', 'size:2'],
            'is_featured' => ['boolean'],
            'is_trending' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'og_image' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('posts', 'slug');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // A scheduled post without a date would sit in the queue forever.
            if ($this->input('status') === PostStatus::Scheduled->value && blank($this->input('scheduled_at'))) {
                $validator->errors()->add('scheduled_at', 'Pick a date and time for the scheduled post.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_trending' => $this->boolean('is_trending'),
            'tags' => array_values(array_filter((array) $this->input('tags', []), fn ($tag) => filled($tag))),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers and single hyphens.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
        ];
    }
}
