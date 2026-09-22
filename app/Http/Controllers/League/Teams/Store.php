<?php

namespace App\Http\Controllers\League\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class Store extends Controller
{
    public function __invoke()
    {
        $data = Request::validate([
            'name' => ['required', 'string', 'max:255', 'unique:teams,name'],
            'short_name' => ['required', 'string', 'max:24'],
            'country' => ['nullable', 'string', 'max:255'],
            'league' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:9'],
            'crest_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'required' => 'Campul este obligatoriu',
            'unique' => 'Există deja o echipă cu acest nume.',
        ]);

        Team::create($data);

        return Redirect::back()->with(['success' => ['message' => 'Echipa a fost adăugată!']]);
    }
}
