<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Show extends Controller
{
    public function __invoke(Cup $cup)
    {
        $cup->load([
            'entries.user',
            'entries.team',
            'winnerEntry.user',
            'winnerEntry.team',
            'matches.homeEntry.user',
            'matches.homeEntry.team',
            'matches.awayEntry.user',
            'matches.awayEntry.team',
        ]);

        $roundsByNumber = $cup->matches->sortBy('round')->groupBy('round');
        $totalRounds = $roundsByNumber->count();

        $rounds = $roundsByNumber
            ->map(fn ($matches, $round) => [
                'round' => (int) $round,
                'label' => $this->roundLabel((int) $round, $totalRounds),
                'matches' => $matches->sortBy('slot')->values(),
            ])
            ->values();

        return Inertia::render('League/Cups/Show', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'cup' => $cup,
            'rounds' => $rounds,
        ]);
    }

    private function roundLabel(int $round, int $totalRounds): string
    {
        $fromEnd = $totalRounds - $round;

        return match ($fromEnd) {
            0 => 'Finală',
            1 => 'Semifinale',
            2 => 'Sferturi',
            default => "Runda {$round}",
        };
    }
}
