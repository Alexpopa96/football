<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class Create extends Controller
{
    public function __invoke()
    {
        return Inertia::render('League/Cups/Create', [
            'players' => User::whereNotNull('pin')
                ->where('status', true)
                ->orderBy('id')
                ->get(['id', 'name', 'avatar_emoji', 'avatar_color']),
        ]);
    }
}
