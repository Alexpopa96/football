<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MatchBet extends Model
{
    protected $fillable = [
        'user_id',
        'market',
        'selection',
        'stake',
        'odds',
        'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'odds' => 'float',
        ];
    }

    public function betable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
