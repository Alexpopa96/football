<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipMatch;
use App\Services\PredictionScorer;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class RecordScore extends Controller
{
    public function __invoke(Championship $championship, ChampionshipMatch $match, PredictionScorer $scorer)
    {
        abort_unless($match->championship_id === $championship->id, 404);

        $data = Request::validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
        ], [
            'required' => 'Introdu ambele scoruri.',
        ]);

        $match->update([
            'home_score' => $data['home_score'],
            'away_score' => $data['away_score'],
            'played_at' => now(),
        ]);

        $scorer->scoreMatch($match);

        $allPlayed = ! $championship->matches()->whereNull('home_score')->exists();
        if ($allPlayed && $championship->status !== 'completed') {
            $championship->update(['status' => 'completed', 'completed_at' => now()]);
        }

        return Redirect::back()->with(['success' => ['message' => 'Scor salvat!']]);
    }
}
