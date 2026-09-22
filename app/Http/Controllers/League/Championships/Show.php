<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Services\StandingsCalculator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Show extends Controller
{
    public function __invoke(Championship $championship, StandingsCalculator $calculator)
    {
        $championship->load([
            'entries.user',
            'entries.team',
            'matches.homeEntry.user',
            'matches.homeEntry.team',
            'matches.awayEntry.user',
            'matches.awayEntry.team',
            'matches.predictions.user',
            'matches.bets.user',
        ]);

        $userId = Auth::id();

        $matchesByRound = $championship->matches
            ->sortBy('round')
            ->groupBy('round')
            ->map(fn ($matches) => [
                'round' => $matches->first()->round,
                'leg' => $matches->first()->leg,
                'matches' => $matches->map(fn ($match) => [
                    ...$match->toArray(),
                    'my_prediction' => $match->predictions->firstWhere('user_id', $userId),
                    'predictions' => $match->isPlayed() ? $match->predictions : [],
                    'bets' => $match->status === 'finished' ? $match->bets : [],
                    'my_bets' => $match->bets->where('user_id', $userId)->values(),
                    'can_bet' => $match->status === 'live' && ! $match->betting_locked_at && ! in_array($userId, $match->participantUserIds(), true),
                ])->values(),
            ])
            ->values();

        return Inertia::render('League/Championships/Show', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'championship' => $championship,
            'standings' => $calculator->calculate($championship),
            'rounds' => $matchesByRound,
        ]);
    }
}
