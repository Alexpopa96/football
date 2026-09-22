<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flat-rate bets placed under the old (non-staked) scoring model have no stake/odds
        // data and are incompatible with the new staked-odds payout model.
        DB::table('match_bets')->delete();

        Schema::table('match_bets', function (Blueprint $table) {
            $table->unsignedInteger('stake')->after('selection');
            $table->decimal('odds', 4, 2)->after('stake');
        });

        Schema::table('match_bets', function (Blueprint $table) {
            $table->smallInteger('points_awarded')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('match_bets', function (Blueprint $table) {
            $table->dropColumn(['stake', 'odds']);
            $table->tinyInteger('points_awarded')->nullable()->change();
        });
    }
};
