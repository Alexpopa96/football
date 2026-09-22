<?php

namespace App\Http\Controllers\League\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class UpdatePin extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $data = Request::validate([
            'current_pin' => ['required', 'digits:4'],
            'pin' => ['required', 'digits:4', 'confirmed'],
        ], [
            'required' => 'Câmpul este obligatoriu.',
            'digits' => 'PIN-ul trebuie să aibă exact 4 cifre.',
            'confirmed' => 'PIN-urile nu coincid.',
        ]);

        if (! $user->verifyPin($data['current_pin'])) {
            throw ValidationException::withMessages([
                'current_pin' => 'PIN-ul actual este incorect.',
            ]);
        }

        $user->update(['pin' => Hash::make($data['pin'])]);

        return Redirect::back()->with(['success' => ['message' => 'PIN-ul a fost actualizat!']]);
    }
}
