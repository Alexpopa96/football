<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendly_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('away_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedSmallInteger('home_score');
            $table->unsignedSmallInteger('away_score');
            $table->timestamp('played_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendly_matches');
    }
};
