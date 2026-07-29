<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndSuperuserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Superusuario',
                'description' => 'Acceso total al sistema y a todas las interfaces.',
                'color' => '#10b981',
                'level_permission' => 10,
            ],
            [
                'name' => 'Administrador',
                'description' => 'Gestion operativa del sistema con privilegios altos.',
                'color' => '#3b82f6',
                'level_permission' => 8,
            ],
            [
                'name' => 'Tecnico',
                'description' => 'Monitoreo tecnico y soporte de nodos y metricas.',
                'color' => '#f59e0b',
                'level_permission' => 5,
            ],
            [
                'name' => 'Consulta',
                'description' => 'Acceso de solo lectura a paneles e historicos.',
                'color' => '#64748b',
                'level_permission' => 1,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData,
            );
        }

        $superuserRole = Role::where('name', 'Superusuario')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@uleam.edu.ec'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('uleamiot2026'),
                'role_id' => $superuserRole->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
