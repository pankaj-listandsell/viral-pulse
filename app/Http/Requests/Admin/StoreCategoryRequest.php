<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $this->slugUniqueRule()],
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('categories', 'slug')->whereNull('deleted_at');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $category = $this->route('category');

            if ($category && (int) $this->input('parent_id') === $category->id) {
                $validator->errors()->add('parent_id', 'A category cannot be its own parent.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }
}
