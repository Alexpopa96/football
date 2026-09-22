<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FriendlyMatch extends Model
{
    protected $fillable = [
        'home_user_id',
        'away_user_id',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'status',
        'started_at',
        'betting_locked_at',
        'played_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'betting_locked_at' => 'datetime',
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

    public function bets(): MorphMany
    {
        return $this->morphMany(MatchBet::class, 'betable');
    }

    public function isPlayed(): bool
    {
        return ! is_null($this->home_score) && ! is_null($this->away_score);
    }

    public function participantUserIds(): array
    {
        return array_filter([$this->home_user_id, $this->away_user_id]);
    }
}
