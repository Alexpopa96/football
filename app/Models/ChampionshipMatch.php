<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'played_at',
    ];

    protected function casts(): array
    {
        return [
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

    public function isPlayed(): bool
    {
        return ! is_null($this->home_score) && ! is_null($this->away_score);
    }
}
