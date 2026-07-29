<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppInterfacesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('app_interfaces')->truncate();

        $interfaces = [
            ['name' => 'Dashboard', 'path' => '/admin/dashboard', 'description' => 'Panel de inicio'],
            ['name' => 'Nodos Sensores', 'path' => '/admin/nodos', 'description' => 'Gestión de nodos y dispositivos'],
            ['name' => 'Categorías de Nodos', 'path' => '/admin/categorias', 'description' => 'Gestión de líneas de investigación'],
            ['name' => 'Métricas de Nodos', 'path' => '/admin/metricas', 'description' => 'Gestión de variables a medir'],
            ['name' => 'Ubicaciones', 'path' => '/admin/ubicaciones', 'description' => 'Mapa y coordenadas'],
            ['name' => 'Usuarios', 'path' => '/admin/usuarios', 'description' => 'Gestión de usuarios'],
            ['name' => 'Roles', 'path' => '/admin/roles', 'description' => 'Gestión de roles'],
            ['name' => 'Interfaces', 'path' => '/admin/interfaces', 'description' => 'Gestión de permisos de interfaces'],
            ['name' => 'Noticias', 'path' => '/admin/noticias', 'description' => 'Gestión de noticias públicas'],
            ['name' => 'Artículos', 'path' => '/admin/articulos', 'description' => 'Gestión de artículos científicos'],
            ['name' => 'Monitor En Vivo', 'path' => '/admin/monitor-en-vivo', 'description' => 'Telemetría en tiempo real'],
            ['name' => 'Histórico Agregado', 'path' => '/admin/historico', 'description' => 'Telemetría histórica y estadísticas'],
            ['name' => 'Notificaciones', 'path' => '/admin/notificaciones', 'description' => 'Buzón de notificaciones'],
            ['name' => 'Modo de Edición', 'path' => '/modo-edicion', 'description' => 'Habilita la edición visual de elementos y secciones en la interfaz pública'],
        ];

        foreach ($interfaces as $iface) {
            DB::table('app_interfaces')->insert([
                'name' => $iface['name'],
                'path' => $iface['path'],
                'description' => $iface['description'],
                'allowed_roles' => json_encode([1, 2]),
                'min_level' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
