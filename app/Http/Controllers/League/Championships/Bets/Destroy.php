<?php

namespace App\Http\Controllers\League\Championships\Bets;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class Destroy extends Controller
{
    public function __invoke(Championship $championship, ChampionshipMatch $match)
    {
        abort_unless($match->championship_id === $championship->id, 404);
        abort_unless($match->status === 'live', 422, 'Poți modifica pariul doar cât meciul e live.');
        abort_if($match->betting_locked_at, 422, 'Pariurile au fost blocate pentru acest meci.');

        $bet = $match->bets()->where('user_id', Auth::id())->first();
        abort_unless($bet, 404);

        DB::transaction(function () use ($bet) {
            Auth::user()->increment('bet_balance', $bet->stake);
            $bet->delete();
        });

        return Redirect::back()->with(['success' => ['message' => 'Pariul a fost anulat.']]);
    }
}
