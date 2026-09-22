<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupEntry;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class SpinWheel extends Controller
{
    public function __invoke(Cup $cup, CupEntry $entry): JsonResponse
    {
        abort_unless($entry->cup_id === $cup->id, 404);

        $poolIds = $cup->wheel_pool_team_ids ?? [];

        $takenTeamIds = $cup->entries()
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
