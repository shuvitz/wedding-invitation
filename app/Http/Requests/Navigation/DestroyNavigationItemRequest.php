<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class DestroyNavigationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('item'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
