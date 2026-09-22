<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('bet_points', 'bet_balance');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('bet_balance')->default(20)->change();
        });

        // Every player starts loaded with a 20-point betting credit.
        DB::table('users')->whereNotNull('pin')->update(['bet_balance' => 20]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('bet_balance')->default(0)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('bet_balance', 'bet_points');
        });
    }
};
