<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\ChampionshipMatch;
use Illuminate\Support\Facades\Redirect;

class LockBetting extends Controller
{
    public function __invoke(Championship $championship, ChampionshipMatch $match)
    {
        abort_unless($match->championship_id === $championship->id, 404);
        abort_unless($match->status === 'live', 422, 'Meciul nu e live.');
        abort_if($match->betting_locked_at, 422, 'Pariurile sunt deja blocate.');

        $match->update(['betting_locked_at' => now()]);

        return Redirect::back()->with(['success' => ['message' => 'Pariurile au fost blocate.']]);
    }
}
