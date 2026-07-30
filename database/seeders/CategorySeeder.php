<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (Category::count() === 0) {
            Category::insert([
                [
                    'nombre' => 'Calidad del Aire',
                    'color' => 'blue',
                    'colorHex' => '#3b82f6',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nombre' => 'Acuicultura / Camaroneras',
                    'color' => 'gold',
                    'colorHex' => '#ca8a04',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nombre' => 'Agricultura Inteligente',
                    'color' => 'green',
                    'colorHex' => '#16a34a',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nombre' => 'Sistemas Ambientales',
                    'color' => 'purple',
                    'colorHex' => '#9333ea',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        }
    }
}
