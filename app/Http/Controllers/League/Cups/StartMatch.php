<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupMatch;
use Illuminate\Support\Facades\Redirect;

class StartMatch extends Controller
{
    public function __invoke(Cup $cup, CupMatch $match)
    {
        abort_unless($match->cup_id === $cup->id, 404);
        abort_unless($match->home_entry_id && $match->away_entry_id, 422, 'Ambele echipe trebuie stabilite înainte de start.');
        abort_if($match->isPlayed(), 422, 'Meciul s-a jucat deja.');
        abort_unless($match->status === 'not_started', 422, 'Meciul e deja pornit.');

        $match->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        return Redirect::back()->with(['success' => ['message' => 'Meci pornit! Se poate paria acum.']]);
    }
}
