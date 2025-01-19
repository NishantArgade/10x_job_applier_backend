<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'nishantargade01@gmail.com'],
            [
                'name' => 'Nishant Argade',
                'password' => Hash::make('123'),
            ]);

        $user->assignRole(Role::ADMIN);
    }
}
