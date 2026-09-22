<?php

namespace App\Http\Controllers\League\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Index extends Controller
{
    public function __invoke()
    {
        request()->validate([
            'league' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
        ]);

        $teams = Team::query()
            ->when(request('search'), fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            }))
            ->when(request('league'), fn ($query, $league) => $query->where('league', $league))
            ->orderBy('league')
            ->orderBy('name')
            ->get();

        return Inertia::render('League/Teams/Index', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'teams' => $teams,
            'leagues' => Team::query()->select('league')->distinct()->whereNotNull('league')->orderBy('league')->pluck('league'),
            'filters' => request()->only(['search', 'league']),
        ]);
    }
}
