<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupMatch;
use App\Services\BetResolver;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class RecordScore extends Controller
{
    public function __invoke(Cup $cup, CupMatch $match, BetResolver $betResolver)
    {
        abort_unless($match->cup_id === $cup->id, 404);
        abort_unless($match->home_entry_id && $match->away_entry_id, 422);

        $data = Request::validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
            'home_penalties' => ['nullable', 'integer', 'min:0', 'max:99'],
            'away_penalties' => ['nullable', 'integer', 'min:0', 'max:99'],
        ], [
            'required' => 'Introdu ambele scoruri.',
        ]);

        $isDraw = $data['home_score'] === $data['away_score'];

        if ($isDraw) {
            if (! isset($data['home_penalties']) || ! isset($data['away_penalties'])) {
                return Redirect::back()->withErrors(['home_penalties' => 'Meciul e egal — introdu rezultatul de la penalty-uri.']);
            }

            if ($data['home_penalties'] === $data['away_penalties']) {
                return Redirect::back()->withErrors(['home_penalties' => 'Penalty-urile nu pot fi egale.']);
            }
        }

        $winnerEntryId = match (true) {
            $data['home_score'] > $data['away_score'] => $match->home_entry_id,
            $data['home_score'] < $data['away_score'] => $match->away_entry_id,
            $data['home_penalties'] > $data['away_penalties'] => $match->home_entry_id,
            default => $match->away_entry_id,
        };

        $match->update([
            'home_score' => $data['home_score'],
            'away_score' => $data['away_score'],
            'home_penalties' => $isDraw ? $data['home_penalties'] : null,
            'away_penalties' => $isDraw ? $data['away_penalties'] : null,
            'winner_entry_id' => $winnerEntryId,
            'status' => 'finished',
            'played_at' => now(),
        ]);

        $betResolver->resolve($match);

        if ($match->feeds_into_match_id) {
            $match->feedsInto->update([
                $match->feeds_into_side === 'home' ? 'home_entry_id' : 'away_entry_id' => $winnerEntryId,
            ]);
        } else {
            $cup->update([
                'winner_entry_id' => $winnerEntryId,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return Redirect::back()->with(['success' => ['message' => 'Scor salvat!']]);
    }
}
