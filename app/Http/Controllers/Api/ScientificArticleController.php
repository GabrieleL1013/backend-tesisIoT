<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScientificArticle;
use Illuminate\Http\Request;

class ScientificArticleController extends Controller
{
    public function index()
    {
        // Seed default scientific articles if database is empty on mount
        if (ScientificArticle::count() === 0) {
            ScientificArticle::create([
                'titulo' => 'Monitoreo Climatológico y Calidad del Aire en la ULEAM con Redes IoT LoRaWAN',
                'autores' => 'Dr. Willian Zamora, Ing. Juan Mero, Ing. Luis Cevallos',
                'revista' => 'Revista de Investigación Científica y Tecnológica ULEAM',
                'resumen' => 'Este artículo presenta el diseño y despliegue de una red de monitoreo ambiental basada en tecnología LoRaWAN dentro del campus principal de la Universidad Laica Eloy Alfaro de Manabí (ULEAM). Se analiza la viabilidad de la tecnología para la recolección distribuida de variables como temperatura, humedad, monóxido de carbono (CO) y partículas suspendidas. El sistema integra nodos de bajo consumo y un Gateway institucional que centraliza las lecturas para su posterior análisis histórico.',
                'palabras_clave' => 'Internet de las Cosas (IoT), LoRaWAN, Monitoreo Ambiental, Calidad del Aire, Smart Campus',
                'introduccion' => 'El crecimiento de los centros urbanos y el aumento de las actividades antropogénicas hacen indispensable el monitoreo continuo de los parámetros ambientales. En los últimos años, el concepto de "Smart Campus" ha surgido como un laboratorio vivo para probar tecnologías del Internet de las Cosas (IoT). La tecnología LoRaWAN destaca en este ámbito debido a su largo alcance, alta penetración en interiores y bajísimo consumo energético, ideal para sensores alimentados por baterías que transmiten pequeñas ráfagas de datos de forma periódica.',
                'metodologia' => 'La arquitectura propuesta consta de tres capas principales: Capa de Dispositivos (nodos sensores equipados con transceptores LoRa RFM95W y microcontroladores ESP32), Capa de Red (un Gateway LoRaWAN multicanal configurado para comunicarse con The Things Network) y Capa de Aplicación (un servidor backend en Laravel que recibe los datos mediante Webhooks de HTTP y los almacena en una base de datos PostgreSQL). Los sensores fueron calibrados individualmente antes de su instalación física en puntos estratégicos del campus.',
                'resultados' => 'El Gateway LoRaWAN fue instalado a una altura de 15 metros sobre el edificio de la Facultad de Ciencias de la Vida. Los resultados demuestran una cobertura superior al 98% en todo el perímetro universitario, con un indicador RSSI promedio de -105 dBm en los puntos más alejados. Las lecturas de calidad del aire revelaron concentraciones de CO dentro de los límites permisibles (promedio de 1.8 ppm), registrándose picos menores durante las horas de mayor afluencia vehicular.',
                'conclusiones' => 'La implementación de la infraestructura LoRaWAN en la ULEAM demostró la viabilidad de las redes de largo alcance y bajo consumo para aplicaciones de telemetría académica. El bajo costo de los nodos sensores permite una fácil escalabilidad del sistema para incluir mediciones de ruido acústico e iluminación. Este trabajo sienta las bases para un sistema institucional de alertas tempranas ante contingencias climatológicas o de contaminación del aire.',
                'referencias' => "[1] A. Al-Fuqaha, et al., \"Internet of Things: A Survey on Enabling Technologies, Protocols, and Applications,\" IEEE Comm. Surveys & Tutorials, vol. 17, no. 4, pp. 2347-2376, 2015.\n[2] W. Zamora and J. Mero, \"Despliegue de Redes LPWAN en Entornos Universitarios de Manabí,\" Revista Científica FACCI, vol. 8, pp. 45-56, 2024.\n[3] LoRa Alliance, \"LoRaWAN Regional Parameters v1.0.3,\" LoRa Alliance Specification, 2021.",
                'url_pdf' => null,
                'estado' => 'Publicado'
            ]);

            ScientificArticle::create([
                'titulo' => 'Algoritmos de Aprendizaje Supervisado para la Predicción de Anomalías Térmicas en Servidores de Misión Crítica',
                'autores' => 'MSc. Diana Mendoza, Dr. Willian Zamora',
                'revista' => 'IEEE Latin America Transactions',
                'resumen' => 'La prevención de fallas térmicas en centros de datos es crucial para garantizar la disponibilidad del servicio y extender la vida útil de los equipos de hardware. Este artículo describe la aplicación y comparación de tres algoritmos de aprendizaje automático (Random Forest, SVM y Redes Neuronales Artificiales) para predecir sobrecalentamientos térmicos en los servidores principales de la Dirección de Innovación Tecnológica (DIT) de la ULEAM, utilizando lecturas recolectadas en tiempo real por nodos sensores IoT.',
                'palabras_clave' => 'Aprendizaje Automático, Centros de Datos, Predicción de Temperatura, IoT Industrial, Mantenimiento Predictivo',
                'introduccion' => 'Los centros de datos modernos albergan infraestructura crítica que disipa enormes cantidades de calor. A pesar de los sistemas de aire acondicionado de precisión, pueden ocurrir "puntos calientes" locales debido a fallas en la circulación del aire o sobrecargas de procesamiento de CPU. La predicción proactiva de estas anomalías térmicas es un pilar del mantenimiento predictivo, permitiendo redistribuir cargas de trabajo o alertar al personal técnico antes de que se activen las paradas automáticas de emergencia por hardware.',
                'metodologia' => 'Se recopiló un conjunto de datos histórico de 12 meses conteniendo lecturas térmicas del rack principal, humedad relativa, uso de CPU y velocidad de ventiladores, muestreado a intervalos de 5 minutos. Los datos fueron normalizados y balanceados mediante técnicas SMOTE. Se programó una tubería en Python con scikit-learn para entrenar y validar los modelos con una partición 80/20. Los hiperparámetros fueron optimizados mediante búsqueda en rejilla (Grid Search).',
                'resultados' => 'El modelo basado en Random Forest obtuvo el mejor desempeño general, con una exactitud (Accuracy) del 96.4% y un F1-Score de 0.958 para una ventana de predicción anticipada de 15 minutos. El modelo SVM mostró un buen tiempo de inferencia pero menor capacidad para detectar picos térmicos abruptos. Las redes neuronales lograron un excelente desempeño en ventanas más largas (30 minutos) a costa de un mayor requerimiento computacional.',
                'conclusiones' => 'Los modelos de machine learning entrenados son capaces de pronosticar fallas térmicas con suficiente anticipación para ejecutar medidas correctivas automatizadas. La integración de estos modelos predictivos en el backend IoT institucional permite pasar de una estrategia de monitoreo reactiva a un esquema de seguridad operativa proactiva y automatizada.',
                'referencias' => "[1] R. Bash and C. Patel, \"Thermodynamics and Heat Transfer in Data Centers,\" Journal of Electronic Packaging, vol. 128, no. 2, pp. 110-117, 2006.\n[2] D. Mendoza, \"Mantenimiento Predictivo Basado en IoT para Servidores Académicos,\" ULEAM Tec, vol. 12, pp. 89-101, 2025.\n[3] F. Pedregosa, et al., \"Scikit-learn: Machine Learning in Python,\" Journal of Machine Learning Research, vol. 12, pp. 2825-2830, 2011.",
                'url_pdf' => null,
                'estado' => 'Publicado'
            ]);
        }

        return response()->json(ScientificArticle::all(), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
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

        $article = ScientificArticle::create($request->all());

        return response()->json($article, 201);
    }

    public function show($id)
    {
        $article = ScientificArticle::findOrFail($id);
        return response()->json($article, 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
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
        $article->update($request->all());

        return response()->json($article, 200);
    }

    public function destroy($id)
    {
        $article = ScientificArticle::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Artículo científico eliminado correctamente.'], 200);
    }
}
