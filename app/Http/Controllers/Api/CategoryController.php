<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        // Auto-seed default categories if database is completely empty
        if (Category::count() === 0) {
            Category::insert([
                [
                    'nombre' => 'Calidad del Aire',
                    'color' => 'blue',
                    'colorHex' => '#3b82f6',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nombre' => 'Acuicultura / Camaroneras',
                    'color' => 'gold',
                    'colorHex' => '#ca8a04',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nombre' => 'Agricultura Inteligente',
                    'color' => 'green',
                    'colorHex' => '#16a34a',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'nombre' => 'Sistemas Ambientales',
                    'color' => 'purple',
                    'colorHex' => '#9333ea',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        }

        return response()->json(Category::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:categories,nombre',
            'color' => 'required|string',
            'colorHex' => 'required|string',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 210); // 210: Created
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category, 200);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|unique:categories,nombre,' . $id,
            'color' => 'required|string',
            'colorHex' => 'required|string',
        ]);

        $category->update($validated);

        return response()->json($category, 200);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada con éxito.'], 200);
    }
}
