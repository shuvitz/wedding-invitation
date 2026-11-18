<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately slimmer than the admin NavigationItemResource — a public
 * consumer needs the label/url/order to render a menu, nothing else. `id`,
 * `visible` (this endpoint only ever returns visible items already), and
 * timestamps are internal bookkeeping the public site has no use for.
 */
class PublicNavigationItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->label,
            'url'   => $this->url,
            'order' => $this->order,
        ];
    }
}
