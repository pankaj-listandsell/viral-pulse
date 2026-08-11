<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $this->slugUniqueRule()],
            'description' => ['nullable', 'string', 'max:255'],
            'is_trending' => ['boolean'],
        ];
    }

    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('tags', 'slug');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_trending' => $this->boolean('is_trending')]);
    }
}
