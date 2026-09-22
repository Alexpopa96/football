<?php

namespace App\Services;

use App\Models\Championship;

class StandingsCalculator
{
    /**
     * Compute standings for a championship. Expects `entries.user`, `entries.team`
     * and `matches` to be loaded (or loads them if missing).
     *
     * @return array<int, array<string, mixed>>
     */
    public function calculate(Championship $championship): array
    {
        $championship->loadMissing(['entries.user', 'entries.team', 'matches']);

        $table = [];

        foreach ($championship->entries as $entry) {
            $table[$entry->id] = [
                'entry_id' => $entry->id,
                'user' => $entry->user,
                'team' => $entry->team,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
            ];
        }

        foreach ($championship->matches as $match) {
            if (! $match->isPlayed()) {
                continue;
            }

            $home = &$table[$match->home_entry_id];
            $away = &$table[$match->away_entry_id];

            if (! $home || ! $away) {
                continue;
            }

            $home['played']++;
            $away['played']++;
            $home['goals_for'] += $match->home_score;
            $home['goals_against'] += $match->away_score;
            $away['goals_for'] += $match->away_score;
            $away['goals_against'] += $match->home_score;

            if ($match->home_score > $match->away_score) {
                $home['won']++;
                $home['points'] += 3;
                $away['lost']++;
            } elseif ($match->home_score < $match->away_score) {
                $away['won']++;
                $away['points'] += 3;
                $home['lost']++;
            } else {
                $home['drawn']++;
                $away['drawn']++;
                $home['points']++;
                $away['points']++;
            }

            unset($home, $away);
        }

        foreach ($table as &$row) {
            $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
        }
        unset($row);

        $rows = array_values($table);

        usort($rows, function ($a, $b) {
            return [$b['points'], $b['goal_difference'], $b['goals_for']]
                <=> [$a['points'], $a['goal_difference'], $a['goals_for']];
        });

        foreach ($rows as $index => &$row) {
            $row['position'] = $index + 1;
        }
        unset($row);

        return $rows;
    }
}
