<?php

namespace App\Http\Requests\Pages;

use App\Enums\PageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageStatusRequest extends FormRequest
{
    /**
     * Checked against the `publish` ability specifically — distinct from
     * `update` — so editors can never flip a page's published state
     * regardless of what they're otherwise allowed to edit.
     */
    public function authorize(): bool
    {
        return $this->user()->can('publish', $this->route('page'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(PageStatus::class)],
        ];
    }
}
