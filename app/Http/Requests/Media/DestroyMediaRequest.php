<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class DestroyMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('media'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
