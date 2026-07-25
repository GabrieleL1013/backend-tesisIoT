<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Node;
use App\Models\Lectura;
use Carbon\Carbon;

class LecturasTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncar para comenzar limpios
        Lectura::truncate();

        $nodes = Node::with('subvariables')->get();

        foreach ($nodes as $node) {
            foreach ($node->subvariables as $sub) {
                $clave = $sub->clave_mqtt;

                // Definir un valor base aproximado para simular lecturas
                $baseVal = 24.0; // temperatura
                if (str_contains($clave, 'hum') || str_contains($clave, 'soil')) {
                    $baseVal = 65.0; // humedad
                } elseif (str_contains($clave, 'co2')) {
                    $baseVal = 400.0;
                } elseif (str_contains($clave, 'aqi')) {
                    $baseVal = 45.0;
                } elseif (str_contains($clave, 'press')) {
                    $baseVal = 1012.0;
                }

                // Generar una lectura cada 4 horas para los últimos 7 días
                $now = Carbon::now();
                for ($hoursAgo = 168; $hoursAgo >= 0; $hoursAgo -= 4) {
                    $timestamp = (clone $now)->subHours($hoursAgo);

                    // Añadir variación sinusoidal aleatoria
                    $variacion = sin($hoursAgo + ord($clave[0] ?? 'a')) * ($baseVal * 0.1);
                    $valor = round($baseVal + $variacion + (rand(-10, 10) / 10), 1);

                    Lectura::create([
                        'node_id' => $node->id,
                        'subvariable_id' => $sub->id,
                        'valor' => abs($valor),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp
                    ]);
                }
            }
        }
    }
}
