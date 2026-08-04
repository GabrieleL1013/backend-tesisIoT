<?php

namespace Database\Seeders;

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
        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'Superusuario'],
            [
                'description' => 'Rol principal con acceso total al sistema',
                'color' => '#10b981',
                'level_permission' => 10,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin de Prueba',
                'password' => Hash::make('password123'),
                'role_id' => $role->id,
            ]
        );
    }
}
