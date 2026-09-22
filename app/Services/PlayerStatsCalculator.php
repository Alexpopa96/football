<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\User;

class PlayerStatsCalculator
{
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
            ];
        }

        $championships = Championship::with(['entries.user', 'matches'])->get();
        $standingsCalculator = new StandingsCalculator;

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

                $homeUserId = $homeEntry->user_id;
                $awayUserId = $awayEntry->user_id;

                if (isset($stats[$homeUserId])) {
                    $stats[$homeUserId]['played']++;
                    $stats[$homeUserId]['goals_for'] += $match->home_score;
                    $stats[$homeUserId]['goals_against'] += $match->away_score;
                }

                if (isset($stats[$awayUserId])) {
                    $stats[$awayUserId]['played']++;
                    $stats[$awayUserId]['goals_for'] += $match->away_score;
                    $stats[$awayUserId]['goals_against'] += $match->home_score;
                }

                if ($match->home_score > $match->away_score) {
                    if (isset($stats[$homeUserId])) {
                        $stats[$homeUserId]['won']++;
                    }
                    if (isset($stats[$awayUserId])) {
                        $stats[$awayUserId]['lost']++;
                    }
                } elseif ($match->home_score < $match->away_score) {
                    if (isset($stats[$awayUserId])) {
                        $stats[$awayUserId]['won']++;
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

            if ($championship->status === 'completed') {
                $standings = $standingsCalculator->calculate($championship);
                $winner = collect($standings)->firstWhere('position', 1);
                $winnerUserId = $winner['user']->id ?? null;

                if ($winnerUserId && isset($stats[$winnerUserId])) {
                    $stats[$winnerUserId]['championships_won']++;
                }
            }
        }

        return collect($stats)
            ->map(function ($row) {
                $row['championships_played'] = count($row['championships']);
                unset($row['championships']);
                $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
                $row['win_rate'] = $row['played'] > 0 ? round($row['won'] / $row['played'] * 100) : 0;

                return $row;
            })
            ->values()
            ->all();
    }
}
