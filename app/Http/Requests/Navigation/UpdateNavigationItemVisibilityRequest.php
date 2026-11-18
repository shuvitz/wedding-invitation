<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNavigationItemVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'visible' => ['required', 'boolean'],
        ];
    }
}
