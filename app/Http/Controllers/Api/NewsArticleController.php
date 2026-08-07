<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class NewsArticleController extends Controller
{
    /**
     * Helper to normalize block types in a JSON content array.
     * Ensures 'type' is strictly 'text' or 'image', correcting blocks where plain text
     * was mislabeled as 'image' or 'imagen' by auto-translation.
     */
    private function normalizeBlocks(?string $jsonContent): ?string
    {
        if (empty($jsonContent)) return $jsonContent;
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as &$block) {
                $val = trim($block['value'] ?? '');
                $isImgUrl = str_starts_with($val, 'data:image') || str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, '/');
                $type = $block['type'] ?? 'text';
                if ($type === 'texto' || $type === 'text') {
                    $type = 'text';
                } elseif ($type === 'imagen' || $type === 'image') {
                    $type = 'image';
                }
                if ($type === 'image' && !empty($val) && !$isImgUrl) {
                    $type = 'text';
                }
                $block['type'] = $type;
            }
            unset($block);
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $jsonContent;
    }

    /**
     * Helper to safely translate JSON content blocks without breaking JSON array syntax
     * or mangling base64 image data strings.
     */
    private function translateContentBlocks(?string $jsonContent, string $fromLang, string $toLang): ?string
    {
        if (empty($jsonContent)) {
            return $jsonContent;
        }

        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as &$block) {
                $val = trim($block['value'] ?? '');
                $isImgUrl = str_starts_with($val, 'data:image') || str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, '/');
                
                $block['type'] = ($block['type'] === 'image' && !empty($val) && !$isImgUrl) ? 'text' : ($block['type'] ?? 'text');

                if ($block['type'] === 'text' && !empty($block['value'])) {
                    $trans = TranslationService::translate($block['value'], $fromLang, $toLang);
                    if (!empty($trans)) {
                        $block['value'] = $trans;
                    }
                }
            }
            unset($block);
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return TranslationService::translate($jsonContent, $fromLang, $toLang);
    }

    private function countBlocks(?string $jsonContent): int
    {
        if (empty($jsonContent)) return 0;
        $decoded = json_decode($jsonContent, true);
        return is_array($decoded) ? count($decoded) : 1;
    }

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

        $folder = public_path('uploads/noticias');
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $filename = 'noticia_' . time() . '_' . uniqid() . '.' . $extension;
        $filePath = $folder . '/' . $filename;
        file_put_contents($filePath, $data);

        return '/uploads/noticias/' . $filename;
    }

    private function processContentImageBlocks(?string $jsonContent): ?string
    {
        if (empty($jsonContent)) return $jsonContent;
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as &$block) {
                if (($block['type'] ?? '') === 'image' && !empty($block['value'])) {
                    $block['value'] = $this->handleImageUpload($block['value']);
                }
            }
            unset($block);
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $jsonContent;
    }

    private function formatImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }
        return url($path);
    }

    private function formatContentBlockImageUrls(?string $jsonContent): ?string
    {
        if (empty($jsonContent)) return $jsonContent;
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as &$block) {
                if (($block['type'] ?? '') === 'image' && !empty($block['value'])) {
                    $block['value'] = $this->formatImageUrl($block['value']);
                }
            }
            unset($block);
            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return $jsonContent;
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
        if ($parsedPath && str_starts_with($parsedPath, '/uploads/noticias/')) {
            $fullPath = public_path(ltrim($parsedPath, '/'));
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    private function extractImagePathsFromContent(?string $jsonContent): array
    {
        if (empty($jsonContent)) return [];
        $decoded = json_decode($jsonContent, true);
        $paths = [];
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            foreach ($decoded as $block) {
                if (($block['type'] ?? '') === 'image' && !empty($block['value'])) {
                    $norm = $this->normalizeImagePath($block['value']);
                    if ($norm) {
                        $paths[] = $norm;
                    }
                }
            }
        }
        return array_values(array_unique($paths));
    }

    private function localizeNews(NewsArticle $news, string $lang): array
    {
        $news->contenido = $this->normalizeBlocks($news->contenido);
        $news->contenido_en = $this->normalizeBlocks($news->contenido_en);

        $data = $news->toArray();
        $isEn = str_starts_with(strtolower($lang), 'en');

        if ($isEn) {
            if (empty($news->titulo_en) && !empty($news->titulo)) {
                $trans = TranslationService::translate($news->titulo, 'es', 'en');
                $news->titulo_en = !empty($trans) ? $trans : $news->titulo;
            }
            if (empty($news->contenido_en) && !empty($news->contenido)) {
                $trans = $this->translateContentBlocks($news->contenido, 'es', 'en');
                $news->contenido_en = !empty($trans) ? $trans : $news->contenido;
            } elseif (!empty($news->contenido) && $this->countBlocks($news->contenido_en) < $this->countBlocks($news->contenido)) {
                $trans = $this->translateContentBlocks($news->contenido, 'es', 'en');
                if (!empty($trans)) {
                    $news->contenido_en = $trans;
                }
            }

            if ($news->isDirty()) {
                $news->save();
            }

            $data['titulo'] = !empty($news->titulo_en) ? $news->titulo_en : $news->titulo;
            $data['contenido'] = !empty($news->contenido_en) ? $news->contenido_en : $news->contenido;
        } else {
            // Spanish requested
            if (!empty($news->contenido_en) && (empty($news->contenido) || $this->countBlocks($news->contenido) < $this->countBlocks($news->contenido_en))) {
                $trans = $this->translateContentBlocks($news->contenido_en, 'en', 'es');
                if (!empty($trans)) {
                    $news->contenido = $trans;
                    if (empty($news->titulo) && !empty($news->titulo_en)) {
                        $transTit = TranslationService::translate($news->titulo_en, 'en', 'es');
                        $news->titulo = !empty($transTit) ? $transTit : $news->titulo_en;
                    }
                    $news->save();
                }
            }

            if ($news->isDirty()) {
                $news->save();
            }

            $data['titulo'] = !empty($news->titulo) ? $news->titulo : $news->titulo_en;
            $data['contenido'] = !empty($news->contenido) ? $news->contenido : $news->contenido_en;
        }

        $data['imagen_url'] = $this->formatImageUrl($data['imagen_url'] ?? null);
        $data['contenido'] = $this->formatContentBlockImageUrls($data['contenido'] ?? null);

        return $data;
    }

    public function index(Request $request)
    {
        $lang = $request->query('lang', $request->header('Accept-Language', 'es'));
        $query = NewsArticle::query();

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

            $targetField = (str_starts_with(strtolower($lang), 'en')) ? 'titulo_en' : 'titulo';

            $query->where(function($q) use ($targetField, $rawSearch, $search, $searchNoAccents, $lang) {
                $q->whereRaw("LOWER({$targetField}) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER({$targetField}) LIKE ?", ["%{$searchNoAccents}%"])
                  ->orWhere($targetField, 'like', "%{$rawSearch}%");

                if (str_starts_with(strtolower($lang), 'en')) {
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
            $paginated->getCollection()->transform(function ($news) use ($lang) {
                return $this->localizeNews($news, $lang);
            });
            return response()->json($paginated, 200);
        }

        if ($request->has('limit') && !empty($request->query('limit'))) {
            $query->limit((int)$request->query('limit'));
        }

        $articles = $query->get()->map(function ($news) use ($lang) {
            return $this->localizeNews($news, $lang);
        });

        return response()->json($articles, 200);
    }

    public function getYears()
    {
        $years = NewsArticle::where('estado', 'Publicado')
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
            'autor' => 'required|string',
            'contenido' => 'required|string',
            'imagen_url' => 'nullable|string',
            'estado' => 'required|string'
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        $validated['imagen_url'] = $this->handleImageUpload($validated['imagen_url'] ?? null);
        $validated['contenido'] = $this->processContentImageBlocks($validated['contenido']);
        $validated['contenido'] = $this->normalizeBlocks($validated['contenido']);

        if ($isEn) {
            $validated['titulo_en'] = $validated['titulo'];
            $validated['contenido_en'] = $validated['contenido'];
            $transTit = TranslationService::translate($validated['titulo'], 'en', 'es');
            $transCont = $this->translateContentBlocks($validated['contenido'], 'en', 'es');
            $validated['titulo'] = !empty($transTit) ? $transTit : $validated['titulo'];
            $validated['contenido'] = !empty($transCont) ? $transCont : $validated['contenido'];
        } else {
            $transTit = TranslationService::translate($validated['titulo'], 'es', 'en');
            $transCont = $this->translateContentBlocks($validated['contenido'], 'es', 'en');
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

        $news = NewsArticle::findOrFail($id);
        $oldMainImage = $this->normalizeImagePath($news->imagen_url);
        $oldContentImages = array_values(array_unique(array_merge(
            $this->extractImagePathsFromContent($news->contenido),
            $this->extractImagePathsFromContent($news->contenido_en)
        )));

        $validated['imagen_url'] = $this->handleImageUpload($validated['imagen_url'] ?? null);
        $validated['contenido'] = $this->processContentImageBlocks($validated['contenido']);
        $validated['contenido'] = $this->normalizeBlocks($validated['contenido']);

        if ($isEn) {
            $validated['titulo_en'] = $validated['titulo'];
            $validated['contenido_en'] = $validated['contenido'];
            $transTit = TranslationService::translate($validated['titulo'], 'en', 'es');
            $transCont = $this->translateContentBlocks($validated['contenido'], 'en', 'es');
            $validated['titulo'] = !empty($transTit) ? $transTit : $validated['titulo'];
            $validated['contenido'] = !empty($transCont) ? $transCont : $validated['contenido'];
        } else {
            $transTit = TranslationService::translate($validated['titulo'], 'es', 'en');
            $transCont = $this->translateContentBlocks($validated['contenido'], 'es', 'en');
            $validated['titulo_en'] = !empty($transTit) ? $transTit : $validated['titulo'];
            $validated['contenido_en'] = !empty($transCont) ? $transCont : $validated['contenido'];
        }

        $newMainNorm = $this->normalizeImagePath($validated['imagen_url']);
        if ($oldMainImage && $oldMainImage !== $newMainNorm) {
            $this->deletePhysicalImage($oldMainImage);
        }

        $newContentImages = array_values(array_unique(array_merge(
            $this->extractImagePathsFromContent($validated['contenido']),
            $this->extractImagePathsFromContent($validated['contenido_en'] ?? null)
        )));

        foreach ($oldContentImages as $oldImg) {
            if (!in_array($oldImg, $newContentImages)) {
                $this->deletePhysicalImage($oldImg);
            }
        }

        $news->update($validated);

        return response()->json($this->localizeNews($news, $lang), 200);
    }

    public function destroy($id)
    {
        $news = NewsArticle::findOrFail($id);

        if ($news->imagen_url) {
            $this->deletePhysicalImage($news->imagen_url);
        }

        $contentImages = array_values(array_unique(array_merge(
            $this->extractImagePathsFromContent($news->contenido),
            $this->extractImagePathsFromContent($news->contenido_en)
        )));

        foreach ($contentImages as $img) {
            $this->deletePhysicalImage($img);
        }

        $news->delete();

        return response()->json(['message' => 'Noticia eliminada correctamente.'], 200);
    }
}
