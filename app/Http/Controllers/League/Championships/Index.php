<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Index extends Controller
{
    public function __invoke()
    {
        $championships = Championship::query()
            ->withCount('matches')
            ->with(['entries.user', 'entries.team'])
            ->latest()
            ->get();

        return Inertia::render('League/Championships/Index', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'championships' => $championships,
        ]);
    }
}
