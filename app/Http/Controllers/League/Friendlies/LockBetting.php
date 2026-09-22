<?php

namespace App\Http\Controllers\League\Friendlies;

use App\Http\Controllers\Controller;
use App\Models\FriendlyMatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class LockBetting extends Controller
{
    public function __invoke(FriendlyMatch $friendly)
    {
        abort_unless(
            $friendly->created_by === Auth::id() || Auth::user()->can('manage league'),
            403
        );
        abort_unless($friendly->status === 'live', 422, 'Meciul nu e live.');
        abort_if($friendly->betting_locked_at, 422, 'Pariurile sunt deja blocate.');

        $friendly->update(['betting_locked_at' => now()]);

        return Redirect::back()->with(['success' => ['message' => 'Pariurile au fost blocate.']]);
    }
}
