<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class SetWheelPool extends Controller
{
    public function __invoke(Cup $cup)
    {
        $data = Request::validate([
            'team_ids' => ['required', 'array', 'min:2'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ], [
            'required' => 'Alege cel puțin 2 echipe pentru roată.',
            'min' => 'Alege cel puțin 2 echipe pentru roată.',
        ]);

        $cup->update(['wheel_pool_team_ids' => array_values($data['team_ids'])]);

        return Redirect::back()->with(['success' => ['message' => 'Lista pentru roată a fost salvată!']]);
    }
}
