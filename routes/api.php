<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MetricTemplateController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\LecturaController;
use App\Http\Controllers\Api\PublicLecturaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NewsArticleController;
use App\Http\Controllers\Api\ScientificArticleController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\InterfaceTextController;
use App\Http\Controllers\Api\InterfaceImageController;
use App\Http\Controllers\Api\NodeAlertController;
use App\Http\Controllers\Api\NodeHealthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AppInterfaceController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (Sin Autenticación Requerida)
|--------------------------------------------------------------------------
*/

// Autenticación de usuario protegida contra fuerza bruta (máximo 6 intentos por minuto)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Envío de mensajes de contacto por visitantes del sitio (máximo 5 por minuto)
Route::post('/contactos', [ContactMessageController::class, 'store'])->middleware('throttle:5,1');

// Lectura de contenidos públicos (noticias, artículos científicos, imágenes y textos editables)
Route::get('/noticias', [NewsArticleController::class, 'index']);
Route::get('/noticias/{id}', [NewsArticleController::class, 'show']);

Route::get('/articulos', [ScientificArticleController::class, 'index']);
Route::get('/articulos/{id}', [ScientificArticleController::class, 'show']);

Route::get('/interface-texts', [InterfaceTextController::class, 'index']);
Route::get('/interface-images', [InterfaceImageController::class, 'index']);

// Lectura pública de nodos, categorías, ubicaciones y plantillas de métricas para el mapa y portal
Route::get('/nodos', [NodeController::class, 'index']);
Route::get('/nodos/internal', [NodeController::class, 'internalIndex']);
Route::get('/nodos/{id}', [NodeController::class, 'show']);

Route::get('/categorias', [CategoryController::class, 'index']);
Route::get('/categorias/{id}', [CategoryController::class, 'show']);

Route::get('/ubicaciones', [LocationController::class, 'index']);
Route::get('/ubicaciones/{id}', [LocationController::class, 'show']);

Route::get('/metricas', [MetricTemplateController::class, 'index']);
Route::get('/metricas/{id}', [MetricTemplateController::class, 'show']);

// Rutas de telemetría e ingesta de sensores IoT / Monitoreo (protegidas por X-Internal-Secret e IP de servicio)
Route::post('/lecturas', [LecturaController::class, 'store']);
Route::get('/lecturas', [LecturaController::class, 'index']);
Route::get('/lecturas/ultimas', [LecturaController::class, 'latest']);
Route::get('/lecturas/recientes', [LecturaController::class, 'recent']);
Route::post('/lecturas/live-history', [LecturaController::class, 'liveHistory']);
Route::get('/public/lecturas/historico', [PublicLecturaController::class, 'historico']);
Route::post('/node-health/check', [NodeHealthController::class, 'check']);


/*
|--------------------------------------------------------------------------
| Rutas Protegidas por Autenticación (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Cierre de sesión
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestión de mensajes de contacto recibidos
    Route::get('/contactos', [ContactMessageController::class, 'index']);
    Route::get('/contactos/{id}', [ContactMessageController::class, 'show']);
    Route::delete('/contactos/{id}', [ContactMessageController::class, 'destroy']);

    // Operaciones de escritura para Nodos, Categorías, Ubicaciones y Métricas
    Route::post('/nodos', [NodeController::class, 'store']);
    Route::put('/nodos/{id}', [NodeController::class, 'update']);
    Route::delete('/nodos/{id}', [NodeController::class, 'destroy']);

    Route::post('/categorias', [CategoryController::class, 'store']);
    Route::put('/categorias/{id}', [CategoryController::class, 'update']);
    Route::delete('/categorias/{id}', [CategoryController::class, 'destroy']);

    Route::post('/ubicaciones', [LocationController::class, 'store']);
    Route::put('/ubicaciones/{id}', [LocationController::class, 'update']);
    Route::delete('/ubicaciones/{id}', [LocationController::class, 'destroy']);

    Route::post('/metricas', [MetricTemplateController::class, 'store']);
    Route::put('/metricas/{id}', [MetricTemplateController::class, 'update']);
    Route::delete('/metricas/{id}', [MetricTemplateController::class, 'destroy']);

    // Operaciones de escritura para Noticias y Artículos
    Route::post('/noticias', [NewsArticleController::class, 'store']);
    Route::put('/noticias/{id}', [NewsArticleController::class, 'update']);
    Route::delete('/noticias/{id}', [NewsArticleController::class, 'destroy']);

    Route::post('/articulos', [ScientificArticleController::class, 'store']);
    Route::put('/articulos/{id}', [ScientificArticleController::class, 'update']);
    Route::delete('/articulos/{id}', [ScientificArticleController::class, 'destroy']);

    // Operaciones de edición para Textos e Imágenes de Interfaz
    Route::post('/interface-texts', [InterfaceTextController::class, 'store']);
    Route::post('/interface-images', [InterfaceImageController::class, 'store']);
    Route::delete('/interface-images/{key}', [InterfaceImageController::class, 'destroy']);

    // Alertas y notificaciones del sistema
    Route::get('/node-alerts', [NodeAlertController::class, 'index']);
    Route::get('/node-alerts/unread-count', [NodeAlertController::class, 'unreadCount']);
    Route::get('/node-alerts/latest', [NodeAlertController::class, 'latest']);
    Route::put('/node-alerts/mark-all-read', [NodeAlertController::class, 'markAllRead']);
    Route::put('/node-alerts/{id}/read', [NodeAlertController::class, 'markRead']);
    Route::delete('/node-alerts/{id}', [NodeAlertController::class, 'destroy']);

    // Consulta básica de Usuarios, Roles e Interfaces por parte de personal autenticado
    Route::get('/users/count', [UserController::class, 'count']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);

    Route::get('/interfaces', [AppInterfaceController::class, 'index']);
    Route::get('/interfaces/{id}', [AppInterfaceController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Rutas de Administración Elevada (Requieren Rol Superusuario)
    |--------------------------------------------------------------------------
    */
    Route::middleware('superadmin')->group(function () {
        // Gestión de usuarios
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Gestión de roles
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{id}', [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

        // Gestión de permisos de interfaces
        Route::post('/interfaces', [AppInterfaceController::class, 'store']);
        Route::put('/interfaces/{id}', [AppInterfaceController::class, 'update']);
        Route::delete('/interfaces/{id}', [AppInterfaceController::class, 'destroy']);
    });
});