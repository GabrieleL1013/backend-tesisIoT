<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private function localizeCategory(Category $category, string $lang): array
    {
        $data = $category->toArray();
        $data['nombre_es'] = $category->nombre;
        $data['nombre_en'] = !empty($category->nombre_en) ? $category->nombre_en : $category->nombre;

        if (str_starts_with(strtolower($lang), 'en')) {
            if (empty($category->nombre_en) && !empty($category->nombre)) {
                $category->nombre_en = TranslationService::translate($category->nombre, 'es', 'en');
                $category->save();
                $data['nombre_en'] = $category->nombre_en;
            }
            $data['nombre'] = !empty($category->nombre_en) ? $category->nombre_en : $category->nombre;
        }
        return $data;
    }

    public function count()
    {
        return response()->json(['count' => Category::count()]);
    }

    public function index(Request $request)
    {
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $categories = Category::all()->map(function ($category) use ($lang) {
            return $this->localizeCategory($category, $lang);
        });
        return response()->json($categories, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:categories,nombre',
            'color' => 'required|string',
            'colorHex' => 'required|string',
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        if ($isEn) {
            $validated['nombre_en'] = $validated['nombre'];
            $translatedEs = TranslationService::translate($validated['nombre'], 'en', 'es');
            $validated['nombre'] = !empty($translatedEs) ? $translatedEs : $validated['nombre'];
        } else {
            $validated['nombre_en'] = TranslationService::translate($validated['nombre'], 'es', 'en');
        }

        $category = Category::create($validated);

        return response()->json($this->localizeCategory($category, $lang), 201);
    }

    public function show(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $lang = $request->query('lang', $request->header('Accept-Language', 'es'));
        return response()->json($this->localizeCategory($category, $lang), 200);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|unique:categories,nombre,' . $id,
            'color' => 'required|string',
            'colorHex' => 'required|string',
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        if ($isEn) {
            $validated['nombre_en'] = $validated['nombre'];
            $translatedEs = TranslationService::translate($validated['nombre'], 'en', 'es');
            $validated['nombre'] = !empty($translatedEs) ? $translatedEs : $validated['nombre'];
        } else {
            $validated['nombre_en'] = TranslationService::translate($validated['nombre'], 'es', 'en');
        }

        $category->update($validated);

        return response()->json($this->localizeCategory($category, $lang), 200);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada con éxito.'], 200);
    }
}
