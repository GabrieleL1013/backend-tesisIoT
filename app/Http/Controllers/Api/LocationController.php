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

        // Seed default locations if database is empty on mount
        if ($locations->isEmpty()) {
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
            $locations = Location::all();
        }

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
