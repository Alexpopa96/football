<?php

namespace App\Support;

class BetMarkets
{
    public const MATCH_RESULT = 'match_result';

    public const CORRECT_SCORE = 'correct_score';

    public const CORRECT_SCORE_MAX_GOALS = 6;

    public const ODDS = 2.0;

    public static function selections(): array
    {
        return [
            self::MATCH_RESULT => ['home', 'draw', 'away'],
            self::CORRECT_SCORE => self::correctScoreOptions(),
        ];
    }

    public static function correctScoreOptions(): array
    {
        $options = [];

        for ($home = 0; $home <= self::CORRECT_SCORE_MAX_GOALS; $home++) {
            for ($away = 0; $away <= self::CORRECT_SCORE_MAX_GOALS; $away++) {
                $options[] = "{$home}-{$away}";
            }
        }

        $options[] = 'other';

        return $options;
    }

    public static function isValidSelection(string $market, string $selection): bool
    {
        return in_array($selection, self::selections()[$market] ?? [], true);
    }

    public static function oddsFor(string $market, string $selection): ?float
    {
        return self::isValidSelection($market, $selection) ? self::ODDS : null;
    }

    public static function wins(string $market, string $selection, int $homeScore, int $awayScore): bool
    {
        return match ($market) {
            self::MATCH_RESULT => $selection === match (true) {
                $homeScore > $awayScore => 'home',
                $homeScore < $awayScore => 'away',
                default => 'draw',
            },
            self::CORRECT_SCORE => $selection === 'other'
                ? ! in_array("{$homeScore}-{$awayScore}", self::correctScoreOptions(), true)
                : $selection === "{$homeScore}-{$awayScore}",
            default => false,
        };
    }
}
