<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScientificArticle;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class ScientificArticleController extends Controller
{
    private function localizeArticle(ScientificArticle $article, string $lang): array
    {
        $data = $article->toArray();
        if (str_starts_with(strtolower($lang), 'en')) {
            $transFields = [
                'titulo', 'resumen', 'palabras_clave', 'introduccion',
                'introduccion_imagen_descripcion', 'metodologia',
                'metodologia_imagen_descripcion', 'resultados',
                'resultados_imagen_descripcion', 'conclusiones',
                'conclusiones_imagen_descripcion'
            ];

            foreach ($transFields as $field) {
                $fieldEn = $field . '_en';
                if (empty($article->$fieldEn) && !empty($article->$field)) {
                    $trans = TranslationService::translate($article->$field, 'es', 'en');
                    $article->$fieldEn = !empty($trans) ? $trans : $article->$field;
                }
                if (!empty($article->$fieldEn)) {
                    $data[$field] = $article->$fieldEn;
                }
            }

            if ($article->isDirty()) {
                $article->save();
            }
        }
        return $data;
    }

    public function index(Request $request)
    {
        $lang = $request->query('lang', $request->header('Accept-Language', 'es'));
        $articles = ScientificArticle::all()->map(function ($article) use ($lang) {
            return $this->localizeArticle($article, $lang);
        });

        return response()->json($articles, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        $transFields = [
            'titulo', 'resumen', 'palabras_clave', 'introduccion',
            'introduccion_imagen_descripcion', 'metodologia',
            'metodologia_imagen_descripcion', 'resultados',
            'resultados_imagen_descripcion', 'conclusiones',
            'conclusiones_imagen_descripcion'
        ];

        foreach ($transFields as $field) {
            if (!empty($validated[$field])) {
                if ($isEn) {
                    $validated[$field . '_en'] = $validated[$field];
                    $translatedEs = TranslationService::translate($validated[$field], 'en', 'es');
                    $validated[$field] = !empty($translatedEs) ? $translatedEs : $validated[$field];
                } else {
                    $translatedEn = TranslationService::translate($validated[$field], 'es', 'en');
                    $validated[$field . '_en'] = !empty($translatedEn) ? $translatedEn : $validated[$field];
                }
            }
        }

        $article = ScientificArticle::create($validated);

        return response()->json($this->localizeArticle($article, $lang), 201);
    }

    public function show(Request $request, $id)
    {
        $article = ScientificArticle::findOrFail($id);
        $lang = $request->query('lang', $request->header('Accept-Language', 'es'));
        return response()->json($this->localizeArticle($article, $lang), 200);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
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

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        $transFields = [
            'titulo', 'resumen', 'palabras_clave', 'introduccion',
            'introduccion_imagen_descripcion', 'metodologia',
            'metodologia_imagen_descripcion', 'resultados',
            'resultados_imagen_descripcion', 'conclusiones',
            'conclusiones_imagen_descripcion'
        ];

        foreach ($transFields as $field) {
            if (!empty($validated[$field])) {
                if ($isEn) {
                    $validated[$field . '_en'] = $validated[$field];
                    $translatedEs = TranslationService::translate($validated[$field], 'en', 'es');
                    $validated[$field] = !empty($translatedEs) ? $translatedEs : $validated[$field];
                } else {
                    $translatedEn = TranslationService::translate($validated[$field], 'es', 'en');
                    $validated[$field . '_en'] = !empty($translatedEn) ? $translatedEn : $validated[$field];
                }
            }
        }

        $article = ScientificArticle::findOrFail($id);
        $article->update($validated);

        return response()->json($this->localizeArticle($article, $lang), 200);
    }

    public function destroy($id)
    {
        $article = ScientificArticle::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Artículo científico eliminado correctamente.'], 200);
    }
}
