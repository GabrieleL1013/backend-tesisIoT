<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MetricTemplate;

class MetricTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (MetricTemplate::count() === 0) {
            $seeds = [
                [
                    'nombre' => 'Estación Meteorológica',
                    'subvariables' => [
                        ['nombre' => 'Temperatura', 'unidad' => '°C', 'clave_mqtt' => 'temp'],
                        ['nombre' => 'Humedad Relativa', 'unidad' => '%', 'clave_mqtt' => 'hum'],
                        ['nombre' => 'Presión Atmosférica', 'unidad' => 'hPa', 'clave_mqtt' => 'press']
                    ]
                ],
                [
                    'nombre' => 'Calidad del Aire',
                    'subvariables' => [
                        ['nombre' => 'Calidad del Aire (AQI)', 'unidad' => 'AQI', 'clave_mqtt' => 'aqi'],
                        ['nombre' => 'Dióxido de Carbono (CO2)', 'unidad' => 'ppm', 'clave_mqtt' => 'co2']
                    ]
                ],
                [
                    'nombre' => 'Sensor de Suelo',
                    'subvariables' => [
                        ['nombre' => 'Humedad de Suelo', 'unidad' => '%', 'clave_mqtt' => 'soil_hum'],
                        ['nombre' => 'Temperatura de Suelo', 'unidad' => '°C', 'clave_mqtt' => 'soil_temp']
                    ]
                ]
            ];

            foreach ($seeds as $s) {
                $t = MetricTemplate::create(['nombre' => $s['nombre']]);
                foreach ($s['subvariables'] as $sub) {
                    $t->subvariables()->create($sub);
                }
            }
        }
    }
}
