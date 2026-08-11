<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateTagRequest extends StoreTagRequest
{
    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('tags', 'slug')->ignore($this->route('tag')?->id);
    }
}
