<?php

namespace App\Http\Requests\Pages;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('page'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
