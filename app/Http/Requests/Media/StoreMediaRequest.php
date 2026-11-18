<?php

namespace App\Http\Requests\Media;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Media::class);
    }

    /**
     * `image` + File::types() both validate against the file's actual
     * sniffed content (not the client-supplied filename/extension).
     * `dimensions` guards against decompression-bomb-style huge images.
     * SVG is deliberately not supported — see MediaService docblock.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'image',
                File::types(['jpeg', 'png', 'webp', 'gif'])->max(5 * 1024),
                'dimensions:max_width=8000,max_height=8000',
            ],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
