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

        User::updateOrCreate(
            ['email' => 'admin@uleam.edu.ec'],
            [
                'name' => 'Daniel Cedeño',
                'password' => bcrypt('uleamiot2026'),
                'rol' => 'Superusuario'
            ]
        );

        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
