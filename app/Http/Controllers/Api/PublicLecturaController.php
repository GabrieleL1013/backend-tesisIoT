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

        $startUtc = $startDate->copy();
        $endUtc = $now->copy();

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
                'min_fecha' => $minRecord ? Carbon::parse($minRecord->created_at)->setTimezone('America/Guayaquil')->format('d/m/Y H:i:s') : '--',
                'max' => $maxRecord ? round((float)$maxRecord->valor, 1) : null,
                'max_fecha' => $maxRecord ? Carbon::parse($maxRecord->created_at)->setTimezone('America/Guayaquil')->format('d/m/Y H:i:s') : '--',
                'total' => $globalStats ? (int)$globalStats->total : 0
            ];

            // 2. Consulta agrupada en bloques de $intervalMinutes usando hora local de Ecuador (America/Guayaquil)
            $rows = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->orderBy('created_at', 'asc')
                ->get();

            $buckets = [];
            foreach ($rows as $l) {
                $dt = Carbon::parse($l->created_at)->setTimezone('America/Guayaquil');
                $timestamp = $dt->timestamp;

                if ($intervalMinutes >= 1440) {
                    $bucketTimestamp = $dt->copy()->startOfDay()->timestamp;
                } else {
                    $dayStart = $dt->copy()->startOfDay()->timestamp;
                    $secondsFromDayStart = $timestamp - $dayStart;
                    $bucketSeconds = floor($secondsFromDayStart / $seconds) * $seconds;
                    $bucketTimestamp = $dayStart + $bucketSeconds;
                }

                if (!isset($buckets[$bucketTimestamp])) {
                    $buckets[$bucketTimestamp] = ['vals' => []];
                }
                $buckets[$bucketTimestamp]['vals'][] = floatval($l->valor);
            }

            $series = [];
            if ($intervalMinutes >= 1440) {
                // Generar exactamente 30 puntos diarios (ventana continua de 30 días hasta hoy en hora de Ecuador)
                $nowDay = Carbon::now('America/Guayaquil')->startOfDay();
                $startDay = $nowDay->copy()->subDays(29);

                for ($d = $startDay->copy(); $d->lte($nowDay); $d->addDay()) {
                    $ts = $d->timestamp;
                    $label = $d->format('d/m/Y');
                    $startStr = $d->copy()->startOfDay()->format('d/m/Y 00:00:00');
                    $endStr = $d->copy()->endOfDay()->format('d/m/Y 23:59:59');

                    if (isset($buckets[$ts]) && count($buckets[$ts]['vals']) > 0) {
                        $vals = $buckets[$ts]['vals'];
                        $avg = array_sum($vals) / count($vals);
                        $minVal = min($vals);
                        $maxVal = max($vals);
                        $series[] = [
                            'valor' => round($avg, 1),
                            'min' => round($minVal, 1),
                            'max' => round($maxVal, 1),
                            'fecha' => $d->toIso8601String(),
                            'label' => $label,
                            'inicio_intervalo' => $startStr,
                            'fin_intervalo' => $endStr,
                            'has_data' => true
                        ];
                    } else {
                        // Día sin lecturas registradas: marcar sin datos
                        $series[] = [
                            'valor' => null,
                            'min' => null,
                            'max' => null,
                            'fecha' => $d->toIso8601String(),
                            'label' => $label,
                            'inicio_intervalo' => $startStr,
                            'fin_intervalo' => $endStr,
                            'has_data' => false
                        ];
                    }
                }
            } else {
                // Para agrupaciones intradía (1h, 5h, 12h) utilizadas en exportaciones CSV
                ksort($buckets);
                foreach ($buckets as $ts => $bData) {
                    if (count($bData['vals']) > 0) {
                        $bDate = Carbon::createFromTimestamp($ts, 'America/Guayaquil');
                        $bDateEnd = $bDate->copy()->addMinutes($intervalMinutes)->subSecond();
                        $label = $bDate->format('d/m/Y H:i');
                        $vals = $bData['vals'];
                        $avg = array_sum($vals) / count($vals);
                        $minVal = min($vals);
                        $maxVal = max($vals);
                        $series[] = [
                            'valor' => round($avg, 1),
                            'min' => round($minVal, 1),
                            'max' => round($maxVal, 1),
                            'fecha' => $bDate->toIso8601String(),
                            'label' => $label,
                            'inicio_intervalo' => $bDate->format('d/m/Y H:i:s'),
                            'fin_intervalo' => $bDateEnd->format('d/m/Y H:i:s'),
                            'has_data' => true
                        ];
                    }
                }
            }

            $seriesResult[$clave] = $series;
        }

        return response()->json([
            'series' => $seriesResult,
            'stats' => $statsResult
        ]);
    }
}
