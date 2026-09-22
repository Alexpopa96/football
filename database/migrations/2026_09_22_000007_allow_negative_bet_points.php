<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_bets', function (Blueprint $table) {
            $table->tinyInteger('points_awarded')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('bet_points')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('match_bets', function (Blueprint $table) {
            $table->unsignedTinyInteger('points_awarded')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('bet_points')->default(0)->change();
        });
    }
};
