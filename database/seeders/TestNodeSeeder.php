<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Node;
use App\Models\MetricTemplate;
use App\Models\SubvariableTemplate;
use App\Models\Location;

class TestNodeSeeder extends Seeder
{
    public function run()
    {
        $location = Location::firstOrCreate(
            ['nombre' => 'Sede Central ULEAM'],
            ['descripcion' => 'Manta', 'latitud' => -0.963, 'longitud' => -80.732]
        );

        // 1. Crear el nodo de prueba ULEAMAQI si no existe
        $node = Node::firstOrCreate(
            ['serial_number' => 'ULEAMAQI'],
            [
                'nombre' => 'Sensor Prueba AQI', 
                'categoria' => 'Ambiente', 
                'estado' => true,
                'location_id' => $location->id
            ]
        );

        // 2. Crear una categoría métrica base
        $metric = MetricTemplate::firstOrCreate(
            ['nombre' => 'Clima']
        );

        // 3. Crear las variables temperatura y humedad
        $temp = SubvariableTemplate::firstOrCreate(
            ['clave_mqtt' => 'temperatura'],
            [
                'nombre' => 'Temperatura', 
                'unidad' => '°C', 
                'metric_template_id' => $metric->id
            ]
        );

        $hum = SubvariableTemplate::firstOrCreate(
            ['clave_mqtt' => 'humedad'],
            [
                'nombre' => 'Humedad Relativa', 
                'unidad' => '%', 
                'metric_template_id' => $metric->id
            ]
        );

        // 4. Vincular el nodo a estas variables (Pivot table)
        $node->subvariables()->syncWithoutDetaching([$temp->id, $hum->id]);
    }
}
