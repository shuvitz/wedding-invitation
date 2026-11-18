<?php

namespace App\Http\Requests\Pages;

use App\Models\Page;
use App\Services\BlockRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Page::class);
    }

    /**
     * Slug defaults from the title when omitted, and is always normalized
     * through Str::slug() — nothing reaches the uniqueness/format rules
     * un-normalized. There is no `status` field here on purpose: creation
     * is always draft (see PageService::create) — the only way to publish
     * is the dedicated, admin-only status endpoint.
     */
    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug') ?: $this->input('title', '');

        $this->merge(['slug' => Str::slug((string) $slug)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'title'             => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'unique:pages,slug'],
            'meta_title'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'content'           => ['sometimes', 'array'],
            'content.blocks'    => ['sometimes', 'array', BlockRegistry::uniqueFormKeysRule(), BlockRegistry::singleCoverRule()],
            'content.blocks.*.type' => ['required_with:content.blocks', Rule::in(BlockRegistry::types())],
        ];

        foreach ($this->input('content.blocks', []) as $index => $block) {
            $rules += BlockRegistry::rulesFor($block['type'] ?? null, "content.blocks.{$index}.data");
        }

        return $rules;
    }
}
