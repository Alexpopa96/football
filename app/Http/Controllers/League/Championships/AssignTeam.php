<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipEntry;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class AssignTeam extends Controller
{
    public function __invoke(Championship $championship, ChampionshipEntry $entry)
    {
        abort_unless($entry->championship_id === $championship->id, 404);

        $takenTeamIds = $championship->entries()
            ->where('id', '!=', $entry->id)
            ->whereNotNull('team_id')
            ->pluck('team_id');

        $data = Request::validate([
            'team_id' => ['required', 'integer', 'exists:teams,id', Rule::notIn($takenTeamIds)],
        ], [
            'required' => 'Campul este obligatoriu',
            'not_in' => 'Echipa este deja aleasă de alt jucător în acest campionat.',
        ]);

        $entry->update([
            'team_id' => $data['team_id'],
            'selection_method' => 'manual',
        ]);

        return Redirect::back()->with(['success' => ['message' => 'Echipă atribuită!']]);
    }
}
