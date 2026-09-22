<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_bets', function (Blueprint $table) {
            $table->dropUnique('match_bets_unique_pick');
        });

        // A user can now hold only one bet per match — keep the most recent pick per (betable, user).
        DB::statement('
            DELETE b1 FROM match_bets b1
            INNER JOIN match_bets b2
                ON b1.betable_type = b2.betable_type
                AND b1.betable_id = b2.betable_id
                AND b1.user_id = b2.user_id
                AND b1.id < b2.id
        ');

        Schema::table('match_bets', function (Blueprint $table) {
            $table->unique(['betable_type', 'betable_id', 'user_id'], 'match_bets_unique_pick');
        });
    }

    public function down(): void
    {
        Schema::table('match_bets', function (Blueprint $table) {
            $table->dropUnique('match_bets_unique_pick');
            $table->unique(['betable_type', 'betable_id', 'user_id', 'market'], 'match_bets_unique_pick');
        });
    }
};
