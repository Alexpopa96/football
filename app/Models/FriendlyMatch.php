<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendlyMatch extends Model
{
    protected $fillable = [
        'home_user_id',
        'away_user_id',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'played_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }

    public function homeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'home_user_id');
    }

    public function awayUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'away_user_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
