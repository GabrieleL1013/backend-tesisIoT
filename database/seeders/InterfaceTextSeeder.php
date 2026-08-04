<?php

namespace Database\Seeders;

use App\Models\AppInterface;
use App\Models\InterfaceText;
use Illuminate\Database\Seeder;

class InterfaceTextSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inicioInterface = AppInterface::where('name', 'Inicio')->first();
        $inicioId = $inicioInterface ? $inicioInterface->id : null;

        $texts = [
            ['key' => 'home_hero_badge', 'text' => 'UNIVERSIDAD LAICA ELOY ALFARO DE MANABÍ', 'text_en' => 'LAICA ELOY ALFARO UNIVERSITY OF MANABÍ', 'interface_id' => $inicioId],
            ['key' => 'home_hero_title', 'text' => 'Ecosistema de Internet de las Cosas IoT', 'text_en' => 'Real-Time Environmental IoT Monitoring', 'interface_id' => $inicioId],
            ['key' => 'home_hero_desc', 'text' => 'Una plataforma diseñada para la automatización, monitorización de recursos y la investigación académica.', 'text_en' => 'Advanced sensor node network for monitoring air quality, water, and weather parameters.', 'interface_id' => $inicioId],
            ['key' => 'home_hero_btn_live', 'text' => 'Monitoreo en Vivo', 'text_en' => 'Explore Live Map', 'interface_id' => $inicioId],
            ['key' => 'home_about_badge', 'text' => 'Smart Campus', 'text_en' => 'Smart Campus', 'interface_id' => $inicioId],
            ['key' => 'home_about_title', 'text' => 'Sobre la Red IoT ULEAM', 'text_en' => 'About the ULEAM IoT Network', 'interface_id' => $inicioId],
            ['key' => 'home_about_desc1', 'text' => 'La plataforma de monitoreo IoT es un proyecto interdisciplinario liderado por la Facultad de Ciencias Informáticas (FACCI) y la Dirección de Innovación Tecnológica & Telecomunicaciones (DIT).', 'text_en' => 'The IoT monitoring platform is an interdisciplinary project led by the Faculty of Computer Sciences (FACCI) and the Directorate of Technological Innovation & Telecommunications (DIT).', 'interface_id' => $inicioId],
            ['key' => 'home_about_desc2', 'text' => 'El objetivo principal es proveer infraestructura tecnológica para la digitalización y el monitoreo ambiental del campus universitario.', 'text_en' => 'The main objective is to provide technological infrastructure for digitization and environmental monitoring of the university campus.', 'interface_id' => $inicioId],
            ['key' => 'home_bullet1', 'text' => 'Optimización del uso de energía y recursos hídricos.', 'text_en' => 'Optimization of energy and water resource usage.', 'interface_id' => $inicioId],
            ['key' => 'home_bullet2', 'text' => 'Almacenamiento de datos históricos para análisis científico.', 'text_en' => 'Historical data storage for scientific analysis.', 'interface_id' => $inicioId],
            ['key' => 'home_bullet3', 'text' => 'Despliegue de sensores de hardware y software abierto.', 'text_en' => 'Deployment of open hardware and software sensors.', 'interface_id' => $inicioId],
            ['key' => 'home_stat_title_1', 'text' => 'Sensores Desplegados', 'text_en' => 'Active Sensors', 'interface_id' => $inicioId],
            ['key' => 'home_stat_desc_1', 'text' => 'Puntos de control registrando variables físicas continuamente.', 'text_en' => 'Control points continuously recording physical variables.', 'interface_id' => $inicioId],
            ['key' => 'home_stat_title_2', 'text' => 'Áreas Clave', 'text_en' => 'Key Areas', 'interface_id' => $inicioId],
            ['key' => 'home_stat_desc_2', 'text' => 'Sectores estratégicos bajo supervisión automatizada.', 'text_en' => 'Strategic sectors under automated supervision.', 'interface_id' => $inicioId],
            ['key' => 'home_stat_num_3', 'text' => '24/7', 'text_en' => '24/7', 'interface_id' => $inicioId],
            ['key' => 'home_stat_title_3', 'text' => 'Operatividad', 'text_en' => 'System Status', 'interface_id' => $inicioId],
            ['key' => 'home_stat_desc_3', 'text' => 'Servidor centralizado recopilando tramas de telemetría constantemente.', 'text_en' => 'Centralized server constantly gathering telemetry frames.', 'interface_id' => $inicioId],
            ['key' => 'home_stat_num_4', 'text' => 'FACCI', 'text_en' => 'FACCI', 'interface_id' => $inicioId],
            ['key' => 'home_stat_title_4', 'text' => 'Semillero Académico', 'text_en' => 'Academic Incubator', 'interface_id' => $inicioId],
            ['key' => 'home_stat_desc_4', 'text' => 'Integrado en proyectos integradores y de tesis estudiantiles.', 'text_en' => 'Integrated into capstone projects and student thesis research.', 'interface_id' => $inicioId],
            ['key' => 'home_infra_badge', 'text' => 'Infraestructura', 'text_en' => 'Infrastructure', 'interface_id' => $inicioId],
            ['key' => 'home_infra_title', 'text' => '¿Cómo Funciona la Red IoT?', 'text_en' => 'How Does the IoT Network Work?', 'interface_id' => $inicioId],
            ['key' => 'home_infra_desc', 'text' => 'La arquitectura de adquisición de datos del campus se divide en cuatro capas tecnológicas integradas.', 'text_en' => 'The campus data acquisition architecture is divided into four integrated technology layers.', 'interface_id' => $inicioId],
            ['key' => 'home_arch_title_0', 'text' => 'Sensores Físicos', 'text_en' => 'Physical Sensors', 'interface_id' => $inicioId],
            ['key' => 'home_arch_desc_0', 'text' => 'Puntos terminales calibrados de medición desplegados en campo.', 'text_en' => 'Calibrated endpoint measuring stations deployed in the field.', 'interface_id' => $inicioId],
            ['key' => 'home_arch_title_1', 'text' => 'Red Inalámbrica', 'text_en' => 'Wireless Network', 'interface_id' => $inicioId],
            ['key' => 'home_arch_desc_1', 'text' => 'Transmisión de paquetes a través de gateways LoRaWAN dedicados y Wi-Fi.', 'text_en' => 'Packet transmission through dedicated LoRaWAN gateways and Wi-Fi.', 'interface_id' => $inicioId],
            ['key' => 'home_arch_title_2', 'text' => 'Broker & DB', 'text_en' => 'Broker & Database', 'interface_id' => $inicioId],
            ['key' => 'home_arch_desc_2', 'text' => 'Recepción centralizada mediante broker MQTT y persistencia segura en base de datos.', 'text_en' => 'Centralized reception via MQTT broker and secure database persistence.', 'interface_id' => $inicioId],
            ['key' => 'home_arch_title_3', 'text' => 'Portal de Gestión', 'text_en' => 'Management Portal', 'interface_id' => $inicioId],
            ['key' => 'home_arch_desc_3', 'text' => 'Interfaces web responsivas y paneles de control analíticos para toma de decisiones.', 'text_en' => 'Responsive web interfaces and analytical dashboards for decision-making.', 'interface_id' => $inicioId],
            ['key' => 'home_news_badge', 'text' => 'Actualizaciones', 'text_en' => 'Updates', 'interface_id' => $inicioId],
            ['key' => 'home_news_title', 'text' => 'Hitos e Investigación IoT', 'text_en' => 'Milestones & IoT Research', 'interface_id' => $inicioId],
            ['key' => 'home_news_desc', 'text' => 'Mantente al tanto del progreso de los despliegues de hardware y las publicaciones académicas asociadas al proyecto.', 'text_en' => 'Stay up to date with hardware deployment progress and related academic publications.', 'interface_id' => $inicioId],
        ];

        foreach ($texts as $t) {
            InterfaceText::updateOrCreate(
                ['key' => $t['key']],
                [
                    'interface_id' => $t['interface_id'],
                    'text' => $t['text'],
                    'text_en' => $t['text_en'],
                ]
            );
        }

        // ── Visualizar Mapa ──
        $mapaInterface = AppInterface::where('name', 'Visualizar Mapa')->first();
        $mapaId = $mapaInterface ? $mapaInterface->id : null;

        $mapTexts = [
            ['key' => 'map_main_title',             'text' => 'Monitoreo por Categorías',                                                                                                                      'text_en' => 'Category Monitoring'],
            ['key' => 'map_subtitle',               'text' => 'Visualización en tiempo real y exploración analítica de variables de hardware en los campus ULEAM.',                                           'text_en' => 'Real-time visualization and analytical exploration of hardware variables across ULEAM campuses.'],
            ['key' => 'map_choose_category',        'text' => 'Elige una categoría de investigación',                                                                                                          'text_en' => 'Choose a research category'],
            ['key' => 'map_view_network_nodes',     'text' => 'Ver Nodos de Red',                                                                                                                              'text_en' => 'View Network Nodes'],
            ['key' => 'map_breadcrumb_categories',  'text' => 'Categorías',                                                                                                                                   'text_en' => 'Categories'],
            ['key' => 'map_category_label',         'text' => 'Categoría',                                                                                                                                    'text_en' => 'Category'],
            ['key' => 'map_tab_map',                'text' => 'Mapa',                                                                                                                                         'text_en' => 'Map'],
            ['key' => 'map_tab_history',            'text' => 'Histórico',                                                                                                                                    'text_en' => 'Historical'],
            ['key' => 'map_realtime_data',          'text' => 'Datos en Tiempo Real',                                                                                                                         'text_en' => 'Real-Time Data'],
            ['key' => 'map_select_device_title',    'text' => 'Selecciona un dispositivo para iniciar la visualización',                                                                                       'text_en' => 'Select a device to start visualization'],
            ['key' => 'map_select_device_desc',     'text' => 'Elige uno de los nodos de hardware de la categoría en el selector para cargar la telemetría y gráficos históricos.',                           'text_en' => 'Choose one of the hardware nodes in the selector to load telemetry and historical charts.'],
        ];


        foreach ($mapTexts as $t) {
            InterfaceText::updateOrCreate(
                ['key' => $t['key']],
                [
                    'interface_id' => $mapaId,
                    'text'         => $t['text'],
                    'text_en'      => $t['text_en'],
                ]
            );
        }
    }
}
