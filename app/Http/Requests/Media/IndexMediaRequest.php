<?php

namespace App\Http\Requests\Media;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class IndexMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Media::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search'    => ['sometimes', 'string', 'max:255'],
            'mime_type' => ['sometimes', 'string', 'max:255'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'      => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
