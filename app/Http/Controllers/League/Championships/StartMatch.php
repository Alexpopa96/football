<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipMatch;
use Illuminate\Support\Facades\Redirect;

class StartMatch extends Controller
{
    public function __invoke(Championship $championship, ChampionshipMatch $match)
    {
        abort_unless($match->championship_id === $championship->id, 404);
        abort_if($match->isPlayed(), 422, 'Meciul s-a jucat deja.');
        abort_unless($match->status === 'not_started', 422, 'Meciul e deja pornit.');

        $match->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        return Redirect::back()->with(['success' => ['message' => 'Meci pornit! Se poate paria acum.']]);
    }
}
