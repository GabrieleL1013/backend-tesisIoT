<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\MetricTemplate;
use App\Models\Node;
use App\Models\SubvariableTemplate;
use Illuminate\Database\Seeder;

class HeltecWaterNodeSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::firstOrCreate(
            ['nombre' => 'Gateway RAK ULEAM'],
            ['descripcion' => 'Nodo LoRaWAN de calidad de agua', 'latitud' => -0.952489, 'longitud' => -80.744866]
        );

        $node = Node::updateOrCreate(
            ['serial_number' => '8aebbb6d6256a64f'],
            [
                'nombre' => 'Heltec V2',
                'categoria' => 'Agua',
                'estado' => true,
                'location_id' => $location->id,
                'broker' => '172.29.101.43',
                'port' => 1883,
                'topic_data' => 'application/+/device/+/event/up',
                'save_frequency' => 30,
                'instability_alert_interval' => 300,
            ]
        );

        $metric = MetricTemplate::firstOrCreate(['nombre' => 'Calidad del Agua']);

        $definitions = [
            ['clave_mqtt' => 'ph', 'nombre' => 'pH', 'unidad' => 'pH', 'icono' => 'ph'],
            ['clave_mqtt' => 'dissolved_oxygen_mg_l', 'nombre' => 'Oxigeno Disuelto', 'unidad' => 'mg/L', 'icono' => 'general'],
            ['clave_mqtt' => 'ph_voltage_mv', 'nombre' => 'Voltaje pH', 'unidad' => 'mV', 'icono' => 'ph'],
            ['clave_mqtt' => 'dissolved_oxygen_voltage_mv', 'nombre' => 'Voltaje Oxigeno Disuelto', 'unidad' => 'mV', 'icono' => 'general'],
            ['clave_mqtt' => 'turbidity_adc_raw', 'nombre' => 'Turbidez ADC', 'unidad' => 'raw', 'icono' => 'general'],
            ['clave_mqtt' => 'turbidity_sensor_voltage_mv', 'nombre' => 'Voltaje Sensor Turbidez', 'unidad' => 'mV', 'icono' => 'general'],
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
