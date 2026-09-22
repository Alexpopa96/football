<?php

namespace App\Http\Controllers\League\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\League\Concerns\BuildsPlayerMatchHistory;
use App\Services\PlayerStatsCalculator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Show extends Controller
{
    use BuildsPlayerMatchHistory;

    public function __invoke(PlayerStatsCalculator $calculator)
    {
        $user = Auth::user();

        $stats = collect($calculator->leaderboard())->firstWhere('user_id', $user->id);
        $teamStats = collect($calculator->teamStats()['players'])->firstWhere('user_id', $user->id);

        return Inertia::render('League/Settings/Show', [
            'player' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_emoji' => $user->avatar_emoji,
                'avatar_color' => $user->avatar_color,
            ],
            'stats' => $stats,
            'trophies' => $calculator->trophiesFor($user->id),
            'ratingHistory' => $calculator->ratingHistoryFor($user->id),
            'teamStats' => $teamStats,
            'matches' => $this->matchHistoryFor($user),
        ]);
    }
}
