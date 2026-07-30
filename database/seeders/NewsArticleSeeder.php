<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewsArticle;

class NewsArticleSeeder extends Seeder
{
    public function run(): void
    {
        if (NewsArticle::count() === 0) {
            NewsArticle::create([
                'titulo' => 'Despliegue de Red LoRaWAN Institucional para Smart Campus',
                'autor' => 'Estudiantes de Ingeniería de Software',
                'contenido' => 'Estudiantes de Ingeniería de Software completaron la instalación de gateways de largo alcance en el Edificio FACCI, habilitando cobertura para cientos de nodos sensores distribuidos en el campus Manta.',
                'imagen_url' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=500&auto=format&fit=crop&q=60',
                'estado' => 'Publicado'
            ]);
            NewsArticle::create([
                'titulo' => 'Análisis de Consumo Eléctrico y Automatización en Laboratorios',
                'autor' => 'Docentes investigadores de la ULEAM',
                'contenido' => 'Docentes investigadores presentaron un reporte sobre la optimización del aire acondicionado en laboratorios de cómputo del Bloque B. Se estima una reducción del 18% en consumo de KW/h mediante IoT.',
                'imagen_url' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=500&auto=format&fit=crop&q=60',
                'estado' => 'Publicado'
            ]);
        }
    }
}
