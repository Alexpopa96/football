<?php

namespace App\Http\Controllers\League\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Show extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        return Inertia::render('League/Settings/Show', [
            'player' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_emoji' => $user->avatar_emoji,
                'avatar_color' => $user->avatar_color,
            ],
        ]);
    }
}
