<?php

namespace App\Services;

use App\Models\ChampionshipMatch;
use App\Models\CupMatch;
use App\Models\FriendlyMatch;
use App\Models\MatchBet;
use App\Models\User;
use App\Support\BetMarkets;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BetStatsCalculator
{
    public const RECENT_BETS_LIMIT = 20;

    /**
     * Per-player betting record, built from settled bets only (pending bets are counted separately).
     */
    public function leaderboard(): array
    {
        $bets = MatchBet::get(['user_id', 'stake', 'points_awarded'])->groupBy('user_id');

        return User::whereNotNull('pin')
            ->orderBy('id')
            ->get(['id', 'name', 'avatar_emoji', 'avatar_color', 'bet_balance', 'bankruptcies_count'])
            ->map(function (User $user) use ($bets) {
                $userBets = $bets->get($user->id, collect());
                $settled = $userBets->whereNotNull('points_awarded');
                $won = $settled->where('points_awarded', '>', 0)->count();

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'avatar_emoji' => $user->avatar_emoji,
                    'avatar_color' => $user->avatar_color,
                    'bet_balance' => $user->bet_balance,
                    'bankruptcies_count' => $user->bankruptcies_count,
                    'bets_settled' => $settled->count(),
                    'bets_pending' => $userBets->whereNull('points_awarded')->count(),
                    'bets_won' => $won,
                    'win_rate' => $settled->count() > 0 ? (int) round($won / $settled->count() * 100) : 0,
                    'total_staked' => (int) $settled->sum('stake'),
                    'net_profit' => (int) $settled->sum('points_awarded'),
                    'biggest_win' => max(0, (int) $settled->max('points_awarded')),
                ];
            })
            ->values()
            ->all();
    }

    public function recentBets(): array
    {
        return MatchBet::whereNotNull('points_awarded')
            ->with([
                'user:id,name,avatar_emoji,avatar_color',
                'betable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    ChampionshipMatch::class => ['homeEntry.team', 'awayEntry.team'],
                    CupMatch::class => ['homeEntry.team', 'awayEntry.team'],
                    FriendlyMatch::class => ['homeTeam', 'awayTeam'],
                ]),
            ])
            ->latest('updated_at')
            ->limit(self::RECENT_BETS_LIMIT)
            ->get()
            ->filter(fn (MatchBet $bet) => $bet->betable && $bet->user)
            ->map(function (MatchBet $bet) {
                $match = $bet->betable;
                [$homeTeam, $awayTeam] = $match instanceof FriendlyMatch
                    ? [$match->homeTeam, $match->awayTeam]
                    : [$match->homeEntry?->team, $match->awayEntry?->team];

                return [
                    'id' => $bet->id,
                    'user_id' => $bet->user_id,
                    'user_name' => $bet->user->name,
                    'avatar_emoji' => $bet->user->avatar_emoji,
                    'avatar_color' => $bet->user->avatar_color,
                    'home_team' => $homeTeam?->short_name,
                    'away_team' => $awayTeam?->short_name,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                    'selection_label' => $this->selectionLabel($bet->market, $bet->selection),
                    'stake' => $bet->stake,
                    'odds' => $bet->odds,
                    'net' => $bet->points_awarded,
                ];
            })
            ->values()
            ->all();
    }

    private function selectionLabel(string $market, string $selection): string
    {
        return match ($market) {
            BetMarkets::MATCH_RESULT => match ($selection) {
                'home' => '1 (Gazdă)',
                'draw' => 'X (Egal)',
                'away' => '2 (Oaspete)',
                default => $selection,
            },
            BetMarkets::CORRECT_SCORE => $selection === 'other' ? 'Scor exact: altul' : "Scor exact {$selection}",
            BetMarkets::HANDICAP => (str_starts_with($selection, 'home') ? 'Gazdă' : 'Oaspete').' +'.substr($selection, strrpos($selection, '_') + 1),
            default => $selection,
        };
    }
}
