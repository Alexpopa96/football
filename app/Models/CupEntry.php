<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupEntry extends Model
{
    protected $fillable = [
        'cup_id',
        'user_id',
        'team_id',
        'selection_method',
    ];

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
