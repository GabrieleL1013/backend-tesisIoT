<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superuserRole = Role::where('name', 'Superusuario')->first();

        if (!$superuserRole) {
            $superuserRole = Role::create([
                'name' => 'Superusuario',
                'description' => 'Acceso total al sistema',
                'color' => '#10b981',
                'level_permission' => 10,
            ]);
        }

        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin de Prueba',
                'password' => Hash::make('password123'),
                'role_id' => $superuserRole->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
