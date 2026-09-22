<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\Team;
use Inertia\Inertia;

class SelectTeams extends Controller
{
    public function __invoke(Championship $championship)
    {
        $championship->load(['entries.user', 'entries.team']);

        return Inertia::render('League/Championships/SelectTeams', [
            'championship' => $championship,
            'teams' => Team::active()->orderBy('league')->orderBy('name')->get(),
        ]);
    }
}
