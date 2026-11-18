<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    /** @use HasFactory<\Database\Factories\GuestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'notes',
    ];

    /**
     * The guest's personal link to the invitation (the public site's
     * homepage), greeting them by name via the cover block's `?to=`.
     */
    public function invitationUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/?'.http_build_query(['to' => $this->name]);
    }
}
