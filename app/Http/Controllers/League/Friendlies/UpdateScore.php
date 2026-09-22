<?php

namespace App\Http\Controllers\League\Friendlies;

use App\Http\Controllers\Controller;
use App\Models\FriendlyMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;

class UpdateScore extends Controller
{
    public function __invoke(FriendlyMatch $friendly)
    {
        abort_unless(
            $friendly->created_by === Auth::id() || Auth::user()->can('manage league'),
            403
        );

        $data = Request::validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
        ], [
            'required' => 'Introdu ambele scoruri.',
        ]);

        $friendly->update($data);

        return Redirect::back()->with(['success' => ['message' => 'Scor actualizat!']]);
    }
}
