<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PlayerLoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/PlayerLogin', [
            'players' => User::whereNotNull('pin')
                ->where('status', true)
                ->orderBy('id')
                ->get(['id', 'name', 'avatar_emoji', 'avatar_color']),
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'pin' => ['required', 'digits:4'],
        ]);

        $player = User::whereNotNull('pin')
            ->where('status', true)
            ->find($data['user_id']);

        if (! $player || ! $player->verifyPin($data['pin'])) {
            return back()->withErrors(['pin' => 'PIN incorect.']);
        }

        Auth::loginUsingId($player->id);
        $request->session()->regenerate();

        return Redirect::intended('/league');
    }
}
