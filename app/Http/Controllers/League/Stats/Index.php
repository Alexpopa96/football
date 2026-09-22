<?php

namespace App\Http\Controllers\League\Stats;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\Cup;
use App\Models\FriendlyMatch;
use App\Services\PlayerStatsCalculator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Index extends Controller
{
    public function __invoke(PlayerStatsCalculator $calculator)
    {
        $championshipMatches = Championship::with([
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
                        'id' => 'c'.$match->id,
                        'championship_name' => $championship->name,
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
            });

        $cupMatches = Cup::with([
            'matches.homeEntry.user',
            'matches.homeEntry.team',
            'matches.awayEntry.user',
            'matches.awayEntry.team',
        ])
            ->get()
            ->flatMap(function ($cup) {
                return $cup->matches
                    ->filter->isPlayed()
                    ->map(fn ($match) => [
                        'id' => 'k'.$match->id,
                        'championship_name' => $cup->name,
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
            });

        $friendlyMatches = FriendlyMatch::with(['homeUser', 'awayUser', 'homeTeam', 'awayTeam'])
            ->get()
            ->map(fn ($match) => [
                'id' => 'f'.$match->id,
                'championship_name' => 'Meci amical',
                'played_at' => $match->played_at,
                'home_user_id' => $match->home_user_id,
                'home_user_name' => $match->homeUser->name,
                'home_team' => $match->homeTeam,
                'away_user_id' => $match->away_user_id,
                'away_user_name' => $match->awayUser->name,
                'away_team' => $match->awayTeam,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
            ]);

        $matches = $championshipMatches->concat($cupMatches)->concat($friendlyMatches)->sortByDesc('played_at')->values();

        return Inertia::render('League/Stats/Index', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'players' => $calculator->leaderboard(),
            'matches' => $matches,
            'teamStats' => $calculator->teamStats(),
        ]);
    }
}
