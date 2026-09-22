<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPrediction extends Model
{
    protected $fillable = [
        'championship_match_id',
        'user_id',
        'predicted_home_score',
        'predicted_away_score',
        'points_awarded',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(ChampionshipMatch::class, 'championship_match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
