<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScientificArticle;

class ScientificArticleSeeder extends Seeder
{
    public function run(): void
    {
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
                'referencias' => "[1] A. Al-Fuqaha, et al., \"Internet of Things: A Survey on Enabling Technologies, Protocols, and Applications,\" IEEE Comm. Surveys & Tutorials, vol. 17, no. 4, pp. 2347-2376, 2015.\n[2] W. Zamora and J. Mero, \"Despliegue de Redes LPWAN en Entornos Universitarios de Manabí,\" Revista Científica FACCI, vol. 8, pp. 45-56, 2024.\n[3] LoRa Alliance, \"LoRaWAN Regional Parameters v1.0.3,\" LoRa Alliance Specification, 2021."
            ]);
        }
    }
}
