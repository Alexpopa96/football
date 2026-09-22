<?php

namespace App\Http\Controllers\League\Players;

use App\Http\Controllers\Controller;
use App\Http\Controllers\League\Concerns\BuildsPlayerMatchHistory;
use App\Models\User;
use App\Services\PlayerStatsCalculator;
use Inertia\Inertia;

class Show extends Controller
{
    use BuildsPlayerMatchHistory;

    public function __invoke(User $player, PlayerStatsCalculator $calculator)
    {
        $stats = collect($calculator->leaderboard())->firstWhere('user_id', $player->id);

        abort_if(! $stats, 404);

        $teamStats = collect($calculator->teamStats()['players'])->firstWhere('user_id', $player->id);

        return Inertia::render('League/Players/Show', [
            'player' => [
                'user_id' => $player->id,
                'name' => $player->name,
                'avatar_emoji' => $player->avatar_emoji,
                'avatar_color' => $player->avatar_color,
            ],
            'stats' => $stats,
            'trophies' => $calculator->trophiesFor($player->id),
            'ratingHistory' => $calculator->ratingHistoryFor($player->id),
            'teamStats' => $teamStats,
            'matches' => $this->matchHistoryFor($player),
        ]);
    }
}
