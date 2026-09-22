<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class Create extends Controller
{
    public function __invoke()
    {
        return Inertia::render('League/Championships/Create', [
            'players' => User::whereNotNull('pin')
                ->where('status', true)
                ->orderBy('id')
                ->get(['id', 'name', 'avatar_emoji', 'avatar_color']),
        ]);
    }
}
