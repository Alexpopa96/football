<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\Cup;
use App\Models\FriendlyMatch;
use App\Models\Team;
use App\Models\User;

class PlayerStatsCalculator
{
    /**
     * Starting Elo rating for a player with no match history yet.
     */
    private const ELO_INITIAL_RATING = 1000;

    /**
     * How much a single result can move a rating. Higher = more volatile.
     */
    private const ELO_K_FACTOR = 32;

    /**
     * A team needs at least this many games (by a player, or overall) before it
     * counts toward "best team" or "cursed"/"lucky" rankings, so a single fluke
     * result doesn't crown or curse a team.
     */
    private const MIN_GAMES_FOR_TEAM_RANKING = 2;

    /**
     * Aggregate career stats for every player with a PIN, across all championships.
     *
     * @return array<int, array<string, mixed>>
     */
    public function leaderboard(): array
    {
        $players = User::whereNotNull('pin')->orderBy('id')->get(['id', 'name', 'avatar_emoji', 'avatar_color', 'status']);

        $stats = [];
        foreach ($players as $player) {
            $stats[$player->id] = [
                'user_id' => $player->id,
                'name' => $player->name,
                'avatar_emoji' => $player->avatar_emoji,
                'avatar_color' => $player->avatar_color,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'championships' => [],
                'championships_won' => 0,
                'cups_won' => 0,
                'rating' => self::ELO_INITIAL_RATING,
                'hattricks' => 0,
                'best_win_margin' => 0,
                'longest_win_streak' => 0,
            ];
        }

        $championships = Championship::with(['entries.user', 'matches'])->get();
        $standingsCalculator = new StandingsCalculator;

        $chronologicalResults = [];

        foreach ($championships as $championship) {
            foreach ($championship->entries as $entry) {
                if ($entry->user_id && isset($stats[$entry->user_id])) {
                    $stats[$entry->user_id]['championships'][$championship->id] = true;
                }
            }

            $entriesById = $championship->entries->keyBy('id');

            foreach ($championship->matches as $match) {
                if (! $match->isPlayed()) {
                    continue;
                }

                $homeEntry = $entriesById->get($match->home_entry_id);
                $awayEntry = $entriesById->get($match->away_entry_id);

                if (! $homeEntry || ! $awayEntry) {
                    continue;
                }

                $this->applyResult($stats, $homeEntry->user_id, $awayEntry->user_id, $match->home_score, $match->away_score);

                $chronologicalResults[] = [
                    'played_at' => $match->played_at,
                    'home_user_id' => $homeEntry->user_id,
                    'away_user_id' => $awayEntry->user_id,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                ];
            }

            if ($championship->status === 'completed') {
                $standings = $standingsCalculator->calculate($championship);
                $winner = collect($standings)->firstWhere('position', 1);
                $winnerUserId = $winner['user']->id ?? null;

                if ($winnerUserId && isset($stats[$winnerUserId])) {
                    $stats[$winnerUserId]['championships_won']++;
                }
            }
        }

        foreach (Cup::with(['entries.user', 'matches', 'winnerEntry'])->get() as $cup) {
            $entriesById = $cup->entries->keyBy('id');

            foreach ($cup->matches as $match) {
                if (! $match->isPlayed()) {
                    continue;
                }

                $homeEntry = $entriesById->get($match->home_entry_id);
                $awayEntry = $entriesById->get($match->away_entry_id);

                if (! $homeEntry || ! $awayEntry) {
                    continue;
                }

                $this->applyResult($stats, $homeEntry->user_id, $awayEntry->user_id, $match->home_score, $match->away_score);

                $chronologicalResults[] = [
                    'played_at' => $match->played_at,
                    'home_user_id' => $homeEntry->user_id,
                    'away_user_id' => $awayEntry->user_id,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                ];
            }

            if ($cup->status === 'completed' && $cup->winnerEntry) {
                $winnerUserId = $cup->winnerEntry->user_id;

                if ($winnerUserId && isset($stats[$winnerUserId])) {
                    $stats[$winnerUserId]['cups_won']++;
                }
            }
        }

        foreach (FriendlyMatch::all() as $friendly) {
            $this->applyResult($stats, $friendly->home_user_id, $friendly->away_user_id, $friendly->home_score, $friendly->away_score);

            $chronologicalResults[] = [
                'played_at' => $friendly->played_at,
                'home_user_id' => $friendly->home_user_id,
                'away_user_id' => $friendly->away_user_id,
                'home_score' => $friendly->home_score,
                'away_score' => $friendly->away_score,
            ];
        }

        $this->applyEloRatings($stats, $chronologicalResults);
        $this->applyWinStreaks($stats, $chronologicalResults);

        return collect($stats)
            ->map(function ($row) {
                $row['championships_played'] = count($row['championships']);
                unset($row['championships']);
                $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
                $row['win_rate'] = $row['played'] > 0 ? round($row['won'] / $row['played'] * 100) : 0;
                $row['rating'] = (int) round($row['rating']);

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * Replay every result in chronological order and update Elo ratings after each one,
     * so the rating reflects current form rather than career totals like trophies do.
     *
     * @param  array<int, array<string, mixed>>  $stats
     * @param  array<int, array<string, mixed>>  $matches
     */
    private function applyEloRatings(array &$stats, array $matches): void
    {
        usort($matches, fn ($a, $b) => $a['played_at'] <=> $b['played_at']);

        $ratings = [];

        foreach ($matches as $match) {
            $homeId = $match['home_user_id'];
            $awayId = $match['away_user_id'];

            $homeRating = $ratings[$homeId] ??= self::ELO_INITIAL_RATING;
            $awayRating = $ratings[$awayId] ??= self::ELO_INITIAL_RATING;

            $expectedHome = 1 / (1 + 10 ** (($awayRating - $homeRating) / 400));

            if ($match['home_score'] > $match['away_score']) {
                $actualHome = 1.0;
            } elseif ($match['home_score'] < $match['away_score']) {
                $actualHome = 0.0;
            } else {
                $actualHome = 0.5;
            }

            $shift = self::ELO_K_FACTOR * ($actualHome - $expectedHome);

            $ratings[$homeId] = $homeRating + $shift;
            $ratings[$awayId] = $awayRating - $shift;
        }

        foreach ($ratings as $userId => $rating) {
            if (isset($stats[$userId])) {
                $stats[$userId]['rating'] = $rating;
            }
        }
    }

    /**
     * Replay every result in chronological order and track each player's longest run
     * of consecutive wins, since that depends on match order rather than totals.
     *
     * @param  array<int, array<string, mixed>>  $stats
     * @param  array<int, array<string, mixed>>  $matches
     */
    private function applyWinStreaks(array &$stats, array $matches): void
    {
        usort($matches, fn ($a, $b) => $a['played_at'] <=> $b['played_at']);

        $currentStreaks = [];

        foreach ($matches as $match) {
            $homeId = $match['home_user_id'];
            $awayId = $match['away_user_id'];

            if ($match['home_score'] > $match['away_score']) {
                $currentStreaks[$homeId] = ($currentStreaks[$homeId] ?? 0) + 1;
                $currentStreaks[$awayId] = 0;
            } elseif ($match['home_score'] < $match['away_score']) {
                $currentStreaks[$awayId] = ($currentStreaks[$awayId] ?? 0) + 1;
                $currentStreaks[$homeId] = 0;
            } else {
                $currentStreaks[$homeId] = 0;
                $currentStreaks[$awayId] = 0;
            }

            foreach ([$homeId, $awayId] as $userId) {
                if (isset($stats[$userId])) {
                    $stats[$userId]['longest_win_streak'] = max($stats[$userId]['longest_win_streak'], $currentStreaks[$userId]);
                }
            }
        }
    }

    /**
     * Tally a single played result (from a championship match or a friendly) into $stats.
     */
    private function applyResult(array &$stats, int $homeUserId, int $awayUserId, int $homeScore, int $awayScore): void
    {
        if (isset($stats[$homeUserId])) {
            $stats[$homeUserId]['played']++;
            $stats[$homeUserId]['goals_for'] += $homeScore;
            $stats[$homeUserId]['goals_against'] += $awayScore;
            if ($homeScore >= 3) {
                $stats[$homeUserId]['hattricks']++;
            }
        }

        if (isset($stats[$awayUserId])) {
            $stats[$awayUserId]['played']++;
            $stats[$awayUserId]['goals_for'] += $awayScore;
            $stats[$awayUserId]['goals_against'] += $homeScore;
            if ($awayScore >= 3) {
                $stats[$awayUserId]['hattricks']++;
            }
        }

        if ($homeScore > $awayScore) {
            if (isset($stats[$homeUserId])) {
                $stats[$homeUserId]['won']++;
                $stats[$homeUserId]['best_win_margin'] = max($stats[$homeUserId]['best_win_margin'], $homeScore - $awayScore);
            }
            if (isset($stats[$awayUserId])) {
                $stats[$awayUserId]['lost']++;
            }
        } elseif ($homeScore < $awayScore) {
            if (isset($stats[$awayUserId])) {
                $stats[$awayUserId]['won']++;
                $stats[$awayUserId]['best_win_margin'] = max($stats[$awayUserId]['best_win_margin'], $awayScore - $homeScore);
            }
            if (isset($stats[$homeUserId])) {
                $stats[$homeUserId]['lost']++;
            }
        } else {
            if (isset($stats[$homeUserId])) {
                $stats[$homeUserId]['drawn']++;
            }
            if (isset($stats[$awayUserId])) {
                $stats[$awayUserId]['drawn']++;
            }
        }
    }

    /**
     * For every player, break their record down by real-world team, so we can surface
     * their best team. Also aggregate the same records across everyone to find the
     * "cursed" (worst win rate) and "lucky" (best win rate) teams overall.
     *
     * @return array{players: array<int, array<string, mixed>>, cursed_teams: array<int, array<string, mixed>>, lucky_teams: array<int, array<string, mixed>>}
     */
    public function teamStats(): array
    {
        $players = User::whereNotNull('pin')->orderBy('id')->get(['id', 'name', 'avatar_emoji', 'avatar_color']);
        $teams = Team::all()->keyBy('id');

        $byPlayerTeam = [];
        $byTeam = [];

        $record = function (?int $teamId, ?int $userId, int $goalsFor, int $goalsAgainst) use (&$byPlayerTeam, &$byTeam) {
            if (! $teamId || ! $userId) {
                return;
            }

            $byPlayerTeam[$userId][$teamId] ??= $this->emptyTeamTally();
            $byTeam[$teamId] ??= $this->emptyTeamTally();

            $this->tallyTeamResult($byPlayerTeam[$userId][$teamId], $goalsFor, $goalsAgainst);
            $this->tallyTeamResult($byTeam[$teamId], $goalsFor, $goalsAgainst);
        };

        foreach (Championship::with(['entries', 'matches'])->get() as $championship) {
            $entriesById = $championship->entries->keyBy('id');

            foreach ($championship->matches as $match) {
                if (! $match->isPlayed()) {
                    continue;
                }

                $homeEntry = $entriesById->get($match->home_entry_id);
                $awayEntry = $entriesById->get($match->away_entry_id);

                if (! $homeEntry || ! $awayEntry) {
                    continue;
                }

                $record($homeEntry->team_id, $homeEntry->user_id, $match->home_score, $match->away_score);
                $record($awayEntry->team_id, $awayEntry->user_id, $match->away_score, $match->home_score);
            }
        }

        foreach (Cup::with(['entries', 'matches'])->get() as $cup) {
            $entriesById = $cup->entries->keyBy('id');

            foreach ($cup->matches as $match) {
                if (! $match->isPlayed()) {
                    continue;
                }

                $homeEntry = $entriesById->get($match->home_entry_id);
                $awayEntry = $entriesById->get($match->away_entry_id);

                if (! $homeEntry || ! $awayEntry) {
                    continue;
                }

                $record($homeEntry->team_id, $homeEntry->user_id, $match->home_score, $match->away_score);
                $record($awayEntry->team_id, $awayEntry->user_id, $match->away_score, $match->home_score);
            }
        }

        foreach (FriendlyMatch::all() as $friendly) {
            $record($friendly->home_team_id, $friendly->home_user_id, $friendly->home_score, $friendly->away_score);
            $record($friendly->away_team_id, $friendly->away_user_id, $friendly->away_score, $friendly->home_score);
        }

        $describeTeam = function (int $teamId, array $tally) use ($teams) {
            $team = $teams->get($teamId);

            return array_merge($tally, [
                'team_id' => $teamId,
                'name' => $team->name ?? 'Echipă ștearsă',
                'short_name' => $team->short_name ?? '???',
                'crest_url' => $team->crest_url ?? null,
                'primary_color' => $team->primary_color ?? null,
                'win_rate' => $tally['played'] > 0 ? round($tally['won'] / $tally['played'] * 100) : 0,
            ]);
        };

        $playerTeamStats = [];

        foreach ($players as $player) {
            if (empty($byPlayerTeam[$player->id])) {
                continue;
            }

            $teamRows = [];
            foreach ($byPlayerTeam[$player->id] as $teamId => $tally) {
                $teamRows[] = $describeTeam($teamId, $tally);
            }

            usort($teamRows, fn ($a, $b) => $b['win_rate'] <=> $a['win_rate'] ?: $b['played'] <=> $a['played']);

            $qualified = array_values(array_filter(
                $teamRows,
                fn ($row) => $row['played'] >= self::MIN_GAMES_FOR_TEAM_RANKING
            ));

            $playerTeamStats[] = [
                'user_id' => $player->id,
                'name' => $player->name,
                'avatar_emoji' => $player->avatar_emoji,
                'avatar_color' => $player->avatar_color,
                'best_team' => $qualified[0] ?? null,
                'teams' => $teamRows,
            ];
        }

        $rankedTeams = [];
        foreach ($byTeam as $teamId => $tally) {
            if ($tally['played'] < self::MIN_GAMES_FOR_TEAM_RANKING) {
                continue;
            }

            $rankedTeams[] = $describeTeam($teamId, $tally);
        }

        $cursedTeams = $rankedTeams;
        usort($cursedTeams, fn ($a, $b) => $a['win_rate'] <=> $b['win_rate'] ?: $a['goal_difference'] <=> $b['goal_difference']);

        $luckyTeams = $rankedTeams;
        usort($luckyTeams, fn ($a, $b) => $b['win_rate'] <=> $a['win_rate'] ?: $b['goal_difference'] <=> $a['goal_difference']);

        return [
            'players' => $playerTeamStats,
            'cursed_teams' => array_slice($cursedTeams, 0, 5),
            'lucky_teams' => array_slice($luckyTeams, 0, 5),
        ];
    }

    /**
     * A fresh played/won/drawn/lost/goals tally for one team's record.
     */
    private function emptyTeamTally(): array
    {
        return [
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
        ];
    }

    /**
     * Tally a single played result into a team's record (either one player's record
     * with that team, or the team's record across every player who's used it).
     */
    private function tallyTeamResult(array &$tally, int $goalsFor, int $goalsAgainst): void
    {
        $tally['played']++;
        $tally['goals_for'] += $goalsFor;
        $tally['goals_against'] += $goalsAgainst;
        $tally['goal_difference'] = $tally['goals_for'] - $tally['goals_against'];

        if ($goalsFor > $goalsAgainst) {
            $tally['won']++;
        } elseif ($goalsFor < $goalsAgainst) {
            $tally['lost']++;
        } else {
            $tally['drawn']++;
        }
    }

    /**
     * The championships and cups a single player has won, for a trophy cabinet.
     *
     * @return array{championships: array<int, array<string, mixed>>, cups: array<int, array<string, mixed>>}
     */
    public function trophiesFor(int $userId): array
    {
        $standingsCalculator = new StandingsCalculator;

        $championships = Championship::where('status', 'completed')
            ->with(['entries.user', 'entries.team', 'matches'])
            ->get()
            ->filter(function ($championship) use ($standingsCalculator, $userId) {
                $winner = collect($standingsCalculator->calculate($championship))->firstWhere('position', 1);

                return ($winner['user']->id ?? null) === $userId;
            })
            ->map(fn ($championship) => [
                'id' => $championship->id,
                'name' => $championship->name,
                'completed_at' => $championship->completed_at,
            ])
            ->values()
            ->all();

        $cups = Cup::where('status', 'completed')
            ->with('winnerEntry.user')
            ->get()
            ->filter(fn ($cup) => ($cup->winnerEntry->user_id ?? null) === $userId)
            ->map(fn ($cup) => [
                'id' => $cup->id,
                'name' => $cup->name,
                'completed_at' => $cup->completed_at,
            ])
            ->values()
            ->all();

        return [
            'championships' => $championships,
            'cups' => $cups,
        ];
    }

    /**
     * One player's Elo rating after each of their matches, in chronological order.
     * Elo is relative, so this replays every match in the league (not just the
     * player's own) to keep opponent strength accurate, then keeps only the
     * points where this player was involved.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ratingHistoryFor(int $userId): array
    {
        $matches = [];

        foreach (Championship::with('entries', 'matches')->get() as $championship) {
            $entriesById = $championship->entries->keyBy('id');

            foreach ($championship->matches as $match) {
                if (! $match->isPlayed()) {
                    continue;
                }

                $homeEntry = $entriesById->get($match->home_entry_id);
                $awayEntry = $entriesById->get($match->away_entry_id);

                if (! $homeEntry || ! $awayEntry) {
                    continue;
                }

                $matches[] = [
                    'played_at' => $match->played_at,
                    'home_user_id' => $homeEntry->user_id,
                    'away_user_id' => $awayEntry->user_id,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                ];
            }
        }

        foreach (Cup::with('entries', 'matches')->get() as $cup) {
            $entriesById = $cup->entries->keyBy('id');

            foreach ($cup->matches as $match) {
                if (! $match->isPlayed()) {
                    continue;
                }

                $homeEntry = $entriesById->get($match->home_entry_id);
                $awayEntry = $entriesById->get($match->away_entry_id);

                if (! $homeEntry || ! $awayEntry) {
                    continue;
                }

                $matches[] = [
                    'played_at' => $match->played_at,
                    'home_user_id' => $homeEntry->user_id,
                    'away_user_id' => $awayEntry->user_id,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                ];
            }
        }

        foreach (FriendlyMatch::all() as $friendly) {
            $matches[] = [
                'played_at' => $friendly->played_at,
                'home_user_id' => $friendly->home_user_id,
                'away_user_id' => $friendly->away_user_id,
                'home_score' => $friendly->home_score,
                'away_score' => $friendly->away_score,
            ];
        }

        usort($matches, fn ($a, $b) => $a['played_at'] <=> $b['played_at']);

        $ratings = [];
        $history = [];

        foreach ($matches as $match) {
            $homeId = $match['home_user_id'];
            $awayId = $match['away_user_id'];

            $homeRating = $ratings[$homeId] ??= self::ELO_INITIAL_RATING;
            $awayRating = $ratings[$awayId] ??= self::ELO_INITIAL_RATING;

            $expectedHome = 1 / (1 + 10 ** (($awayRating - $homeRating) / 400));

            if ($match['home_score'] > $match['away_score']) {
                $actualHome = 1.0;
            } elseif ($match['home_score'] < $match['away_score']) {
                $actualHome = 0.0;
            } else {
                $actualHome = 0.5;
            }

            $shift = self::ELO_K_FACTOR * ($actualHome - $expectedHome);

            $ratings[$homeId] = $homeRating + $shift;
            $ratings[$awayId] = $awayRating - $shift;

            if ($homeId === $userId || $awayId === $userId) {
                $isHome = $homeId === $userId;
                $ownScore = $isHome ? $match['home_score'] : $match['away_score'];
                $opponentScore = $isHome ? $match['away_score'] : $match['home_score'];

                $history[] = [
                    'played_at' => $match['played_at'],
                    'rating' => (int) round($ratings[$userId]),
                    'opponent_id' => $isHome ? $awayId : $homeId,
                    'result' => $ownScore > $opponentScore ? 'W' : ($ownScore < $opponentScore ? 'L' : 'D'),
                ];
            }
        }

        return $history;
    }
}
