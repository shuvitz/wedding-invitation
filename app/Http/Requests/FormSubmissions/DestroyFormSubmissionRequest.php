<?php

namespace App\Http\Requests\FormSubmissions;

use Illuminate\Foundation\Http\FormRequest;

class DestroyFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('submission'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
