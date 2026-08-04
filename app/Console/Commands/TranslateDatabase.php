<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InterfaceText;
use App\Models\NewsArticle;
use App\Models\ScientificArticle;
use App\Models\Category;
use App\Services\TranslationService;

class TranslateDatabase extends Command
{
    protected $signature = 'db:translate-all';
    protected $description = 'Translate all existing records in Postgres database from Spanish to English';

    public function handle()
    {
        $this->info("Starting automatic translation of Postgres database records...");

        // 1. InterfaceTexts
        $this->info("Translating InterfaceTexts...");
        $interfaceTexts = InterfaceText::all();
        foreach ($interfaceTexts as $item) {
            if ((empty($item->text_en) || $item->text_en === $item->text) && !empty($item->text)) {
                $translated = TranslationService::translate($item->text, 'es', 'en');
                if (!empty($translated)) {
                    $item->text_en = $translated;
                    $item->save();
                    $this->line("Translated [{$item->key}]: {$item->text} -> {$item->text_en}");
                }
            }
        }

        // 2. Categories
        $this->info("Translating Categories...");
        $categories = Category::all();
        foreach ($categories as $cat) {
            if ((empty($cat->nombre_en) || $cat->nombre_en === $cat->nombre) && !empty($cat->nombre)) {
                $translated = TranslationService::translate($cat->nombre, 'es', 'en');
                if (!empty($translated)) {
                    $cat->nombre_en = $translated;
                    $cat->save();
                    $this->line("Translated Category [{$cat->nombre}]: -> {$cat->nombre_en}");
                }
            }
        }

        // 3. News Articles
        $this->info("Translating News Articles...");
        $news = NewsArticle::all();
        foreach ($news as $article) {
            if (empty($article->titulo_en) && !empty($article->titulo)) {
                $article->titulo_en = TranslationService::translate($article->titulo, 'es', 'en');
            }
            if (empty($article->contenido_en) && !empty($article->contenido)) {
                $article->contenido_en = TranslationService::translate($article->contenido, 'es', 'en');
            }
            if ($article->isDirty()) {
                $article->save();
                $this->line("Translated News [{$article->id}]: {$article->titulo}");
            }
        }

        // 4. Scientific Articles
        $this->info("Translating Scientific Articles...");
        $articles = ScientificArticle::all();
        $fields = [
            'titulo', 'resumen', 'palabras_clave', 'introduccion',
            'introduccion_imagen_descripcion', 'metodologia',
            'metodologia_imagen_descripcion', 'resultados',
            'resultados_imagen_descripcion', 'conclusiones',
            'conclusiones_imagen_descripcion'
        ];

        foreach ($articles as $article) {
            foreach ($fields as $field) {
                $fieldEn = $field . '_en';
                if (empty($article->$fieldEn) && !empty($article->$field)) {
                    $article->$fieldEn = TranslationService::translate($article->$field, 'es', 'en');
                }
            }
            if ($article->isDirty()) {
                $article->save();
                $this->line("Translated Scientific Article [{$article->id}]: {$article->titulo}");
            }
        }

        $this->info("All database records translated successfully!");
        return 0;
    }
}
