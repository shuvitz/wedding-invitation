<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'form_key'   => $this->form_key,
            'invited_as' => $this->invited_as,
            'data'       => $this->data,
            'created_at' => $this->created_at,
        ];
    }
}
