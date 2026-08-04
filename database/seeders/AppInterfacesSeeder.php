<?php

namespace Database\Seeders;

use App\Models\AppInterface;
use Illuminate\Database\Seeder;

class AppInterfacesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $interfaces = [
            // ── Interfaces Administrativas ──
            [
                'name' => 'Dashboard Admin',
                'name_es' => 'Dashboard Admin',
                'name_en' => 'Admin Dashboard',
                'path' => '/es/admin/dashboard',
                'path_es' => '/es/admin/dashboard',
                'path_en' => '/en/admin/dashboard',
                'description' => 'Panel principal de métricas y resumen del sistema',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Nodos Sensores',
                'name_es' => 'Nodos Sensores',
                'name_en' => 'Sensor Nodes',
                'path' => '/es/admin/nodos',
                'path_es' => '/es/admin/nodos',
                'path_en' => '/en/admin/nodes',
                'description' => 'Gestión de nodos y dispositivos IoT',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Categorías de Nodos',
                'name_es' => 'Categorías de Nodos',
                'name_en' => 'Node Categories',
                'path' => '/es/admin/categorias',
                'path_es' => '/es/admin/categorias',
                'path_en' => '/en/admin/categories',
                'description' => 'Gestión de líneas y áreas de investigación',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Métricas de Nodos',
                'name_es' => 'Métricas de Nodos',
                'name_en' => 'Node Metrics',
                'path' => '/es/admin/metricas',
                'path_es' => '/es/admin/metricas',
                'path_en' => '/en/admin/metrics',
                'description' => 'Gestión de plantillas y variables de medición',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Ubicaciones',
                'name_es' => 'Ubicaciones',
                'name_en' => 'Locations',
                'path' => '/es/admin/ubicaciones',
                'path_es' => '/es/admin/ubicaciones',
                'path_en' => '/en/admin/locations',
                'description' => 'Gestión de ubicaciones físicas y coordenadas GPS',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Usuarios',
                'name_es' => 'Usuarios',
                'name_en' => 'Users',
                'path' => '/es/admin/usuarios',
                'path_es' => '/es/admin/usuarios',
                'path_en' => '/en/admin/users',
                'description' => 'Gestión de usuarios y cuentas del sistema',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Roles',
                'name_es' => 'Roles',
                'name_en' => 'Roles',
                'path' => '/es/admin/roles',
                'path_es' => '/es/admin/roles',
                'path_en' => '/en/admin/roles',
                'description' => 'Gestión de roles y niveles de permiso',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Interfaces',
                'name_es' => 'Interfaces',
                'name_en' => 'Interfaces',
                'path' => '/es/admin/interfaces',
                'path_es' => '/es/admin/interfaces',
                'path_en' => '/en/admin/interfaces',
                'description' => 'Gestión de permisos de accesibilidad de interfaces',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Noticias (Admin)',
                'name_es' => 'Noticias (Admin)',
                'name_en' => 'News (Admin)',
                'path' => '/es/admin/noticias',
                'path_es' => '/es/admin/noticias',
                'path_en' => '/en/admin/news',
                'description' => 'Gestión de publicación de noticias',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Artículos (Admin)',
                'name_es' => 'Artículos (Admin)',
                'name_en' => 'Articles (Admin)',
                'path' => '/es/admin/articulos',
                'path_es' => '/es/admin/articulos',
                'path_en' => '/en/admin/articles',
                'description' => 'Gestión de publicaciones de artículos científicos',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Monitor En Vivo',
                'name_es' => 'Monitor En Vivo',
                'name_en' => 'Live Monitor',
                'path' => '/es/admin/monitor-en-vivo',
                'path_es' => '/es/admin/monitor-en-vivo',
                'path_en' => '/en/admin/live-monitor',
                'description' => 'Panel de monitorización técnica de tramas de telemetría',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Histórico Agregado',
                'name_es' => 'Histórico Agregado',
                'name_en' => 'Aggregated History',
                'path' => '/es/admin/historico',
                'path_es' => '/es/admin/historico',
                'path_en' => '/en/admin/history',
                'description' => 'Consulta técnica de históricos agregados y exportación',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Notificaciones',
                'name_es' => 'Notificaciones',
                'name_en' => 'Notifications',
                'path' => '/es/admin/notificaciones',
                'path_es' => '/es/admin/notificaciones',
                'path_en' => '/en/admin/notifications',
                'description' => 'Buzón y log de alertas emitidas por sensores',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
            [
                'name' => 'Modo de Edición',
                'name_es' => 'Modo de Edición',
                'name_en' => 'Edit Mode',
                'path' => '/modo-edicion',
                'path_es' => '/modo-edicion',
                'path_en' => '/edit-mode',
                'description' => 'Habilita la edición visual de textos y secciones en la interfaz',
                'allowed_roles' => [1, 2],
                'min_level' => 10,
            ],
        ];

        // Eliminar registros de la base de datos que ya no forman parte del catálogo administrativo
        $allowedNames = array_column($interfaces, 'name');
        AppInterface::whereNotIn('name', $allowedNames)->delete();

        foreach ($interfaces as $iface) {
            AppInterface::updateOrCreate(
                ['name' => $iface['name']],
                [
                    'name_es' => $iface['name_es'],
                    'name_en' => $iface['name_en'],
                    'path' => $iface['path'],
                    'path_es' => $iface['path_es'],
                    'path_en' => $iface['path_en'],
                    'description' => $iface['description'],
                    'allowed_roles' => $iface['allowed_roles'],
                    'min_level' => $iface['min_level'],
                ]
            );
        }
    }
}
