<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\MetricTemplate;
use App\Models\Node;
use App\Models\SubvariableTemplate;
use Illuminate\Database\Seeder;

class UleamWaterStationSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::firstOrCreate(
            ['nombre' => 'Estacion ULEAM Central'],
            ['descripcion' => 'Raspberry Pi con sensores de agua por MQTT institucional', 'latitud' => -0.952489, 'longitud' => -80.744866]
        );

        $node = Node::updateOrCreate(
            ['serial_number' => 'ULEAMCENTRAL02'],
            [
                'nombre' => 'Raspberry ULEAM Central 02',
                'categoria' => 'Agua',
                'estado' => true,
                'location_id' => $location->id,
                'broker' => '10.150.253.2',
                'port' => 1883,
                'topic_data' => 'iot_uleam/uleam',
                'username' => 'mqtt-uleam',
                'password' => 'Mqtt-Uleam2025$',
                'save_frequency' => 30,
                'instability_alert_interval' => 300,
            ]
        );

        $metric = MetricTemplate::firstOrCreate(['nombre' => 'Estacion de Agua']);

        $definitions = [
            ['clave_mqtt' => 'temperatura', 'nombre' => 'Temperatura DS18B20', 'unidad' => '°C', 'icono' => 'termometro'],
            ['clave_mqtt' => 'ph', 'nombre' => 'pH', 'unidad' => 'pH', 'icono' => 'ph'],
            ['clave_mqtt' => 'oxigeno_disuelto', 'nombre' => 'Oxigeno Disuelto', 'unidad' => 'mg/L', 'icono' => 'general'],
            ['clave_mqtt' => 'potenciometro_1', 'nombre' => 'Potenciometro 1', 'unidad' => 'V', 'icono' => 'general'],
            ['clave_mqtt' => 'potenciometro_2', 'nombre' => 'Potenciometro 2', 'unidad' => 'V', 'icono' => 'general'],
            ['clave_mqtt' => 'ec_us_cm', 'nombre' => 'Conductividad EC', 'unidad' => 'uS/cm', 'icono' => 'general'],
            ['clave_mqtt' => 'ec_temperature_c', 'nombre' => 'Temperatura EC', 'unidad' => '°C', 'icono' => 'termometro'],
            ['clave_mqtt' => 'salinity_ppm', 'nombre' => 'Salinidad', 'unidad' => 'ppm', 'icono' => 'general'],
            ['clave_mqtt' => 'tds_ppm', 'nombre' => 'TDS', 'unidad' => 'ppm', 'icono' => 'general'],
            ['clave_mqtt' => 'turbidity_ntu', 'nombre' => 'Turbidez', 'unidad' => 'NTU', 'icono' => 'general'],
            ['clave_mqtt' => 'turbidity_temperature_c', 'nombre' => 'Temperatura Turbidez', 'unidad' => '°C', 'icono' => 'termometro'],
        ];

        $subvariableIds = [];

        foreach ($definitions as $definition) {
            $subvariable = SubvariableTemplate::updateOrCreate(
                ['clave_mqtt' => $definition['clave_mqtt']],
                [
                    'nombre' => $definition['nombre'],
                    'unidad' => $definition['unidad'],
                    'icono' => $definition['icono'],
                    'metric_template_id' => $metric->id,
                ]
            );

            $subvariableIds[] = $subvariable->id;
        }

        $node->subvariables()->syncWithoutDetaching($subvariableIds);
    }
}
