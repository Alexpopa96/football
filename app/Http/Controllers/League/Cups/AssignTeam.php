<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupEntry;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class AssignTeam extends Controller
{
    public function __invoke(Cup $cup, CupEntry $entry)
    {
        abort_unless($entry->cup_id === $cup->id, 404);

        $takenTeamIds = $cup->entries()
            ->where('id', '!=', $entry->id)
            ->whereNotNull('team_id')
            ->pluck('team_id');

        $data = Request::validate([
            'team_id' => ['required', 'integer', 'exists:teams,id', Rule::notIn($takenTeamIds)],
        ], [
            'required' => 'Campul este obligatoriu',
            'not_in' => 'Echipa este deja aleasă de alt jucător în această cupă.',
        ]);

        $entry->update([
            'team_id' => $data['team_id'],
            'selection_method' => 'manual',
        ]);

        return Redirect::back()->with(['success' => ['message' => 'Echipă atribuită!']]);
    }
}
