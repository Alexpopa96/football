<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupMatch;
use App\Services\BracketScheduler;
use Illuminate\Support\Facades\Redirect;

class GenerateFixtures extends Controller
{
    public function __invoke(Cup $cup, BracketScheduler $scheduler)
    {
        $entries = $cup->entries()->whereNotNull('team_id')->get();

        if ($entries->count() !== $cup->entries()->count() || $entries->count() < 2) {
            return Redirect::back()->withErrors(['team' => 'Toți jucătorii trebuie să aibă o echipă atribuită.']);
        }

        if ($cup->matches()->exists()) {
            return Redirect::to("/league/cups/{$cup->id}")
                ->with(['success' => ['message' => 'Turul a fost deja generat.']]);
        }

        $rounds = $scheduler->generate($entries->pluck('id')->all());

        $createdByRoundSlot = [];

        foreach ($rounds as $round => $matches) {
            foreach ($matches as $fixture) {
                $match = $cup->matches()->create([
                    'round' => $round,
                    'slot' => $fixture['slot'],
                    'home_entry_id' => $fixture['home_entry_id'],
                    'away_entry_id' => $fixture['away_entry_id'],
                ]);

                $createdByRoundSlot[$round][$fixture['slot']] = $match;
            }
        }

        foreach ($createdByRoundSlot as $round => $slots) {
            if (! isset($createdByRoundSlot[$round + 1])) {
                continue;
            }

            foreach ($slots as $slot => $match) {
                $nextMatch = $createdByRoundSlot[$round + 1][intdiv($slot, 2)];

                $match->update([
                    'feeds_into_match_id' => $nextMatch->id,
                    'feeds_into_side' => $slot % 2 === 0 ? 'home' : 'away',
                ]);
            }
        }

        // Only round 1 pairs real entries straight from the initial list, so it's the
        // only round that can produce a genuine bye (an odd entry left without an
        // opponent). Later rounds start with both slots empty and get filled in as
        // earlier rounds are decided, so they're never a bye themselves.
        foreach ($createdByRoundSlot[1] ?? [] as $match) {
            $this->resolveByeIfNeeded($match);
        }

        $cup->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return Redirect::to("/league/cups/{$cup->id}")
            ->with(['success' => ['message' => 'Turul a fost generat! Baftă la meciuri.']]);
    }

    /**
     * A fixture with only one entry filled in (a bye) has no opponent to play against,
     * so it's a walkover: the lone entry advances automatically.
     */
    private function resolveByeIfNeeded(CupMatch $match): void
    {
        $winnerEntryId = $match->home_entry_id ?: $match->away_entry_id;

        if (! $winnerEntryId || ($match->home_entry_id && $match->away_entry_id)) {
            return;
        }

        $match->update([
            'winner_entry_id' => $winnerEntryId,
            'status' => 'finished',
            'played_at' => now(),
        ]);

        if ($match->feeds_into_match_id) {
            $match->feedsInto->update([
                $match->feeds_into_side === 'home' ? 'home_entry_id' : 'away_entry_id' => $winnerEntryId,
            ]);
        } else {
            $match->cup->update([
                'winner_entry_id' => $winnerEntryId,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
