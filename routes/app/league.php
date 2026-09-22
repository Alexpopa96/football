<?php

use App\Http\Controllers\Auth\PlayerLoginController;
use App\Http\Controllers\League\Championships\AssignTeam;
use App\Http\Controllers\League\Championships\Create as ChampionshipsCreate;
use App\Http\Controllers\League\Championships\GenerateFixtures;
use App\Http\Controllers\League\Championships\Index as ChampionshipsIndex;
use App\Http\Controllers\League\Championships\RecordScore;
use App\Http\Controllers\League\Championships\SelectTeams;
use App\Http\Controllers\League\Championships\SetWheelPool;
use App\Http\Controllers\League\Championships\Show as ChampionshipsShow;
use App\Http\Controllers\League\Championships\SpinWheel;
use App\Http\Controllers\League\Championships\Store as ChampionshipsStore;
use App\Http\Controllers\League\Dashboard;
use App\Http\Controllers\League\Settings\Show as SettingsShow;
use App\Http\Controllers\League\Settings\UpdatePin as SettingsUpdatePin;
use App\Http\Controllers\League\Stats\Index as StatsIndex;
use App\Http\Controllers\League\Teams\Index as TeamsIndex;
use App\Http\Controllers\League\Teams\Store as TeamsStore;
use App\Http\Controllers\League\Teams\Update as TeamsUpdate;
use Illuminate\Support\Facades\Route;

Route::get('/play', [PlayerLoginController::class, 'show'])->middleware('guest')->name('play');
Route::post('/play/login', [PlayerLoginController::class, 'login'])->middleware('guest')->name('play.login');

Route::middleware(['auth'])
    ->prefix('league')
    ->as('league.')
    ->group(function () {
        Route::get('', Dashboard::class)->name('')->middleware('can:view league');

        Route::prefix('teams')->as('teams.')->group(function () {
            Route::get('', TeamsIndex::class)->name('')->middleware('can:view league');
            Route::post('store', TeamsStore::class)->name('store')->middleware('can:manage league');
            Route::put('{team}/update', TeamsUpdate::class)->name('update')->middleware('can:manage league');
        });

        Route::prefix('settings')->as('settings.')->group(function () {
            Route::get('', SettingsShow::class)->name('')->middleware('can:view league');
            Route::put('pin', SettingsUpdatePin::class)->name('pin')->middleware('can:view league');
        });

        Route::prefix('stats')->as('stats.')->group(function () {
            Route::get('', StatsIndex::class)->name('')->middleware('can:view league');
        });

        Route::prefix('championships')->as('championships.')->group(function () {
            Route::get('', ChampionshipsIndex::class)->name('')->middleware('can:view league');
            Route::get('create', ChampionshipsCreate::class)->name('create')->middleware('can:manage league');
            Route::post('store', ChampionshipsStore::class)->name('store')->middleware('can:manage league');
            Route::get('{championship}', ChampionshipsShow::class)->name('show')->middleware('can:view league');
            Route::get('{championship}/select-teams', SelectTeams::class)->name('select-teams')->middleware('can:manage league');
            Route::put('{championship}/wheel-pool', SetWheelPool::class)->name('wheel-pool')->middleware('can:manage league');
            Route::put('{championship}/entries/{entry}/assign', AssignTeam::class)->name('assign')->middleware('can:manage league');
            Route::post('{championship}/entries/{entry}/spin', SpinWheel::class)->name('spin')->middleware('can:manage league');
            Route::post('{championship}/generate', GenerateFixtures::class)->name('generate')->middleware('can:manage league');
            Route::put('{championship}/matches/{match}/score', RecordScore::class)->name('score')->middleware('can:manage league');
        });
    });
