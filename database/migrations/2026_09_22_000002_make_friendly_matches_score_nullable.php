<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('friendly_matches', function (Blueprint $table) {
            $table->unsignedSmallInteger('home_score')->nullable()->change();
            $table->unsignedSmallInteger('away_score')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('friendly_matches', function (Blueprint $table) {
            $table->unsignedSmallInteger('home_score')->nullable(false)->change();
            $table->unsignedSmallInteger('away_score')->nullable(false)->change();
        });
    }
};
