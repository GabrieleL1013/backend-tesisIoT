<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScientificArticle;
use Illuminate\Http\Request;

class ScientificArticleController extends Controller
{
    public function index()
    {
        return response()->json(ScientificArticle::all(), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string',
            'autores' => 'required|string',
            'revista' => 'nullable|string',
            'resumen' => 'nullable|string',
            'palabras_clave' => 'nullable|string',
            'introduccion' => 'nullable|string',
            'introduccion_imagen' => 'nullable|string',
            'introduccion_imagen_descripcion' => 'nullable|string',
            'metodologia' => 'nullable|string',
            'metodologia_imagen' => 'nullable|string',
            'metodologia_imagen_descripcion' => 'nullable|string',
            'resultados' => 'nullable|string',
            'resultados_imagen' => 'nullable|string',
            'resultados_imagen_descripcion' => 'nullable|string',
            'conclusiones' => 'nullable|string',
            'conclusiones_imagen' => 'nullable|string',
            'conclusiones_imagen_descripcion' => 'nullable|string',
            'referencias' => 'nullable|string',
            'url_pdf' => 'nullable|string',
            'estado' => 'required|string',
            'tipo_registro' => 'nullable|string'
        ]);

        $article = ScientificArticle::create($request->all());

        return response()->json($article, 201);
    }

    public function show($id)
    {
        $article = ScientificArticle::findOrFail($id);
        return response()->json($article, 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string',
            'autores' => 'required|string',
            'revista' => 'nullable|string',
            'resumen' => 'nullable|string',
            'palabras_clave' => 'nullable|string',
            'introduccion' => 'nullable|string',
            'introduccion_imagen' => 'nullable|string',
            'introduccion_imagen_descripcion' => 'nullable|string',
            'metodologia' => 'nullable|string',
            'metodologia_imagen' => 'nullable|string',
            'metodologia_imagen_descripcion' => 'nullable|string',
            'resultados' => 'nullable|string',
            'resultados_imagen' => 'nullable|string',
            'resultados_imagen_descripcion' => 'nullable|string',
            'conclusiones' => 'nullable|string',
            'conclusiones_imagen' => 'nullable|string',
            'conclusiones_imagen_descripcion' => 'nullable|string',
            'referencias' => 'nullable|string',
            'url_pdf' => 'nullable|string',
            'estado' => 'required|string',
            'tipo_registro' => 'nullable|string'
        ]);

        $article = ScientificArticle::findOrFail($id);
        $article->update($request->all());

        return response()->json($article, 200);
    }

    public function destroy($id)
    {
        $article = ScientificArticle::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Artículo científico eliminado correctamente.'], 200);
    }
}
