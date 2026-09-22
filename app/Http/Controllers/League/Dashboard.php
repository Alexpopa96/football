<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Services\StandingsCalculator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Dashboard extends Controller
{
    public function __invoke(StandingsCalculator $calculator)
    {
        $active = Championship::query()
            ->whereIn('status', ['in_progress', 'selecting_teams'])
            ->latest()
            ->first();

        $standings = null;
        if ($active && $active->status === 'in_progress') {
            $standings = $calculator->calculate($active);
        } elseif ($active) {
            $active->load(['entries.user', 'entries.team']);
        }

        return Inertia::render('League/Dashboard', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'active' => $active,
            'standings' => $standings,
            'championshipsCount' => Championship::count(),
            'teamsCount' => \App\Models\Team::active()->count(),
        ]);
    }
}
