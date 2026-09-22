<?php

namespace App\Http\Controllers\League\Predictions;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipMatch;
use App\Models\MatchPrediction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class Store extends Controller
{
    public function __invoke(Championship $championship, ChampionshipMatch $match)
    {
        abort_unless($match->championship_id === $championship->id, 404);
        abort_if($match->isPlayed(), 422, 'Meciul s-a jucat deja, nu mai poți paria.');

        $data = Request::validate([
            'predicted_home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'predicted_away_score' => ['required', 'integer', 'min:0', 'max:99'],
        ], [
            'required' => 'Introdu ambele scoruri.',
        ]);

        MatchPrediction::updateOrCreate(
            [
                'championship_match_id' => $match->id,
                'user_id' => Auth::id(),
            ],
            $data
        );

        return Redirect::back()->with(['success' => ['message' => 'Pronostic salvat!']]);
    }
}
