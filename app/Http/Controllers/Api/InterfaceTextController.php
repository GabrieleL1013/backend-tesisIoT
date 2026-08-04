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

        return AppInterface::where('path_es', $clean)
            ->orWhere('path_en', $clean)
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

        // Resolve interface_id from path if provided
        if (!$interfaceIdFilter && $pathFilter) {
            $iface = $this->resolveInterface($pathFilter);
            $interfaceIdFilter = $iface?->id;
        }

        $cacheKey = 'interface_texts_cache_' . ($isEn ? 'en' : 'es')
            . ($interfaceIdFilter ? "_iface_{$interfaceIdFilter}" : '_all');

        $texts = Cache::remember($cacheKey, 3600, function () use ($isEn, $interfaceIdFilter) {
            $query = InterfaceText::query();

            if ($interfaceIdFilter) {
                $query->where('interface_id', $interfaceIdFilter);
            }

            $items = $query->get();
            $result = [];

            foreach ($items as $item) {
                if ($isEn) {
                    // Auto-translate if text_en is missing or equal to Spanish text
                    if ((empty($item->text_en) || $item->text_en === $item->text) && !empty($item->text)) {
                        $translated = TranslationService::translate($item->text, 'es', 'en');
                        if ($translated && $translated !== $item->text) {
                            $item->text_en = $translated;
                            $item->save();
                        }
                    }
                    $result[$item->key] = !empty($item->text_en) ? $item->text_en : $item->text;
                } else {
                    $result[$item->key] = $item->text;
                }
            }

            return $result;
        });

        return response()->json($texts, 200, [], JSON_FORCE_OBJECT);
    }

    /**
     * Store or update an interface text with bidirectional auto-translation.
     *
     * Rules:
     * - Editing in ES → text = what user typed, text_en = translated to EN.
     *   If translation fails or returns same word (unknown word), text_en = what user typed.
     * - Editing in EN → text_en = what user typed, text = translated to ES.
     *   If translation fails or returns same word (unknown word), text = what user typed.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key'          => 'required|string|max:255',
            'text'         => 'required|string',
            'interface_id' => 'nullable|integer|exists:app_interfaces,id',
            'path'         => 'nullable|string',
        ]);

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

        if ($isEn) {
            // User edited in English → text_en = exact user input
            // Translate to Spanish → if fails or returns same, use user input as fallback
            $translatedEs = TranslationService::translate($userText, 'en', 'es');

            $updateData['text_en'] = $userText;
            // Only use translation if it's non-empty and different from the source
            $updateData['text'] = (!empty($translatedEs) && $translatedEs !== $userText)
                ? $translatedEs
                : $userText;

            Log::info("InterfaceText [EN→ES] key={$validated['key']} text_en={$userText} text={$updateData['text']}");
        } else {
            // User edited in Spanish → text = exact user input
            // Translate to English → if fails or returns same, use user input as fallback
            $translatedEn = TranslationService::translate($userText, 'es', 'en');

            $updateData['text'] = $userText;
            $updateData['text_en'] = (!empty($translatedEn) && $translatedEn !== $userText)
                ? $translatedEn
                : $userText;

            Log::info("InterfaceText [ES→EN] key={$validated['key']} text={$userText} text_en={$updateData['text_en']}");
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
            : $interfaceText->text;

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
