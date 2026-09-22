<?php

namespace App\Support;

class BetMarkets
{
    public const MATCH_RESULT = 'match_result';

    public const CORRECT_SCORE = 'correct_score';

    public const HANDICAP = 'handicap';

    public const CORRECT_SCORE_MAX_GOALS = 6;

    /**
     * Goal-difference margins bettable on the handicap market, and the fixed odds for each.
     */
    public const ODDS_BY_HANDICAP_MARGIN = [
        2 => 1.5,
        3 => 2.5,
        4 => 3.5,
    ];

    public const ODDS_BY_MARKET = [
        self::MATCH_RESULT => 2.0,
        self::CORRECT_SCORE => 10.0,
    ];

    public static function selections(): array
    {
        return [
            self::MATCH_RESULT => ['home', 'draw', 'away'],
            self::CORRECT_SCORE => self::correctScoreOptions(),
            self::HANDICAP => self::handicapOptions(),
        ];
    }

    public static function handicapOptions(): array
    {
        $options = [];

        foreach (['home', 'away'] as $side) {
            foreach (array_keys(self::ODDS_BY_HANDICAP_MARGIN) as $margin) {
                $options[] = "{$side}_by_{$margin}";
            }
        }

        return $options;
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
        if (! self::isValidSelection($market, $selection)) {
            return null;
        }

        if ($market === self::HANDICAP) {
            [, $margin] = self::parseHandicapSelection($selection);

            return self::ODDS_BY_HANDICAP_MARGIN[$margin];
        }

        return self::ODDS_BY_MARKET[$market];
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
            self::HANDICAP => self::handicapWins($selection, $homeScore, $awayScore),
            default => false,
        };
    }

    /**
     * A handicap selection like "home_by_3" wins only when that side wins by exactly
     * that goal difference, matching the exact-outcome convention every other market uses.
     */
    private static function handicapWins(string $selection, int $homeScore, int $awayScore): bool
    {
        [$side, $margin] = self::parseHandicapSelection($selection);

        $actualDifference = $side === 'home' ? $homeScore - $awayScore : $awayScore - $homeScore;

        return $actualDifference === $margin;
    }

    private static function parseHandicapSelection(string $selection): array
    {
        [$side, , $margin] = explode('_', $selection);

        return [$side, (int) $margin];
    }
}
