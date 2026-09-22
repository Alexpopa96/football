<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('predicted_home_score');
            $table->unsignedSmallInteger('predicted_away_score');
            $table->unsignedTinyInteger('points_awarded')->nullable();
            $table->timestamps();

            $table->unique(['championship_match_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_predictions');
    }
};
