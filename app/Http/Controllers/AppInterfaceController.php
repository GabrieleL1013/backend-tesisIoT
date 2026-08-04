<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppInterface;

class AppInterfaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $interfaces = AppInterface::where('path', 'like', '%/admin%')
            ->orWhere('path', '/modo-edicion')
            ->orWhere('path', '/edit-mode')
            ->get();

        return response()->json($interfaces, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:app_interfaces,name',
            'path' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allowed_roles' => 'nullable|array',
            'min_level' => 'nullable|integer',
        ]);

        $appInterface = AppInterface::create($validated);
        return response()->json($appInterface, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $appInterface = AppInterface::findOrFail($id);
        return response()->json($appInterface, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $appInterface = AppInterface::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:app_interfaces,name,' . $id,
            'path' => 'required|string|max:255',
            'description' => 'nullable|string',
            'allowed_roles' => 'nullable|array',
            'min_level' => 'nullable|integer',
        ]);

        $appInterface->update($validated);
        return response()->json($appInterface, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $appInterface = AppInterface::findOrFail($id);
        $appInterface->delete();
        return response()->json(['message' => 'Interface deleted successfully'], 200);
    }
}
