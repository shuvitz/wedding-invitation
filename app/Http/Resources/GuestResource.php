<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'invitation_url' => $this->invitationUrl(),
            'whatsapp_url' => $this->whatsappUrl(),
            // Only on the list (GuestController@index); null = hasn't replied.
            'reply' => $this->whenLoaded('reply', fn () => $this->reply ? [
                'data' => $this->reply->data,
                'created_at' => $this->reply->created_at,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
