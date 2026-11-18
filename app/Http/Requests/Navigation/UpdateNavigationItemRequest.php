<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNavigationItemRequest extends FormRequest
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
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'url'   => ['sometimes', 'required', 'string', 'max:2048'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
