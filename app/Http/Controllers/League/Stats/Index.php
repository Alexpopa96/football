<?php

namespace App\Http\Controllers\League\Stats;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Services\PlayerStatsCalculator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Index extends Controller
{
    public function __invoke(PlayerStatsCalculator $calculator)
    {
        $matches = Championship::with([
            'matches.homeEntry.user',
            'matches.homeEntry.team',
            'matches.awayEntry.user',
            'matches.awayEntry.team',
        ])
            ->get()
            ->flatMap(function ($championship) {
                return $championship->matches
                    ->filter->isPlayed()
                    ->map(fn ($match) => [
                        'id' => $match->id,
                        'championship_id' => $championship->id,
                        'championship_name' => $championship->name,
                        'round' => $match->round,
                        'leg' => $match->leg,
                        'played_at' => $match->played_at,
                        'home_user_id' => $match->homeEntry->user_id,
                        'home_user_name' => $match->homeEntry->user->name,
                        'home_team' => $match->homeEntry->team,
                        'away_user_id' => $match->awayEntry->user_id,
                        'away_user_name' => $match->awayEntry->user->name,
                        'away_team' => $match->awayEntry->team,
                        'home_score' => $match->home_score,
                        'away_score' => $match->away_score,
                    ]);
            })
            ->sortByDesc('played_at')
            ->values();

        return Inertia::render('League/Stats/Index', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'players' => $calculator->leaderboard(),
            'matches' => $matches,
        ]);
    }
}
