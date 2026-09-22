<?php

namespace App\Services;

class RoundRobinScheduler
{
    /**
     * Generate a double round-robin (tur-retur) schedule for the given entry IDs.
     *
     * Uses the standard "circle method" for the first leg (tur), then mirrors
     * every fixture with home/away swapped for the second leg (retur).
     *
     * @param  array<int>  $entryIds
     * @return array<int, array{round: int, leg: string, home_entry_id: int, away_entry_id: int}>
     */
    public function generate(array $entryIds): array
    {
        $ids = array_values($entryIds);
        $count = count($ids);

        if ($count < 2) {
            return [];
        }

        // Circle method needs an even number of participants; pad with a "bye" slot.
        $bye = null;
        if ($count % 2 !== 0) {
            $ids[] = $bye;
            $count++;
        }

        $rounds = $count - 1;
        $half = $count / 2;
        $fixtures = [];

        $rotating = $ids;

        for ($round = 1; $round <= $rounds; $round++) {
            for ($i = 0; $i < $half; $i++) {
                $home = $rotating[$i];
                $away = $rotating[$count - 1 - $i];

                if ($home === null || $away === null) {
                    continue;
                }

                // Alternate which side stays "home" so the fixture list feels fair.
                if ($round % 2 === 0) {
                    [$home, $away] = [$away, $home];
                }

                $fixtures[] = [
                    'round' => $round,
                    'leg' => 'tur',
                    'home_entry_id' => $home,
                    'away_entry_id' => $away,
                ];
            }

            // Rotate all but the first element.
            $fixed = $rotating[0];
            $rest = array_slice($rotating, 1);
            array_unshift($rest, array_pop($rest));
            $rotating = array_merge([$fixed], $rest);
        }

        $returFixtures = array_map(function (array $fixture) use ($rounds) {
            return [
                'round' => $fixture['round'] + $rounds,
                'leg' => 'retur',
                'home_entry_id' => $fixture['away_entry_id'],
                'away_entry_id' => $fixture['home_entry_id'],
            ];
        }, $fixtures);

        return array_merge($fixtures, $returFixtures);
    }
}
