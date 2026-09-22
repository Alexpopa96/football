<?php

namespace App\Http\Controllers\League\Friendlies;

use App\Http\Controllers\Controller;
use App\Models\FriendlyMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class Destroy extends Controller
{
    public function __invoke(FriendlyMatch $friendly)
    {
        abort_unless(
            $friendly->created_by === Auth::id() || Auth::user()->can('manage league'),
            403
        );

        DB::transaction(function () use ($friendly) {
            foreach ($friendly->bets as $bet) {
                if (is_null($bet->points_awarded)) {
                    $bet->user->increment('bet_balance', $bet->stake);
                }
            }

            $friendly->bets()->delete();
            $friendly->delete();
        });

        return Redirect::back()->with(['success' => ['message' => 'Meciul amical a fost șters.']]);
    }
}
