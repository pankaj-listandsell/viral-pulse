<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    /**
     * The `image` rule checks the real file contents, and `mimetypes` checks
     * the sniffed type rather than the filename. MediaService re-encodes on
     * top of this, so a disguised payload never survives.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'image',
                'mimetypes:'.implode(',', config('site.media.allowed_mimes')),
                'max:'.config('site.media.max_upload_kb'),
                'dimensions:max_width=8000,max_height=8000',
            ],
            'folder' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9 _-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = round(config('site.media.max_upload_kb') / 1024, 1);

        return [
            'files.*.max' => "Each image must be {$maxMb} MB or smaller.",
            'files.*.mimetypes' => 'Only JPEG, PNG, WebP and GIF images are allowed.',
            'files.*.dimensions' => 'That image is too large to process safely.',
        ];
    }
}
