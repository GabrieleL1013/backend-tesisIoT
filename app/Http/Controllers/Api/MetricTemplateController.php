<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetricTemplate;
use Illuminate\Http\Request;

class MetricTemplateController extends Controller
{
    public function index()
    {
        $templates = MetricTemplate::with('subvariables')->get();

        // Auto-seed default sensors with subvariables if empty on initial call
        if ($templates->isEmpty()) {
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

            $templates = MetricTemplate::with('subvariables')->get();
        }

        $mapped = $templates->map(function ($template) {
            return [
                'id' => $template->id,
                'nombre' => $template->nombre,
                'imagen' => $template->imagen,
                'subvariables' => $template->subvariables->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'nombre' => $sub->nombre,
                        'unidad' => $sub->unidad,
                        'claveMqtt' => $sub->clave_mqtt,
                        'minExpected' => $sub->min_expected,
                        'maxExpected' => $sub->max_expected,
                        'icono' => $sub->icono
                    ];
                })
            ];
        });

        return response()->json($mapped);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'imagen' => 'nullable|string',
            'subvariables' => 'required|array|min:1',
            'subvariables.*.nombre' => 'required|string',
            'subvariables.*.unidad' => 'required|string',
            'subvariables.*.claveMqtt' => 'required|string',
            'subvariables.*.minExpected' => 'nullable|numeric',
            'subvariables.*.maxExpected' => 'nullable|numeric',
            'subvariables.*.icono' => 'nullable|string'
        ]);

        $template = MetricTemplate::create([
            'nombre' => $request->nombre,
            'imagen' => $request->imagen
        ]);

        foreach ($request->subvariables as $sub) {
            $template->subvariables()->create([
                'nombre' => $sub['nombre'],
                'unidad' => $sub['unidad'],
                'clave_mqtt' => $sub['claveMqtt'],
                'min_expected' => $sub['minExpected'] ?? null,
                'max_expected' => $sub['maxExpected'] ?? null,
                'icono' => $sub['icono'] ?? null
            ]);
        }

        return response()->json([
            'id' => $template->id,
            'nombre' => $template->nombre,
            'imagen' => $template->imagen,
            'subvariables' => $template->subvariables->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'nombre' => $sub->nombre,
                    'unidad' => $sub->unidad,
                    'claveMqtt' => $sub->clave_mqtt,
                    'minExpected' => $sub->min_expected,
                    'maxExpected' => $sub->max_expected,
                    'icono' => $sub->icono
                ];
            })
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string',
            'imagen' => 'nullable|string',
            'subvariables' => 'required|array|min:1',
            'subvariables.*.nombre' => 'required|string',
            'subvariables.*.unidad' => 'required|string',
            'subvariables.*.claveMqtt' => 'required|string',
            'subvariables.*.minExpected' => 'nullable|numeric',
            'subvariables.*.maxExpected' => 'nullable|numeric',
            'subvariables.*.icono' => 'nullable|string'
        ]);

        $template = MetricTemplate::findOrFail($id);
        $template->update([
            'nombre' => $request->nombre,
            'imagen' => $request->imagen
        ]);

        // Keep existing subvariables that are still sent by the frontend, delete the rest, and update/create in-place
        $incomingIds = [];
        foreach ($request->subvariables as $sub) {
            if (isset($sub['id']) && $sub['id']) {
                $incomingIds[] = $sub['id'];
            }
        }

        // Delete removed subvariables
        $template->subvariables()->whereNotIn('id', $incomingIds)->delete();

        // Update existing subvariables or create new ones
        foreach ($request->subvariables as $sub) {
            if (isset($sub['id']) && $sub['id']) {
                $template->subvariables()->where('id', $sub['id'])->update([
                    'nombre' => $sub['nombre'],
                    'unidad' => $sub['unidad'],
                    'clave_mqtt' => $sub['claveMqtt'],
                    'min_expected' => $sub['minExpected'] ?? null,
                    'max_expected' => $sub['maxExpected'] ?? null,
                    'icono' => $sub['icono'] ?? null
                ]);
            } else {
                $template->subvariables()->create([
                    'nombre' => $sub['nombre'],
                    'unidad' => $sub['unidad'],
                    'clave_mqtt' => $sub['claveMqtt'],
                    'min_expected' => $sub['minExpected'] ?? null,
                    'max_expected' => $sub['maxExpected'] ?? null,
                    'icono' => $sub['icono'] ?? null
                ]);
            }
        }

        return response()->json([
            'id' => $template->id,
            'nombre' => $template->nombre,
            'imagen' => $template->imagen,
            'subvariables' => $template->subvariables()->get()->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'nombre' => $sub->nombre,
                    'unidad' => $sub->unidad,
                    'claveMqtt' => $sub->clave_mqtt,
                    'minExpected' => $sub->min_expected,
                    'maxExpected' => $sub->max_expected,
                    'icono' => $sub->icono
                ];
            })
        ]);
    }

    public function destroy($id)
    {
        $template = MetricTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Sensor package template deleted successfully.']);
    }
}
