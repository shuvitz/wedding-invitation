<?php

namespace App\Http\Requests\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Setting::class);
    }

    /**
     * Every group and every field within it is optional — the update
     * endpoint merges whatever groups are present into the existing
     * record rather than requiring the full shape every time.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_identity'                    => ['sometimes', 'array'],
            'site_identity.name'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'site_identity.logo_media_id'      => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'site_identity.favicon_media_id'   => ['sometimes', 'nullable', 'integer', 'exists:media,id'],

            'contact'         => ['sometimes', 'array'],
            'contact.email'   => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact.phone'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact.address' => ['sometimes', 'nullable', 'string', 'max:1000'],

            'social'           => ['sometimes', 'array'],
            'social.instagram' => ['sometimes', 'nullable', 'string', 'max:255'],
            'social.facebook'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'social.tiktok'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'social.linkedin'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'social.whatsapp'  => ['sometimes', 'nullable', 'string', 'max:255'],

            'seo'                  => ['sometimes', 'array'],
            'seo.meta_title'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo.meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
