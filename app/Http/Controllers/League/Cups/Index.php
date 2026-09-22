<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Index extends Controller
{
    public function __invoke()
    {
        $cups = Cup::query()
            ->withCount('matches')
            ->with(['entries.user', 'entries.team'])
            ->latest()
            ->get();

        return Inertia::render('League/Cups/Index', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'cups' => $cups,
        ]);
    }
}
