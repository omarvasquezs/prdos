<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the two required roles
        $admin = Role::firstOrCreate(['name' => 'administrador']);
        $caja = Role::firstOrCreate(['name' => 'caja']);
        $contador = Role::firstOrCreate(['name' => 'contador']);

        // Attach roles to seeded users if they exist.
        // Assumption: user 'admin' exists and will receive both roles; 'test' gets 'caja'.
        $adminUser = User::where('username', 'admin')->first();
        $testUser = User::where('username', 'test')->first();

        if ($adminUser) {
            $adminUser->roles()->syncWithoutDetaching([$admin->id, $caja->id]);
        }

        if ($testUser) {
            $testUser->roles()->syncWithoutDetaching([$caja->id]);
        }
    }
}
