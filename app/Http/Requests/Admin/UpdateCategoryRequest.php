<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('categories', 'slug')
            ->whereNull('deleted_at')
            ->ignore($this->route('category')?->id);
    }
}
