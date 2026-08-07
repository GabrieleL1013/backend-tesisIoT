<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppInterface;
use App\Models\InterfaceText;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InterfaceTextController extends Controller
{
    /**
     * Resolve the AppInterface from the current URL path (es or en).
     */
    private function resolveInterface(?string $path): ?AppInterface
    {
        if (empty($path)) return null;

        $clean = strtok(rtrim($path, '/'), '?') ?: '/';

        $iface = AppInterface::where('path_es', $clean)
            ->orWhere('path_en', $clean)
            ->orWhere('path', $clean)
            ->first();

        if ($iface) return $iface;

        $stripped = preg_replace('#^/(es|en)(/.*)?$#i', '$2', $clean) ?: '/';

        return AppInterface::where('path_es', $stripped)
            ->orWhere('path_en', $stripped)
            ->orWhere('path', $stripped)
            ->orWhere('path_es', '/es' . $stripped)
            ->orWhere('path_en', '/en' . $stripped)
            ->first();
    }

    /**
     * Display a listing of interface texts as key-value pairs.
     * Optionally filtered by ?interface_id= or ?path= (current URL path).
     */
    public function index(Request $request)
    {
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        $interfaceIdFilter = $request->query('interface_id');
        $pathFilter = $request->query('path');

        $resolvedFromPath = false;
        if (!$interfaceIdFilter && $pathFilter) {
            $resolvedFromPath = true;
            $iface = $this->resolveInterface($pathFilter);
            $interfaceIdFilter = $iface?->id;
        }

        $cacheKey = 'interface_texts_cache_' . ($isEn ? 'en' : 'es')
            . ($interfaceIdFilter ? "_iface_{$interfaceIdFilter}" : ($resolvedFromPath ? "_path_" . md5($pathFilter) : '_all'));

        $texts = Cache::remember($cacheKey, 3600, function () use ($isEn, $interfaceIdFilter, $resolvedFromPath) {
            $query = InterfaceText::query();

            if ($interfaceIdFilter) {
                // Devolver únicamente los textos asociados a esta interfaz + los textos globales (interface_id IS NULL)
                $query->where(function($q) use ($interfaceIdFilter) {
                    $q->where('interface_id', $interfaceIdFilter)
                      ->orWhereNull('interface_id');
                });
            } else if ($resolvedFromPath) {
                // Si se envió un parámetro ?path pero la ruta no está registrada en app_interfaces, retornar solo globales
                $query->whereNull('interface_id');
            }

            $items = $query->get();
            $result = [];

            foreach ($items as $item) {
                if ($isEn) {
                    $result[$item->key] = !empty($item->text_en) ? $item->text_en : $item->text;
                } else {
                    $result[$item->key] = !empty($item->text) ? $item->text : $item->text_en;
                }
            }

            return $result;
        });

        return response()->json($texts, 200, [], JSON_FORCE_OBJECT);
    }

    /**
     * Store or update an interface text with optional auto-translation.
     *
     * Rules:
     * - auto_translate = true:
     *   - Editing in ES -> text = user input, text_en = translated to EN.
     *   - Editing in EN -> text_en = user input, text = translated to ES.
     * - auto_translate = false:
     *   - Editing in ES -> update ONLY text (text_en remains untouched).
     *   - Editing in EN -> update ONLY text_en (text remains untouched).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key'            => 'required|string|max:255',
            'text'           => 'present|nullable|string',
            'interface_id'   => 'nullable|integer|exists:app_interfaces,id',
            'path'           => 'nullable|string',
            'auto_translate' => 'nullable|boolean',
        ]);

        $autoTranslate = $request->boolean('auto_translate', true);
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        // Resolve interface_id from path if not provided directly
        $interfaceId = $validated['interface_id'] ?? null;
        if (!$interfaceId && !empty($validated['path'])) {
            $iface = $this->resolveInterface($validated['path']);
            $interfaceId = $iface?->id;
        }

        $userText = trim($validated['text']);
        $updateData = ['interface_id' => $interfaceId];

        if ($autoTranslate) {
            if ($isEn) {
                // User edited in English with auto-translate ON
                $translatedEs = TranslationService::translate($userText, 'en', 'es');
                $updateData['text_en'] = $userText;
                $updateData['text'] = (!empty($translatedEs) && $translatedEs !== $userText)
                    ? $translatedEs
                    : $userText;
                Log::info("InterfaceText [EN->ES Auto] key={$validated['key']} text_en={$userText} text={$updateData['text']}");
            } else {
                // User edited in Spanish with auto-translate ON
                $translatedEn = TranslationService::translate($userText, 'es', 'en');
                $updateData['text'] = $userText;
                $updateData['text_en'] = (!empty($translatedEn) && $translatedEn !== $userText)
                    ? $translatedEn
                    : $userText;
                Log::info("InterfaceText [ES->EN Auto] key={$validated['key']} text={$userText} text_en={$updateData['text_en']}");
            }
        } else {
            // Auto-translate OFF
            if ($isEn) {
                // User edited in English without auto-translate -> update ONLY text_en
                $updateData['text_en'] = $userText;
                Log::info("InterfaceText [EN Only] key={$validated['key']} text_en={$userText}");
            } else {
                // User edited in Spanish without auto-translate -> update ONLY text
                $updateData['text'] = $userText;
                Log::info("InterfaceText [ES Only] key={$validated['key']} text={$userText}");
            }
        }

        $interfaceText = InterfaceText::updateOrCreate(
            ['key' => $validated['key']],
            $updateData
        );

        // Bust ALL cache variants so the next GET always returns fresh data
        $this->bustAllCaches($interfaceId);

        // Return the text in the active language
        $responseText = $isEn
            ? (!empty($interfaceText->text_en) ? $interfaceText->text_en : $interfaceText->text)
            : (!empty($interfaceText->text) ? $interfaceText->text : $interfaceText->text_en);

        return response()->json([
            'id'           => $interfaceText->id,
            'key'          => $interfaceText->key,
            'text'         => $responseText,
            'text_es'      => $interfaceText->text,
            'text_en'      => $interfaceText->text_en,
            'interface_id' => $interfaceText->interface_id,
        ], 200);
    }

    /**
     * Clear all interface text cache keys.
     */
    private function bustAllCaches(?int $interfaceId = null): void
    {
        Cache::forget('interface_texts_cache_es_all');
        Cache::forget('interface_texts_cache_en_all');
        Cache::forget('interface_texts_cache_es');
        Cache::forget('interface_texts_cache_en');
        Cache::forget('interface_texts_cache');

        if ($interfaceId) {
            Cache::forget("interface_texts_cache_es_iface_{$interfaceId}");
            Cache::forget("interface_texts_cache_en_iface_{$interfaceId}");
        }

        // Also bust ALL numbered interface caches (brute force)
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget("interface_texts_cache_es_iface_{$i}");
            Cache::forget("interface_texts_cache_en_iface_{$i}");
        }
    }
}
