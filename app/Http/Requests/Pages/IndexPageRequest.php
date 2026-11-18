<?php

namespace App\Http\Requests\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Page::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search'   => ['sometimes', 'string', 'max:255'],
            'status'   => ['sometimes', Rule::enum(PageStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'     => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
