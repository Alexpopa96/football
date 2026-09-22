<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE match_bets MODIFY market ENUM(
            'match_result',
            'double_chance',
            'total_goals',
            'odd_even',
            'btts',
            'clean_sheet_home',
            'clean_sheet_away',
            'correct_score'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE match_bets MODIFY market ENUM('match_result', 'total_goals', 'btts') NOT NULL");
    }
};
