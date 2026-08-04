<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Translate a given text from source language to target language.
     * Uses Google Translate GTX with MyMemory fallback and SSL bypass for local PHP cURL.
     *
     * @param string|null $text
     * @param string $source
     * @param string $target
     * @return string|null
     */
    public static function translate(?string $text, string $source = 'es', string $target = 'en'): ?string
    {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        // 1. Try Google Translate GTX endpoint with Chrome User-Agent
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ])->withoutVerifying()->timeout(6)->get('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl' => $source,
                'tl' => $target,
                'dt' => 't',
                'q' => $text
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                    $translated = '';
                    foreach ($data[0] as $segment) {
                        if (isset($segment[0])) {
                            $translated .= $segment[0];
                        }
                    }
                    if (!empty($translated) && trim($translated) !== '') {
                        return $translated;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Google TranslationService error: " . $e->getMessage());
        }

        // 2. Fallback: MyMemory API
        try {
            $response = Http::withoutVerifying()->timeout(6)->get('https://api.mymemory.translated.net/get', [
                'q' => $text,
                'langpair' => "{$source}|{$target}"
            ]);

            if ($response->successful()) {
                $responseData = $response->json('responseData');
                if (!empty($responseData['translatedText'])) {
                    return $responseData['translatedText'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("MyMemory TranslationService error: " . $e->getMessage());
        }

        return $text;
    }
}
