<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class Store extends Controller
{
    public function __invoke()
    {
        $data = Request::validate([
            'name' => ['required', 'string', 'max:255'],
            'player_ids' => ['required', 'array', 'size:4'],
            'player_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ], [
            'required' => 'Campul este obligatoriu',
            'size' => 'Trebuie să alegi exact 4 jucători.',
            'distinct' => 'Fiecare jucător poate fi ales o singură dată.',
        ]);

        $cup = Cup::create([
            'name' => $data['name'],
            'status' => 'selecting_teams',
            'created_by' => Auth::id(),
        ]);

        foreach ($data['player_ids'] as $playerId) {
            $cup->entries()->create(['user_id' => $playerId]);
        }

        return Redirect::to("/league/cups/{$cup->id}/select-teams")
            ->with(['success' => ['message' => 'Cupa a fost creată! Alege echipele.']]);
    }
}
