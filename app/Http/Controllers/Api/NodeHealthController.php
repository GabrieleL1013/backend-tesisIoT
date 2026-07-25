<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\NodeAlert;
use App\Models\Lectura;
use App\Models\SubvariableTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NodeHealthController extends Controller
{
    /**
     * Verificar la salud de todos los nodos activos.
     * Genera alertas por:
     * 1. Nodo offline (sin datos por 5+ minutos)
     * 2. Nodo inestable (>30% de lecturas fuera de rango en el intervalo configurado)
     */
    public function check()
    {
        $nodes = Node::where('estado', true)
            ->with(['subvariables', 'lecturas' => function ($q) {
                $q->latest('created_at')->limit(1);
            }])
            ->get();

        $alertsCreated = 0;
        $now = Carbon::now();

        foreach ($nodes as $node) {
            // ── 1. CHECK OFFLINE ──
            $alertsCreated += $this->checkOffline($node, $now);

            // ── 2. CHECK INSTABILITY ──
            $alertsCreated += $this->checkInstability($node, $now);
        }

        return response()->json([
            'message' => "Health check completado. {$alertsCreated} alertas generadas.",
            'alerts_created' => $alertsCreated,
            'nodes_checked' => $nodes->count(),
            'checked_at' => $now->toDateTimeString()
        ]);
    }

    /**
     * Verifica si un nodo está offline (sin datos por 5+ minutos).
     */
    private function checkOffline(Node $node, Carbon $now): int
    {
        $lastReading = $node->lecturas->first();

        // Si no hay lecturas, considerar offline
        $isOffline = true;
        if ($lastReading) {
            $lastReadingTime = Carbon::parse($lastReading->created_at);
            $minutesSinceLastReading = $lastReadingTime->diffInMinutes($now);
            $isOffline = $minutesSinceLastReading >= 5;
        }

        if (!$isOffline) {
            // Si el nodo volvió a estar online, resolver alertas existentes
            NodeAlert::where('node_id', $node->id)
                ->where('type', 'offline')
                ->whereNull('resolved_at')
                ->update(['resolved_at' => $now]);
            return 0;
        }

        // Verificar si ya existe una alerta offline no resuelta para este nodo
        $existingAlert = NodeAlert::where('node_id', $node->id)
            ->where('type', 'offline')
            ->whereNull('resolved_at')
            ->first();

        if ($existingAlert) {
            return 0; // Ya hay una alerta activa, no duplicar
        }

        // Crear nueva alerta
        $minutesAgo = $lastReading
            ? Carbon::parse($lastReading->created_at)->diffInMinutes($now)
            : 999;

        NodeAlert::create([
            'node_id' => $node->id,
            'type' => 'offline',
            'title' => "Nodo sin conexión: {$node->serial_number}",
            'message' => "El nodo \"{$node->nombre}\" ({$node->serial_number}) no ha enviado datos en los últimos {$minutesAgo} minutos. Última lectura: " .
                ($lastReading ? Carbon::parse($lastReading->created_at)->format('d/m/Y H:i:s') : 'Nunca'),
            'severity' => 'critical',
            'metadata' => [
                'last_reading_at' => $lastReading ? $lastReading->created_at->toDateTimeString() : null,
                'minutes_offline' => $minutesAgo
            ]
        ]);

        return 1;
    }

    /**
     * Verifica si un nodo tiene inestabilidad en sus lecturas.
     * Un nodo es inestable si >30% de sus lecturas recientes caen fuera del rango esperado.
     */
    private function checkInstability(Node $node, Carbon $now): int
    {
        $intervalSeconds = $node->instability_alert_interval ?? 300;
        $intervalStart = $now->copy()->subSeconds($intervalSeconds);

        // Obtener las subvariables del nodo con sus rangos esperados
        $subvariables = $node->subvariables;

        if ($subvariables->isEmpty()) {
            return 0;
        }

        $unstableVariables = [];

        foreach ($subvariables as $sub) {
            $minExpected = $sub->min_expected;
            $maxExpected = $sub->max_expected;

            // Si no hay rango definido, no podemos evaluar inestabilidad
            if ($minExpected === null || $maxExpected === null) {
                continue;
            }

            // Obtener lecturas en el intervalo
            $readings = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->where('created_at', '>=', $intervalStart)
                ->get();

            if ($readings->count() < 3) {
                continue; // Muy pocas lecturas para evaluar
            }

            // Contar lecturas fuera de rango
            $outOfRange = $readings->filter(function ($r) use ($minExpected, $maxExpected) {
                return $r->valor < $minExpected || $r->valor > $maxExpected;
            })->count();

            $percentage = ($outOfRange / $readings->count()) * 100;

            if ($percentage > 30) {
                $unstableVariables[] = [
                    'variable' => $sub->nombre,
                    'clave' => $sub->clave_mqtt,
                    'out_of_range_count' => $outOfRange,
                    'total_readings' => $readings->count(),
                    'percentage' => round($percentage, 1),
                    'min_expected' => $minExpected,
                    'max_expected' => $maxExpected
                ];
            }
        }

        if (empty($unstableVariables)) {
            // Si ya no hay inestabilidad, resolver alertas existentes
            NodeAlert::where('node_id', $node->id)
                ->where('type', 'instability')
                ->whereNull('resolved_at')
                ->update(['resolved_at' => $now]);
            return 0;
        }

        // Verificar si ya existe una alerta de inestabilidad no resuelta reciente
        $existingAlert = NodeAlert::where('node_id', $node->id)
            ->where('type', 'instability')
            ->whereNull('resolved_at')
            ->where('created_at', '>=', $intervalStart)
            ->first();

        if ($existingAlert) {
            return 0; // Ya hay una alerta activa reciente
        }

        // Construir mensaje descriptivo
        $varNames = array_map(fn($v) => "{$v['variable']} ({$v['percentage']}% fuera de rango)", $unstableVariables);
        $varList = implode(', ', $varNames);

        NodeAlert::create([
            'node_id' => $node->id,
            'type' => 'instability',
            'title' => "Inestabilidad detectada: {$node->serial_number}",
            'message' => "El nodo \"{$node->nombre}\" ({$node->serial_number}) presenta inestabilidad en: {$varList}.",
            'severity' => 'warning',
            'metadata' => [
                'unstable_variables' => $unstableVariables,
                'interval_seconds' => $intervalSeconds
            ]
        ]);

        return 1;
    }
}
