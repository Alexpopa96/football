<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cups', function (Blueprint $table) {
            $table->foreignId('winner_entry_id')->nullable()->after('wheel_pool_team_ids')->constrained('cup_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('winner_entry_id');
        });
    }
};
