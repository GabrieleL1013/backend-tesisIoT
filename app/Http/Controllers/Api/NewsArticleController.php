<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class NewsArticleController extends Controller
{
    private function localizeNews(NewsArticle $news, string $lang): array
    {
        $data = $news->toArray();
        if (str_starts_with(strtolower($lang), 'en')) {
            if (empty($news->titulo_en) && !empty($news->titulo)) {
                $trans = TranslationService::translate($news->titulo, 'es', 'en');
                $news->titulo_en = !empty($trans) ? $trans : $news->titulo;
            }
            if (empty($news->contenido_en) && !empty($news->contenido)) {
                $trans = TranslationService::translate($news->contenido, 'es', 'en');
                $news->contenido_en = !empty($trans) ? $trans : $news->contenido;
            }
            if ($news->isDirty()) {
                $news->save();
            }

            $data['titulo'] = !empty($news->titulo_en) ? $news->titulo_en : $news->titulo;
            $data['contenido'] = !empty($news->contenido_en) ? $news->contenido_en : $news->contenido;
        }
        return $data;
    }

    public function index(Request $request)
    {
        $lang = $request->query('lang', $request->header('Accept-Language', 'es'));
        $articles = NewsArticle::all()->map(function ($news) use ($lang) {
            return $this->localizeNews($news, $lang);
        });

        return response()->json($articles, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string',
            'autor' => 'required|string',
            'contenido' => 'required|string',
            'imagen_url' => 'nullable|string',
            'estado' => 'required|string'
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        if ($isEn) {
            $validated['titulo_en'] = $validated['titulo'];
            $validated['contenido_en'] = $validated['contenido'];
            $transTit = TranslationService::translate($validated['titulo'], 'en', 'es');
            $transCont = TranslationService::translate($validated['contenido'], 'en', 'es');
            $validated['titulo'] = !empty($transTit) ? $transTit : $validated['titulo'];
            $validated['contenido'] = !empty($transCont) ? $transCont : $validated['contenido'];
        } else {
            $transTit = TranslationService::translate($validated['titulo'], 'es', 'en');
            $transCont = TranslationService::translate($validated['contenido'], 'es', 'en');
            $validated['titulo_en'] = !empty($transTit) ? $transTit : $validated['titulo'];
            $validated['contenido_en'] = !empty($transCont) ? $transCont : $validated['contenido'];
        }

        $news = NewsArticle::create($validated);

        return response()->json($this->localizeNews($news, $lang), 201);
    }

    public function show(Request $request, $id)
    {
        $news = NewsArticle::findOrFail($id);
        $lang = $request->query('lang', $request->header('Accept-Language', 'es'));
        return response()->json($this->localizeNews($news, $lang), 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'titulo' => 'required|string',
            'autor' => 'required|string',
            'contenido' => 'required|string',
            'imagen_url' => 'nullable|string',
            'estado' => 'required|string'
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        if ($isEn) {
            $validated['titulo_en'] = $validated['titulo'];
            $validated['contenido_en'] = $validated['contenido'];
            $transTit = TranslationService::translate($validated['titulo'], 'en', 'es');
            $transCont = TranslationService::translate($validated['contenido'], 'en', 'es');
            $validated['titulo'] = !empty($transTit) ? $transTit : $validated['titulo'];
            $validated['contenido'] = !empty($transCont) ? $transCont : $validated['contenido'];
        } else {
            $transTit = TranslationService::translate($validated['titulo'], 'es', 'en');
            $transCont = TranslationService::translate($validated['contenido'], 'es', 'en');
            $validated['titulo_en'] = !empty($transTit) ? $transTit : $validated['titulo'];
            $validated['contenido_en'] = !empty($transCont) ? $transCont : $validated['contenido'];
        }

        $news = NewsArticle::findOrFail($id);
        $news->update($validated);

        return response()->json($this->localizeNews($news, $lang), 200);
    }

    public function destroy($id)
    {
        $news = NewsArticle::findOrFail($id);
        $news->delete();

        return response()->json(['message' => 'Noticia eliminada correctamente.'], 200);
    }
}
