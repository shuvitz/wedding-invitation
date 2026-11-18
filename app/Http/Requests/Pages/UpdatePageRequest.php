<?php

namespace App\Http\Requests\Pages;

use App\Services\BlockRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('page'));
    }

    /**
     * Slug is normalized only if the request actually includes one — an
     * update that omits `slug` must leave the existing slug untouched
     * (stable-unless-explicitly-changed). No `status` field here either;
     * see StorePageRequest/PageService for why.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('slug'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'title'             => ['sometimes', 'required', 'string', 'max:255'],
            'slug'              => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($this->route('page'))],
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
