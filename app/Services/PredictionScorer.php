<?php

namespace App\Services;

use App\Models\ChampionshipMatch;

class PredictionScorer
{
    private const EXACT_SCORE_POINTS = 3;

    private const CORRECT_OUTCOME_POINTS = 1;

    public function scoreMatch(ChampionshipMatch $match): void
    {
        if (! $match->isPlayed()) {
            return;
        }

        $outcome = $this->outcome($match->home_score, $match->away_score);

        foreach ($match->predictions as $prediction) {
            $predictedOutcome = $this->outcome($prediction->predicted_home_score, $prediction->predicted_away_score);

            if (
                $prediction->predicted_home_score === $match->home_score
                && $prediction->predicted_away_score === $match->away_score
            ) {
                $points = self::EXACT_SCORE_POINTS;
            } elseif ($predictedOutcome === $outcome) {
                $points = self::CORRECT_OUTCOME_POINTS;
            } else {
                $points = 0;
            }

            $prediction->update(['points_awarded' => $points]);
        }
    }

    private function outcome(int $homeScore, int $awayScore): string
    {
        return match (true) {
            $homeScore > $awayScore => 'home',
            $homeScore < $awayScore => 'away',
            default => 'draw',
        };
    }
}
