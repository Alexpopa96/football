<?php

namespace App\Http\Controllers\League\Friendlies;

use App\Http\Controllers\Controller;
use App\Models\FriendlyMatch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class Index extends Controller
{
    public function __invoke()
    {
        $userId = Auth::id();

        return Inertia::render('League/Friendlies/Index', [
            'can' => [
                'manage' => Auth::user()->can('manage league'),
            ],
            'matches' => FriendlyMatch::query()
                ->with(['homeUser', 'awayUser', 'homeTeam', 'awayTeam', 'bets.user'])
                ->orderByRaw("status = 'live' desc")
                ->latest('played_at')
                ->latest('started_at')
                ->get()
                ->map(fn ($match) => [
                    ...$match->toArray(),
                    'bets' => $match->status === 'finished' ? $match->bets : [],
                    'my_bets' => $match->bets->where('user_id', $userId)->values(),
                    'can_bet' => $match->status === 'live' && ! $match->betting_locked_at && ! in_array($userId, $match->participantUserIds(), true),
                ]),
            'players' => User::whereNotNull('pin')
                ->where('status', true)
                ->orderBy('id')
                ->get(['id', 'name', 'avatar_emoji', 'avatar_color']),
            'teams' => Team::active()
                ->orderBy('league')
                ->orderBy('name')
                ->get(['id', 'name', 'short_name', 'league', 'crest_url', 'primary_color']),
        ]);
    }
}
