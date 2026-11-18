<?php

namespace App\Http\Requests\Guests;

use App\Models\Guest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create and update share the same fields.
 */
class SaveGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Guest::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::fieldRules();
    }

    /**
     * Also used per row by the CSV import.
     *
     * @return array<string, mixed>
     */
    public static function fieldRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 \-]*$/'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
