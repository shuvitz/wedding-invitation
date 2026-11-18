<?php

namespace App\Http\Requests\FormSubmissions;

use App\Models\FormSubmission;
use Illuminate\Foundation\Http\FormRequest;

class IndexFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', FormSubmission::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'form_key' => ['sometimes', 'nullable', 'string', 'max:50'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
