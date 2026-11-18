<?php

namespace App\Http\Requests\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class ShowSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', Setting::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
