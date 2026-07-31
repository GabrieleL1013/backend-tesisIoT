<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lectura;
use App\Models\Node;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PublicLecturaController extends Controller
{
    /**
     * Retorna lecturas históricas agrupadas con estadísticas (Min, Max, Promedio) 
     * con una restricción de máximo 30 días para usuarios del portal público.
     */
    public function historico(Request $request)
    {
        $request->validate([
            'node_id' => 'required_without:serial_number',
            'serial_number' => 'required_without:node_id',
            'periodo' => 'nullable|string|in:24h,7d,30d',
            'intervalo' => 'nullable|integer',
            'clave_mqtt' => 'nullable|string'
        ]);

        if ($request->filled('node_id')) {
            $node = Node::find($request->node_id);
        } else {
            $node = Node::where('serial_number', $request->serial_number)->first();
        }

        if (!$node) {
            return response()->json([
                'series' => [],
                'stats' => []
            ]);
        }

        // Definir límite estricto de máximo 30 días atrás
        $now = Carbon::now('America/Guayaquil');
        $maxLimitDate = $now->copy()->subDays(30)->startOfDay();

        $periodo = $request->input('periodo', '30d');
        switch ($periodo) {
            case '24h':
                $startDate = $now->copy()->subHours(24);
                break;
            case '7d':
                $startDate = $now->copy()->subDays(7)->startOfDay();
                break;
            case '30d':
            default:
                $startDate = $now->copy()->subDays(30)->startOfDay();
                break;
        }

        // Garantizar que la fecha de inicio NUNCA supere los 30 días de antigüedad
        if ($startDate->lt($maxLimitDate)) {
            $startDate = $maxLimitDate;
        }

        $startUtc = $startDate->copy()->setTimezone('UTC');
        $endUtc = $now->copy()->setTimezone('UTC');

        // Intervalo de agrupación en minutos (por defecto 60 min)
        $intervalMinutes = (int) $request->input('intervalo', 60);
        if ($intervalMinutes < 15) $intervalMinutes = 15;
        if ($intervalMinutes > 1440) $intervalMinutes = 1440;
        $seconds = $intervalMinutes * 60;

        $subvariables = $node->subvariables;
        if ($request->filled('clave_mqtt')) {
            $subvariables = $subvariables->where('clave_mqtt', $request->clave_mqtt);
        }

        $seriesResult = [];
        $statsResult = [];

        foreach ($subvariables as $sub) {
            $clave = $sub->clave_mqtt;

            // 1. Estadísticas globales del período para esta subvariable
            $globalStats = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->selectRaw('AVG(valor) as promedio, MIN(valor) as min_val, MAX(valor) as max_val, COUNT(*) as total')
                ->first();

            $minRecord = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->orderBy('valor', 'asc')
                ->first();

            $maxRecord = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->orderBy('valor', 'desc')
                ->first();

            $statsResult[$clave] = [
                'tipo' => $sub->nombre,
                'unidad' => $sub->unidad,
                'icono' => $sub->icono,
                'promedio' => ($globalStats && $globalStats->total > 0 && $globalStats->promedio !== null) ? round((float)$globalStats->promedio, 1) : null,
                'min' => $minRecord ? round((float)$minRecord->valor, 1) : null,
                'min_fecha' => $minRecord ? $minRecord->created_at->copy()->setTimezone('America/Guayaquil')->format('d/m/Y, h:i:s a') : '--',
                'max' => $maxRecord ? round((float)$maxRecord->valor, 1) : null,
                'max_fecha' => $maxRecord ? $maxRecord->created_at->copy()->setTimezone('America/Guayaquil')->format('d/m/Y, h:i:s a') : '--',
                'total' => $globalStats ? (int)$globalStats->total : 0
            ];

            // 2. Consulta agrupada en bloques de $intervalMinutes usando PostgreSQL
            $rows = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->selectRaw("
                    to_timestamp(floor((extract('epoch' from created_at) - 18000) / ?) * ? + 18000) AT TIME ZONE 'UTC' as fecha_agrupada, 
                    AVG(valor) as valor_promedio,
                    MAX(valor) as valor_max,
                    MIN(valor) as valor_min
                ", [$seconds, $seconds])
                ->groupBy('fecha_agrupada')
                ->orderBy('fecha_agrupada', 'asc')
                ->get();

            $seriesResult[$clave] = $rows->map(function ($row) use ($intervalMinutes) {
                $date = Carbon::parse($row->fecha_agrupada)->setTimezone('America/Guayaquil');
                $label = $intervalMinutes >= 1440 ? $date->format('d/m/Y') : $date->format('d/m/Y H:i');
                return [
                    'valor' => round((float)$row->valor_promedio, 1),
                    'min' => round((float)$row->valor_min, 1),
                    'max' => round((float)$row->valor_max, 1),
                    'fecha' => $date->toIso8601String(),
                    'label' => $label
                ];
            })->toArray();
        }

        return response()->json([
            'series' => $seriesResult,
            'stats' => $statsResult
        ]);
    }
}
