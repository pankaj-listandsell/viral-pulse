<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdatePostRequest extends StorePostRequest
{
    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('posts', 'slug')->ignore($this->route('post')?->id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // An already-scheduled post keeps its original time on save, so the
        // "must be in the future" rule only applies when the value changes.
        if ($this->input('scheduled_at') === optional($this->route('post')?->scheduled_at)->format('Y-m-d\TH:i')) {
            $rules['scheduled_at'] = ['nullable', 'date'];
        }

        return $rules;
    }
}
