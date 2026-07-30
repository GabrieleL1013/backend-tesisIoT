<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\Request;

class NewsArticleController extends Controller
{
    public function index()
    {
        return response()->json(NewsArticle::all(), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string',
            'autor' => 'required|string',
            'contenido' => 'required|string',
            'imagen_url' => 'nullable|string',
            'estado' => 'required|string'
        ]);

        $news = NewsArticle::create($request->all());

        return response()->json($news, 201);
    }

    public function show($id)
    {
        $news = NewsArticle::findOrFail($id);
        return response()->json($news, 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string',
            'autor' => 'required|string',
            'contenido' => 'required|string',
            'imagen_url' => 'nullable|string',
            'estado' => 'required|string'
        ]);

        $news = NewsArticle::findOrFail($id);
        $news->update($request->all());

        return response()->json($news, 200);
    }

    public function destroy($id)
    {
        $news = NewsArticle::findOrFail($id);
        $news->delete();

        return response()->json(['message' => 'Noticia eliminada correctamente.'], 200);
    }
}
