<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
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
