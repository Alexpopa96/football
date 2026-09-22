<?php

namespace App\Services;

use App\Support\BetMarkets;
use Illuminate\Database\Eloquent\Model;

class BetResolver
{
    public function resolve(Model $match): void
    {
        foreach ($match->bets()->with('user')->get() as $bet) {
            $won = BetMarkets::wins($bet->market, $bet->selection, $match->home_score, $match->away_score);

            if ($won) {
                $payout = (int) round($bet->stake * $bet->odds);
                $bet->user->increment('bet_balance', $payout);
                $net = $payout - $bet->stake;
            } else {
                $net = -$bet->stake;
            }

            $bet->update(['points_awarded' => $net]);
        }
    }
}
