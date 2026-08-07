<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScientificArticle;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class ScientificArticleController extends Controller
{
    private array $imageFields = [
        'introduccion_imagen',
        'metodologia_imagen',
        'resultados_imagen',
        'conclusiones_imagen'
    ];

    private function handleImageUpload(?string $base64Image): ?string
    {
        if (!$base64Image || !preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            return $base64Image;
        }

        $data = substr($base64Image, strpos($base64Image, ',') + 1);
        $data = base64_decode($data);

        if ($data === false) {
            return null;
        }

        $extension = 'webp';

        $folder = public_path('uploads/articulos');
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $filename = 'articulo_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $folder . '/' . $filename;
        file_put_contents($filePath, $data);

        return '/uploads/articulos/' . $filename;
    }

    private function formatImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }
        return url($path);
    }

    private function normalizeImagePath(?string $path): ?string
    {
        if (!$path) return null;
        $parsed = parse_url($path, PHP_URL_PATH);
        return $parsed ? $parsed : $path;
    }

    private function deletePhysicalImage(?string $path): void
    {
        if (!$path) return;

        $parsedPath = parse_url($path, PHP_URL_PATH);
        if ($parsedPath && str_starts_with($parsedPath, '/uploads/articulos/')) {
            $fullPath = public_path(ltrim($parsedPath, '/'));
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

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

        foreach ($this->imageFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = $this->formatImageUrl($data[$field]);
            }
        }

        return $data;
    }

    public function index(Request $request)
    {
        $lang = $request->query('lang', $request->header('Accept-Language', 'es'));
        $query = ScientificArticle::query();

        if ($request->has('estado') && !empty($request->query('estado'))) {
            $query->where('estado', $request->query('estado'));
        }

        if ($request->has('search') && !empty($request->query('search'))) {
            $rawSearch = trim($request->query('search'));
            $search = mb_strtolower($rawSearch, 'UTF-8');
            $searchNoAccents = strtr($search, [
                'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ñ'=>'n',
                'ü'=>'u', 'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ñ'=>'n'
            ]);

            $targetField = ($lang === 'en') ? 'titulo_en' : 'titulo';

            $query->where(function($q) use ($targetField, $rawSearch, $search, $searchNoAccents, $lang) {
                $q->whereRaw("LOWER({$targetField}) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER({$targetField}) LIKE ?", ["%{$searchNoAccents}%"])
                  ->orWhere($targetField, 'like', "%{$rawSearch}%");

                if ($lang === 'en') {
                    $q->orWhere(function($sub) use ($search, $searchNoAccents, $rawSearch) {
                        $sub->where(function($isNull) {
                            $isNull->whereNull('titulo_en')->orWhere('titulo_en', '');
                        })->where(function($sub2) use ($search, $searchNoAccents, $rawSearch) {
                            $sub2->whereRaw("LOWER(titulo) LIKE ?", ["%{$search}%"])
                                 ->orWhereRaw("LOWER(titulo) LIKE ?", ["%{$searchNoAccents}%"])
                                 ->orWhere('titulo', 'like', "%{$rawSearch}%");
                        });
                    });
                }
            });
        }

        if ($request->has('year') && !empty($request->query('year')) && $request->query('year') !== 'todos') {
            $year = $request->query('year');
            $query->whereYear('created_at', $year);
        }

        $sort = $request->query('sort', 'recientes');
        if ($sort === 'antiguos') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'alfa_asc') {
            $query->orderBy('titulo', 'asc');
        } elseif ($sort === 'alfa_desc') {
            $query->orderBy('titulo', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('per_page') || $request->has('page')) {
            $perPage = (int) $request->query('per_page', 9);
            $paginated = $query->paginate($perPage);
            $paginated->getCollection()->transform(function ($article) use ($lang) {
                return $this->localizeArticle($article, $lang);
            });
            return response()->json($paginated, 200);
        }

        if ($request->has('limit') && !empty($request->query('limit'))) {
            $query->limit((int)$request->query('limit'));
        }

        $articles = $query->get()->map(function ($article) use ($lang) {
            return $this->localizeArticle($article, $lang);
        });

        return response()->json($articles, 200);
    }

    public function getYears()
    {
        $years = ScientificArticle::where('estado', 'Publicado')
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();

        return response()->json($years, 200);
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

        foreach ($this->imageFields as $field) {
            if (!empty($validated[$field])) {
                $validated[$field] = $this->handleImageUpload($validated[$field]);
            }
        }

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

        $article = ScientificArticle::findOrFail($id);

        foreach ($this->imageFields as $field) {
            $oldImgNorm = $this->normalizeImagePath($article->$field);
            if (array_key_exists($field, $validated)) {
                $validated[$field] = $this->handleImageUpload($validated[$field]);
                $newImgNorm = $this->normalizeImagePath($validated[$field]);

                if ($oldImgNorm && $oldImgNorm !== $newImgNorm) {
                    $stillUsed = false;
                    foreach ($this->imageFields as $otherField) {
                        $checkVal = array_key_exists($otherField, $validated) ? $validated[$otherField] : $article->$otherField;
                        if ($otherField !== $field && $this->normalizeImagePath($checkVal) === $oldImgNorm) {
                            $stillUsed = true;
                            break;
                        }
                    }
                    if (!$stillUsed) {
                        $this->deletePhysicalImage($oldImgNorm);
                    }
                }
            }
        }

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

        $article->update($validated);

        return response()->json($this->localizeArticle($article, $lang), 200);
    }

    public function destroy($id)
    {
        $article = ScientificArticle::findOrFail($id);

        $deletedPaths = [];
        foreach ($this->imageFields as $field) {
            $norm = $this->normalizeImagePath($article->$field);
            if ($norm && !in_array($norm, $deletedPaths)) {
                $this->deletePhysicalImage($norm);
                $deletedPaths[] = $norm;
            }
        }

        $article->delete();

        return response()->json(['message' => 'Artículo científico eliminado correctamente.'], 200);
    }
}
