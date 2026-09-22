<?php

namespace App\Http\Controllers\League\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupMatch;
use Illuminate\Support\Facades\Redirect;

class LockBetting extends Controller
{
    public function __invoke(Cup $cup, CupMatch $match)
    {
        abort_unless($match->cup_id === $cup->id, 404);
        abort_unless($match->status === 'live', 422, 'Meciul nu e live.');
        abort_if($match->betting_locked_at, 422, 'Pariurile sunt deja blocate.');

        $match->update(['betting_locked_at' => now()]);

        return Redirect::back()->with(['success' => ['message' => 'Pariurile au fost blocate.']]);
    }
}
