<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::all();
        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric'
        ]);

        $location = Location::create($request->all());

        return response()->json($location, 201);
    }

    public function show($id)
    {
        $location = Location::findOrFail($id);
        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric'
        ]);

        $location = Location::findOrFail($id);
        $location->update($request->all());

        return response()->json($location);
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        
        // Prevent deletion if nodes are linked to this campus
        if ($location->nodes()->count() > 0) {
            return response()->json([
                'error' => 'No se puede eliminar esta ubicación porque tiene nodos sensores vinculados a ella.'
            ], 400);
        }

        $location->delete();

        return response()->json(['message' => 'Ubicación eliminada correctamente.']);
    }
}
