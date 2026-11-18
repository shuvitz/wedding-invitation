<?php

namespace App\Http\Requests\Pages;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageHomeRequest extends FormRequest
{
    /**
     * Checked against the `setHome` ability specifically — admin only,
     * same reasoning as `publish`.
     */
    public function authorize(): bool
    {
        return $this->user()->can('setHome', $this->route('page'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_home' => ['required', 'boolean'],
        ];
    }
}
