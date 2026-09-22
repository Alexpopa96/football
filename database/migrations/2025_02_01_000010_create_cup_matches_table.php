<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cup_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cup_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round');
            $table->unsignedTinyInteger('slot');
            $table->foreignId('home_entry_id')->nullable()->constrained('cup_entries')->cascadeOnDelete();
            $table->foreignId('away_entry_id')->nullable()->constrained('cup_entries')->cascadeOnDelete();
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->unsignedTinyInteger('home_penalties')->nullable();
            $table->unsignedTinyInteger('away_penalties')->nullable();
            $table->foreignId('winner_entry_id')->nullable()->constrained('cup_entries')->nullOnDelete();
            $table->foreignId('feeds_into_match_id')->nullable()->constrained('cup_matches')->nullOnDelete();
            $table->enum('feeds_into_side', ['home', 'away'])->nullable();
            $table->timestamp('played_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cup_matches');
    }
};
