<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class Store extends Controller
{
    public function __invoke()
    {
        $data = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'player_ids' => ['required', 'array', 'min:3', 'max:4'],
            'player_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ], [
            'required' => 'Campul este obligatoriu',
            'min' => 'Trebuie să alegi cel puțin 3 jucători.',
            'max' => 'Poți alege cel mult 4 jucători.',
            'distinct' => 'Fiecare jucător poate fi ales o singură dată.',
        ]);

        $championship = Championship::create([
            'name' => $data['name'],
            'status' => 'selecting_teams',
            'created_by' => Auth::id(),
        ]);

        foreach ($data['player_ids'] as $playerId) {
            $championship->entries()->create(['user_id' => $playerId]);
        }

        return Redirect::to("/league/championships/{$championship->id}/select-teams")
            ->with(['success' => ['message' => 'Campionatul a fost creat! Alege echipele.']]);
    }
}
