<?php

namespace App\Http\Controllers\League\Championships;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Services\RoundRobinScheduler;
use Illuminate\Support\Facades\Redirect;

class GenerateFixtures extends Controller
{
    public function __invoke(Championship $championship, RoundRobinScheduler $scheduler)
    {
        $entries = $championship->entries()->whereNotNull('team_id')->get();

        if ($entries->count() !== $championship->entries()->count() || $entries->count() < 2) {
            return Redirect::back()->withErrors(['team' => 'Toți jucătorii trebuie să aibă o echipă atribuită.']);
        }

        if ($championship->matches()->exists()) {
            return Redirect::to("/league/championships/{$championship->id}")
                ->with(['success' => ['message' => 'Clasamentul a fost deja generat.']]);
        }

        $fixtures = $scheduler->generate($entries->pluck('id')->all());

        foreach ($fixtures as $fixture) {
            $championship->matches()->create($fixture);
        }

        $championship->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return Redirect::to("/league/championships/{$championship->id}")
            ->with(['success' => ['message' => 'Clasamentul a fost generat! Baftă la meciuri.']]);
    }
}
