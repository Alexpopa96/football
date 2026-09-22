<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CupMatch extends Model
{
    protected $fillable = [
        'cup_id',
        'round',
        'slot',
        'home_entry_id',
        'away_entry_id',
        'home_score',
        'away_score',
        'home_penalties',
        'away_penalties',
        'winner_entry_id',
        'feeds_into_match_id',
        'feeds_into_side',
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

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function homeEntry(): BelongsTo
    {
        return $this->belongsTo(CupEntry::class, 'home_entry_id');
    }

    public function awayEntry(): BelongsTo
    {
        return $this->belongsTo(CupEntry::class, 'away_entry_id');
    }

    public function winnerEntry(): BelongsTo
    {
        return $this->belongsTo(CupEntry::class, 'winner_entry_id');
    }

    public function feedsInto(): BelongsTo
    {
        return $this->belongsTo(CupMatch::class, 'feeds_into_match_id');
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
