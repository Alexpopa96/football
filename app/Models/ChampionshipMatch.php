<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ChampionshipMatch extends Model
{
    protected $fillable = [
        'championship_id',
        'round',
        'leg',
        'home_entry_id',
        'away_entry_id',
        'home_score',
        'away_score',
        'status',
        'started_at',
        'betting_locked_at',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'betting_locked_at' => 'datetime',
            'played_at' => 'datetime',
        ];
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function homeEntry(): BelongsTo
    {
        return $this->belongsTo(ChampionshipEntry::class, 'home_entry_id');
    }

    public function awayEntry(): BelongsTo
    {
        return $this->belongsTo(ChampionshipEntry::class, 'away_entry_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(MatchPrediction::class, 'championship_match_id');
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
        return array_filter([$this->homeEntry?->user_id, $this->awayEntry?->user_id]);
    }
}
