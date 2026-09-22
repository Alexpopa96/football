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
        $championship->load(['entries.user', 'entries.team', 'matches.homeEntry.user', 'matches.homeEntry.team', 'matches.awayEntry.user', 'matches.awayEntry.team']);

        $matchesByRound = $championship->matches
            ->sortBy('round')
            ->groupBy('round')
            ->map(fn ($matches) => [
                'round' => $matches->first()->round,
                'leg' => $matches->first()->leg,
                'matches' => $matches->values(),
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
