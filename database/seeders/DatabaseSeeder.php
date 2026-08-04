<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'Superusuario'],
            [
                'description' => 'Rol principal con acceso total al sistema',
                'color' => '#10b981',
                'level_permission' => 10,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@uleam.edu.ec'],
            [
                'name' => 'Daniel Cedeño',
                'password' => bcrypt('uleamiot2026'),
                'role_id' => $role->id,
            ]
        );

        $this->call([
            AdminUserSeeder::class,
            MetricTemplateSeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
            NewsArticleSeeder::class,
            ScientificArticleSeeder::class,
            InterfaceTextSeeder::class,
        ]);
    }
}
