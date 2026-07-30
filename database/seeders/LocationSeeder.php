<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        if (Location::count() === 0) {
            Location::create([
                'nombre' => 'Campus Central ULEAM (Manta)',
                'descripcion' => 'Campus principal de la Universidad Laica Eloy Alfaro de Manabí',
                'latitud' => -0.952136,
                'longitud' => -80.742337
            ]);
            Location::create([
                'nombre' => 'Campus Chone ULEAM',
                'descripcion' => 'Extensión Chone de la Universidad Laica Eloy Alfaro de Manabí',
                'latitud' => -0.697424,
                'longitud' => -80.098877
            ]);
            Location::create([
                'nombre' => 'Campus Pedernales ULEAM',
                'descripcion' => 'Extensión Pedernales - Monitoreo Costero',
                'latitud' => 0.071850,
                'longitud' => -80.052600
            ]);
        }
    }
}
