<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MetricTemplateController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\LecturaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NewsArticleController;
use App\Http\Controllers\Api\ScientificArticleController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\InterfaceTextController;
use App\Http\Controllers\Api\InterfaceImageController;
use App\Http\Controllers\Api\NodeAlertController;
use App\Http\Controllers\Api\NodeHealthController;

// Ruta pública a la que React apuntará para el Login
Route::post('/login', [AuthController::class, 'login']);

// API de la Plataforma IoT
Route::apiResource('categorias', CategoryController::class);
Route::apiResource('metricas', MetricTemplateController::class);
Route::apiResource('ubicaciones', LocationController::class);
Route::apiResource('nodos', NodeController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('roles', \App\Http\Controllers\RoleController::class);
Route::apiResource('interfaces', \App\Http\Controllers\AppInterfaceController::class);
Route::apiResource('noticias', NewsArticleController::class);
Route::apiResource('articulos', ScientificArticleController::class);
Route::apiResource('contactos', ContactMessageController::class);

use App\Http\Controllers\Api\PublicLecturaController;

// Rutas para textos editables de la interfaz
Route::get('/interface-texts', [InterfaceTextController::class, 'index']);
Route::post('/interface-texts', [InterfaceTextController::class, 'store']);

// Rutas para imágenes editables de la interfaz
Route::get('/interface-images', [InterfaceImageController::class, 'index']);
Route::post('/interface-images', [InterfaceImageController::class, 'store']);
Route::delete('/interface-images/{key}', [InterfaceImageController::class, 'destroy']);

// Rutas de telemetría y lecturas
Route::post('/lecturas', [LecturaController::class, 'store']);
Route::get('/lecturas', [LecturaController::class, 'index']);
Route::get('/lecturas/ultimas', [LecturaController::class, 'latest']);
Route::get('/lecturas/recientes', [LecturaController::class, 'recent']);
Route::post('/lecturas/live-history', [LecturaController::class, 'liveHistory']);

// Ruta dedicada para lecturas históricas públicas (máximo 30 días)
Route::get('/public/lecturas/historico', [PublicLecturaController::class, 'historico']);

// Rutas de alertas y notificaciones del sistema
Route::get('/node-alerts', [NodeAlertController::class, 'index']);
Route::get('/node-alerts/unread-count', [NodeAlertController::class, 'unreadCount']);
Route::get('/node-alerts/latest', [NodeAlertController::class, 'latest']);
Route::put('/node-alerts/mark-all-read', [NodeAlertController::class, 'markAllRead']);
Route::put('/node-alerts/{id}/read', [NodeAlertController::class, 'markRead']);
Route::delete('/node-alerts/{id}', [NodeAlertController::class, 'destroy']);

// Ruta de health check (invocada por el monitor Python)
Route::post('/node-health/check', [NodeHealthController::class, 'check']);

// Rutas protegidas (solo accesibles si React envía el Token Bearer en las cabeceras)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});