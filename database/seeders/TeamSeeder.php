<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Seed the football team catalog from database/seeders/data/teams.json.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/teams.json');

        if (! file_exists($path)) {
            return;
        }

        $teams = json_decode(file_get_contents($path), true) ?? [];

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['name' => $team['name']],
                [
                    'short_name' => $team['short_name'],
                    'country' => $team['country'] ?? null,
                    'league' => $team['league'] ?? null,
                    'crest_url' => $team['crest_url'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
