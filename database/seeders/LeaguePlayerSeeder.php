<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LeaguePlayerSeeder extends Seeder
{
    /**
     * Seed the 4 FIFA player accounts, PIN-only login.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Alexandru', 'email' => 'player1@fifa.local', 'pin' => '1111', 'emoji' => '👑', 'color' => '#22c55e', 'role' => 'admin'],
            ['name' => 'Jucător 2', 'email' => 'player2@fifa.local', 'pin' => '2222', 'emoji' => '⚡', 'color' => '#3b82f6', 'role' => 'player'],
            ['name' => 'Jucător 3', 'email' => 'player3@fifa.local', 'pin' => '3333', 'emoji' => '🔥', 'color' => '#f97316', 'role' => 'player'],
            ['name' => 'Jucător 4', 'email' => 'player4@fifa.local', 'pin' => '4444', 'emoji' => '🚀', 'color' => '#a855f7', 'role' => 'player'],
        ])->each(function ($factory) {
            $user = User::firstOrNew(['email' => $factory['email']]);

            $user->fill([
                'name' => $factory['name'],
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'pin' => Hash::make($factory['pin']),
                'avatar_emoji' => $factory['emoji'],
                'avatar_color' => $factory['color'],
                'status' => true,
            ]);

            $user->save();

            $user->syncRoles(Role::where('name', $factory['role'])->first());
        });
    }
}
