<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\Team;
use Inertia\Inertia;

class SelectTeams extends Controller
{
    public function __invoke(Cup $cup)
    {
        $cup->load(['entries.user', 'entries.team']);

        return Inertia::render('League/Cups/SelectTeams', [
            'cup' => $cup,
            'teams' => Team::active()->orderBy('league')->orderBy('name')->get(),
        ]);
    }
}
