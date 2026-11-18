<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationItemFactory> */
    use HasFactory;

    /**
     * `visible` is intentionally excluded — like User's `active`, it's set
     * explicitly by the dedicated visibility endpoint, not the general
     * update endpoint, keeping the two concerns separate (Phase 1 convention).
     *
     * @var list<string>
     */
    protected $fillable = [
        'label',
        'url',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'visible' => 'boolean',
        ];
    }
}
