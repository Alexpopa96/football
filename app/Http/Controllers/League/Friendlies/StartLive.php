<?php

namespace App\Http\Controllers\League\Friendlies;

use App\Http\Controllers\Controller;
use App\Models\FriendlyMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class StartLive extends Controller
{
    public function __invoke()
    {
        $data = Request::validate([
            'home_user_id' => ['required', 'exists:users,id', 'different:away_user_id'],
            'away_user_id' => ['required', 'exists:users,id'],
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id'],
        ], [
            'required' => 'Câmpul este obligatoriu.',
            'different' => 'Alege doi jucători diferiți.',
        ]);

        FriendlyMatch::create([
            ...$data,
            'status' => 'live',
            'started_at' => now(),
            'created_by' => Auth::id(),
        ]);

        return Redirect::back()->with(['success' => ['message' => 'Meci live pornit! Se poate paria acum.']]);
    }
}
