<?php

namespace App\Http\Requests\Navigation;

use App\Models\NavigationItem;
use Illuminate\Foundation\Http\FormRequest;

class IndexNavigationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', NavigationItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
