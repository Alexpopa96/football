<?php

namespace App\Http\Controllers\League\Friendlies;

use App\Http\Controllers\Controller;
use App\Models\FriendlyMatch;
use App\Services\PlayerStatsCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class Store extends Controller
{
    public function __invoke(PlayerStatsCalculator $statsCalculator)
    {
        $data = Request::validate([
            'home_user_id' => ['required', 'exists:users,id', 'different:away_user_id'],
            'away_user_id' => ['required', 'exists:users,id'],
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id'],
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
        ], [
            'required' => 'Câmpul este obligatoriu.',
            'different' => 'Alege doi jucători diferiți.',
        ]);

        FriendlyMatch::create([
            ...$data,
            'played_at' => now(),
            'created_by' => Auth::id(),
        ]);

        $statsCalculator->recalculateRatings();

        return Redirect::back()->with(['success' => ['message' => 'Meciul amical a fost adăugat!']]);
    }
}
