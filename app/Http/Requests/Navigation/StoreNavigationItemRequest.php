<?php

namespace App\Http\Requests\Navigation;

use App\Models\NavigationItem;
use Illuminate\Foundation\Http\FormRequest;

class StoreNavigationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', NavigationItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'url'   => ['required', 'string', 'max:2048'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
