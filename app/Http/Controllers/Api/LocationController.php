<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    private function processBilingualData(array $data, string $lang): array
    {
        $isEn = str_starts_with(strtolower($lang), 'en');

        if ($isEn) {
            // Se recibió en inglés
            if (isset($data['nombre'])) {
                $data['nombre_en'] = $data['nombre'];
                $trans = TranslationService::translate($data['nombre'], 'en', 'es');
                $data['nombre'] = !empty($trans) ? $trans : $data['nombre'];
            }
            if (isset($data['descripcion'])) {
                $data['descripcion_en'] = $data['descripcion'];
                $trans = TranslationService::translate($data['descripcion'], 'en', 'es');
                $data['descripcion'] = !empty($trans) ? $trans : $data['descripcion'];
            }
        } else {
            // Se recibió en español
            if (isset($data['nombre'])) {
                $trans = TranslationService::translate($data['nombre'], 'es', 'en');
                $data['nombre_en'] = !empty($trans) ? $trans : $data['nombre'];
            }
            if (isset($data['descripcion'])) {
                $trans = TranslationService::translate($data['descripcion'], 'es', 'en');
                $data['descripcion_en'] = !empty($trans) ? $trans : $data['descripcion'];
            }
        }

        return $data;
    }

    public function index(Request $request)
    {
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        $locations = Location::all()->map(function ($loc) use ($isEn) {
            $data = $loc->toArray();
            $data['nombre_es'] = $loc->nombre;
            $data['descripcion_es'] = $loc->descripcion;

            if ($isEn) {
                $data['nombre'] = !empty($loc->nombre_en) ? $loc->nombre_en : $loc->nombre;
                $data['descripcion'] = !empty($loc->descripcion_en) ? $loc->descripcion_en : $loc->descripcion;
            }

            return $data;
        });

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric'
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $payload = $this->processBilingualData($validated, $lang);

        $location = Location::create($payload);

        return response()->json($location, 201);
    }

    public function show($id)
    {
        $location = Location::findOrFail($id);
        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric'
        ]);

        $location = Location::findOrFail($id);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $payload = $this->processBilingualData($validated, $lang);

        $location->update($payload);

        return response()->json($location);
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        
        if ($location->nodes()->count() > 0) {
            return response()->json([
                'error' => 'No se puede eliminar esta ubicación porque tiene nodos sensores vinculados a ella.'
            ], 400);
        }

        $location->delete();

        return response()->json(['message' => 'Ubicación eliminada correctamente.']);
    }
}
