<?php

namespace App\Http\Controllers\League\Championships\Bets;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipMatch;
use App\Support\BetMarkets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class Store extends Controller
{
    public function __invoke(Championship $championship, ChampionshipMatch $match)
    {
        abort_unless($match->championship_id === $championship->id, 404);
        abort_unless($match->status === 'live', 422, 'Poți paria doar pe meciuri live.');
        abort_if($match->betting_locked_at, 422, 'Pariurile au fost blocate pentru acest meci.');
        abort_if(in_array(Auth::id(), $match->participantUserIds(), true), 403, 'Nu poți paria pe propriul meci.');

        $data = Request::validate([
            'market' => ['required', Rule::in(array_keys(BetMarkets::selections()))],
            'selection' => ['required', 'string'],
            'stake' => ['required', 'integer', 'min:1'],
        ]);

        abort_unless(BetMarkets::isValidSelection($data['market'], $data['selection']), 422, 'Selecție invalidă.');

        $odds = BetMarkets::oddsFor($data['market'], $data['selection']);
        $user = Auth::user();
        $existing = $match->bets()->where('user_id', $user->id)->first();
        $availableBalance = $user->bet_balance + ($existing?->stake ?? 0);

        abort_if($data['stake'] > $availableBalance, 422, 'Nu ai suficiente puncte disponibile.');

        DB::transaction(function () use ($match, $user, $existing, $data, $odds) {
            if ($existing) {
                $user->increment('bet_balance', $existing->stake);
            }

            $user->spendBetBalance($data['stake']);

            $match->bets()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'market' => $data['market'],
                    'selection' => $data['selection'],
                    'stake' => $data['stake'],
                    'odds' => $odds,
                ]
            );
        });

        return Redirect::back()->with(['success' => ['message' => 'Pariu plasat!']]);
    }
}
