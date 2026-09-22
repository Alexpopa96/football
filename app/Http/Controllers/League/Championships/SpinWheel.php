<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipEntry;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class SpinWheel extends Controller
{
    public function __invoke(Championship $championship, ChampionshipEntry $entry): JsonResponse
    {
        abort_unless($entry->championship_id === $championship->id, 404);

        $poolIds = $championship->wheel_pool_team_ids ?? [];

        $takenTeamIds = $championship->entries()
            ->whereNotNull('team_id')
            ->pluck('team_id')
            ->all();

        $remainingIds = array_values(array_diff($poolIds, $takenTeamIds));

        if (empty($remainingIds)) {
            return response()->json(['message' => 'Nu mai sunt echipe disponibile în roată.'], 422);
        }

        $winningTeamId = $remainingIds[array_rand($remainingIds)];
        $winningTeam = Team::findOrFail($winningTeamId);

        $entry->update([
            'team_id' => $winningTeam->id,
            'selection_method' => 'wheel',
        ]);

        return response()->json([
            'team' => $winningTeam,
            'pool' => Team::whereIn('id', $poolIds)->orderBy('name')->get(['id', 'name', 'short_name', 'primary_color', 'crest_url']),
        ]);
    }
}
