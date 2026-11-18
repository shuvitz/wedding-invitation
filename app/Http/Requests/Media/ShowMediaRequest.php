<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class ShowMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('media'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
