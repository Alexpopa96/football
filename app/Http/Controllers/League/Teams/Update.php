<?php

namespace App\Http\Controllers\League\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class Update extends Controller
{
    public function __invoke(Team $team)
    {
        $data = Request::validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($team->id)],
            'short_name' => ['required', 'string', 'max:24'],
            'country' => ['nullable', 'string', 'max:255'],
            'league' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:9'],
            'crest_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['boolean'],
        ], [
            'required' => 'Campul este obligatoriu',
            'unique' => 'Există deja o echipă cu acest nume.',
        ]);

        $team->update($data);

        return Redirect::back()->with(['success' => ['message' => 'Echipa a fost actualizată!']]);
    }
}
