<?php

namespace App\Services;

class BracketScheduler
{
    /**
     * Generate a single-elimination bracket for the given entry IDs, in seed order
     * (the first two entries meet in slot 0 of round 1, the next two in slot 1, etc).
     *
     * Later rounds have unknown participants until earlier rounds are played, so their
     * fixtures come back with null home/away entry IDs — the caller wires them up as
     * winners are recorded.
     *
     * @param  array<int>  $entryIds
     * @return array<int, array<int, array{slot: int, home_entry_id: ?int, away_entry_id: ?int}>> fixtures keyed by round number (1-based)
     */
    public function generate(array $entryIds): array
    {
        $ids = array_values($entryIds);

        if (count($ids) < 2) {
            return [];
        }

        $rounds = [];
        $round = 1;
        $current = $ids;

        while (count($current) > 1) {
            $matches = [];

            for ($i = 0; $i < count($current); $i += 2) {
                $matches[] = [
                    'slot' => intdiv($i, 2),
                    'home_entry_id' => $current[$i] ?? null,
                    'away_entry_id' => $current[$i + 1] ?? null,
                ];
            }

            $rounds[$round] = $matches;
            $current = array_fill(0, count($matches), null);
            $round++;
        }

        return $rounds;
    }
}
