<?php

use App\Http\Controllers\Auth\PlayerLoginController;
use App\Http\Controllers\League\Championships\AssignTeam;
use App\Http\Controllers\League\Championships\Bets\Destroy as ChampionshipsBetsDestroy;
use App\Http\Controllers\League\Championships\Bets\Store as ChampionshipsBetsStore;
use App\Http\Controllers\League\Championships\Create as ChampionshipsCreate;
use App\Http\Controllers\League\Championships\GenerateFixtures;
use App\Http\Controllers\League\Championships\Index as ChampionshipsIndex;
use App\Http\Controllers\League\Championships\LockBetting as ChampionshipsLockBetting;
use App\Http\Controllers\League\Championships\RecordScore;
use App\Http\Controllers\League\Championships\SelectTeams;
use App\Http\Controllers\League\Championships\SetWheelPool;
use App\Http\Controllers\League\Championships\Show as ChampionshipsShow;
use App\Http\Controllers\League\Championships\SpinWheel;
use App\Http\Controllers\League\Championships\StartMatch;
use App\Http\Controllers\League\Championships\Store as ChampionshipsStore;
use App\Http\Controllers\League\Cups\AssignTeam as CupsAssignTeam;
use App\Http\Controllers\League\Cups\Bets\Destroy as CupsBetsDestroy;
use App\Http\Controllers\League\Cups\Bets\Store as CupsBetsStore;
use App\Http\Controllers\League\Cups\Create as CupsCreate;
use App\Http\Controllers\League\Cups\GenerateFixtures as CupsGenerateFixtures;
use App\Http\Controllers\League\Cups\Index as CupsIndex;
use App\Http\Controllers\League\Cups\LockBetting as CupsLockBetting;
use App\Http\Controllers\League\Cups\RecordScore as CupsRecordScore;
use App\Http\Controllers\League\Cups\SelectTeams as CupsSelectTeams;
use App\Http\Controllers\League\Cups\SetWheelPool as CupsSetWheelPool;
use App\Http\Controllers\League\Cups\Show as CupsShow;
use App\Http\Controllers\League\Cups\SpinWheel as CupsSpinWheel;
use App\Http\Controllers\League\Cups\StartMatch as CupsStartMatch;
use App\Http\Controllers\League\Cups\Store as CupsStore;
use App\Http\Controllers\League\Dashboard;
use App\Http\Controllers\League\Friendlies\Bets\Destroy as FriendliesBetsDestroy;
use App\Http\Controllers\League\Friendlies\Bets\Store as FriendliesBetsStore;
use App\Http\Controllers\League\Friendlies\Destroy as FriendliesDestroy;
use App\Http\Controllers\League\Friendlies\Index as FriendliesIndex;
use App\Http\Controllers\League\Friendlies\LockBetting as FriendliesLockBetting;
use App\Http\Controllers\League\Friendlies\StartLive as FriendliesStartLive;
use App\Http\Controllers\League\Friendlies\Store as FriendliesStore;
use App\Http\Controllers\League\Friendlies\UpdateScore as FriendliesUpdateScore;
use App\Http\Controllers\League\Players\Show as PlayersShow;
use App\Http\Controllers\League\Predictions\Store as PredictionsStore;
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

        Route::get('players/{player}', PlayersShow::class)->name('players.show')->middleware('can:view league');

        Route::prefix('friendlies')->as('friendlies.')->group(function () {
            Route::get('', FriendliesIndex::class)->name('')->middleware('can:view league');
            Route::post('store', FriendliesStore::class)->name('store')->middleware('can:view league');
            Route::post('start-live', FriendliesStartLive::class)->name('start-live')->middleware('can:view league');
            Route::put('{friendly}/score', FriendliesUpdateScore::class)->name('score')->middleware('can:view league');
            Route::put('{friendly}/lock-betting', FriendliesLockBetting::class)->name('lock-betting')->middleware('can:view league');
            Route::post('{friendly}/bets', FriendliesBetsStore::class)->name('bets.store')->middleware('can:view league');
            Route::delete('{friendly}/bets', FriendliesBetsDestroy::class)->name('bets.destroy')->middleware('can:view league');
            Route::delete('{friendly}', FriendliesDestroy::class)->name('destroy')->middleware('can:view league');
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
            Route::put('{championship}/matches/{match}/start', StartMatch::class)->name('matches.start')->middleware('can:manage league');
            Route::put('{championship}/matches/{match}/lock-betting', ChampionshipsLockBetting::class)->name('matches.lock-betting')->middleware('can:manage league');
            Route::post('{championship}/matches/{match}/predict', PredictionsStore::class)->name('predict')->middleware('can:view league');
            Route::post('{championship}/matches/{match}/bets', ChampionshipsBetsStore::class)->name('matches.bets.store')->middleware('can:view league');
            Route::delete('{championship}/matches/{match}/bets', ChampionshipsBetsDestroy::class)->name('matches.bets.destroy')->middleware('can:view league');
        });

        Route::prefix('cups')->as('cups.')->group(function () {
            Route::get('', CupsIndex::class)->name('')->middleware('can:view league');
            Route::get('create', CupsCreate::class)->name('create')->middleware('can:manage league');
            Route::post('store', CupsStore::class)->name('store')->middleware('can:manage league');
            Route::get('{cup}', CupsShow::class)->name('show')->middleware('can:view league');
            Route::get('{cup}/select-teams', CupsSelectTeams::class)->name('select-teams')->middleware('can:manage league');
            Route::put('{cup}/wheel-pool', CupsSetWheelPool::class)->name('wheel-pool')->middleware('can:manage league');
            Route::put('{cup}/entries/{entry}/assign', CupsAssignTeam::class)->name('assign')->middleware('can:manage league');
            Route::post('{cup}/entries/{entry}/spin', CupsSpinWheel::class)->name('spin')->middleware('can:manage league');
            Route::post('{cup}/generate', CupsGenerateFixtures::class)->name('generate')->middleware('can:manage league');
            Route::put('{cup}/matches/{match}/score', CupsRecordScore::class)->name('score')->middleware('can:manage league');
            Route::put('{cup}/matches/{match}/start', CupsStartMatch::class)->name('matches.start')->middleware('can:manage league');
            Route::put('{cup}/matches/{match}/lock-betting', CupsLockBetting::class)->name('matches.lock-betting')->middleware('can:manage league');
            Route::post('{cup}/matches/{match}/bets', CupsBetsStore::class)->name('matches.bets.store')->middleware('can:view league');
            Route::delete('{cup}/matches/{match}/bets', CupsBetsDestroy::class)->name('matches.bets.destroy')->middleware('can:view league');
        });
    });
