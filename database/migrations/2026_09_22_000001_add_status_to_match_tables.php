<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['championship_matches', 'cup_matches', 'friendly_matches'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->enum('status', ['not_started', 'live', 'finished'])->default('not_started')->after('id');
                $table->timestamp('started_at')->nullable()->after('status');
            });

            DB::table($table)->whereNotNull('home_score')->update(['status' => 'finished']);
        }
    }

    public function down(): void
    {
        foreach (['championship_matches', 'cup_matches', 'friendly_matches'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['status', 'started_at']);
            });
        }
    }
};
