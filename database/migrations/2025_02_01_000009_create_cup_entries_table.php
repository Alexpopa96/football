<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cup_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('selection_method', ['manual', 'wheel'])->nullable();
            $table->timestamps();

            $table->unique(['cup_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cup_entries');
    }
};
