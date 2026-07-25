<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\Request;

class NewsArticleController extends Controller
{
    public function index()
    {
        // Seed default news if database is empty on mount
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
                'imagen_url' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=500&auto=format&fit=crop&q=60',
                'estado' => 'Publicado'
            ]);
            NewsArticle::create([
                'titulo' => 'Integración de Alarmas Térmicas en el DataCenter de la ULEAM',
                'autor' => 'El equipo de DIT',
                'contenido' => 'El equipo de DIT culminó la configuración de alarmas tempranas para el rack principal. En caso de anomalías climatológicas, el sistema emite notificaciones instantáneas a los operadores.',
                'imagen_url' => 'https://images.unsplash.com/photo-1500485035595-cbe6f645feb1?w=500&auto=format&fit=crop&q=60',
                'estado' => 'Publicado'
            ]);
        }

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
