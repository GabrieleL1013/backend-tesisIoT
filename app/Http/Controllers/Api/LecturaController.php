<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lectura;
use App\Models\Node;
use App\Services\TelemetryIngestionService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LecturaController extends Controller
{
    /**
     * Recibe lecturas desde el script MQTT_LISTENER.py
     */
    public function store(Request $request, TelemetryIngestionService $telemetryIngestionService)
    {
        $result = $telemetryIngestionService->ingestFromPayload($request->all());

        if ($result['status'] !== 'processed') {
            return response()->json([
                'status' => 'ignored',
                'message' => $result['reason'],
            ], 422);
        }

        Log::info('Lectura procesada', $result);

        return response()->json([
            'status' => 'success',
            'message' => 'Lecturas procesadas y emitidas',
            'data' => $result,
        ]);
    }

    /**
     * Retorna el listado histórico de lecturas para un nodo y variable específica.
     */
    public function index(Request $request)
    {
        $request->validate([
            'serial_number' => 'required',
            'clave_mqtt' => 'nullable|string',
            'filter_mode' => 'nullable|string|in:range,day,hour',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'hour' => 'nullable|integer|min:0|max:23'
        ]);

        $node = Node::where('serial_number', $request->serial_number)->first();
        if (!$node) {
            return response()->json([]);
        }

        $claveMqtt = $request->clave_mqtt;
        $filterMode = $request->input('filter_mode', 'day'); // default 'day'

        $query = Lectura::where('node_id', $node->id);

        if ($claveMqtt) {
            $query->whereHas('subvariable', function ($q) use ($claveMqtt) {
                $q->where('clave_mqtt', $claveMqtt);
            });
        }

        // MODO: HORA ESPECÍFICA (Agrupación manual de X minutos en memoria)
        if ($filterMode === 'hour') {
            $dateStr = $request->input('start_date', Carbon::now('America/Guayaquil')->format('Y-m-d'));
            $hourStr = str_pad($request->input('hour', Carbon::now('America/Guayaquil')->hour), 2, '0', STR_PAD_LEFT);
            
            // Convertir hora local a UTC para consultar DB
            $start = Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr $hourStr:00:00", 'America/Guayaquil')->setTimezone('UTC');
            $end = $start->copy()->addHour();

            $intervalMinutes = (int) $request->input('interval', 5);

            $query->whereBetween('created_at', [$start, $end]);
            $lecturasRaw = $query->orderBy('created_at', 'asc')->get();

            // Agrupar en bloques de X minutos
            $grouped = $lecturasRaw->groupBy(function($item) use ($intervalMinutes) {
                $d = Carbon::parse($item->created_at)->setTimezone('America/Guayaquil');
                $min = (int) (floor($d->minute / $intervalMinutes) * $intervalMinutes);
                $d->minute = $min;
                $d->second = 0;
                return $d->format('Y-m-d H:i');
            });

            $lecturasMap = $grouped->map(function($items, $key) {
                return [
                    'valor' => round($items->avg('valor'), 1),
                    'min' => round($items->min('valor'), 1),
                    'max' => round($items->max('valor'), 1),
                    'fecha' => Carbon::parse($key)->toIso8601String(),
                    'label' => Carbon::parse($key)->format('H:i')
                ];
            })->values();

            return response()->json($lecturasMap);
        }

        // MODOS: RANGO (Días) usando PostgreSQL date_trunc o to_timestamp
        if ($filterMode === 'range') {
            $startStr = $request->input('start_date', Carbon::now('America/Guayaquil')->subDays(7)->format('Y-m-d'));
            $endStr = $request->input('end_date', Carbon::now('America/Guayaquil')->format('Y-m-d'));
            
            $start = Carbon::createFromFormat('Y-m-d H:i:s', "$startStr 00:00:00", 'America/Guayaquil')->setTimezone('UTC');
            $end = Carbon::createFromFormat('Y-m-d H:i:s', "$endStr 23:59:59", 'America/Guayaquil')->setTimezone('UTC');
            
            $intervalMinutes = (int) $request->input('interval', 1440);
            $seconds = $intervalMinutes * 60;

            $query->whereBetween('created_at', [$start, $end]);

            // Offset 18000s = 5 horas para timezone de Ecuador
            $lecturas = $query->selectRaw("
                    to_timestamp(floor((extract('epoch' from created_at) - 18000) / ?) * ? + 18000) AT TIME ZONE 'UTC' as fecha_agrupada, 
                    AVG(valor) as valor_promedio,
                    MAX(valor) as valor_max,
                    MIN(valor) as valor_min
                ", [$seconds, $seconds])
                ->groupBy('fecha_agrupada')
                ->orderBy('fecha_agrupada', 'asc')
                ->get();

            return response()->json($lecturas->map(function ($l) use ($intervalMinutes) {
                $date = Carbon::parse($l->fecha_agrupada)->setTimezone('America/Guayaquil');
                $label = $intervalMinutes >= 1440 ? $date->format('d/m') : $date->format('d/m H:i');
                return [
                    'valor' => round($l->valor_promedio, 1),
                    'min' => round($l->valor_min, 1),
                    'max' => round($l->valor_max, 1),
                    'fecha' => $date->toIso8601String(),
                    'label' => $label
                ];
            }));
        }

        // MODO: DÍA (Agrupación manual en memoria por X minutos)
        if ($filterMode === 'day') {
            $dateStr = $request->input('start_date', Carbon::now('America/Guayaquil')->format('Y-m-d'));
            $start = Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr 00:00:00", 'America/Guayaquil')->setTimezone('UTC');
            $end = Carbon::createFromFormat('Y-m-d H:i:s', "$dateStr 23:59:59", 'America/Guayaquil')->setTimezone('UTC');
            
            $intervalMinutes = (int) $request->input('interval', 60);

            $query->whereBetween('created_at', [$start, $end]);
            $lecturasRaw = $query->orderBy('created_at', 'asc')->get();

            $grouped = $lecturasRaw->groupBy(function($item) use ($intervalMinutes) {
                $d = Carbon::parse($item->created_at)->setTimezone('America/Guayaquil');
                $minutesOfDay = $d->hour * 60 + $d->minute;
                $bucketMinutes = (int) (floor($minutesOfDay / $intervalMinutes) * $intervalMinutes);
                
                $h = (int) floor($bucketMinutes / 60);
                $m = $bucketMinutes % 60;
                
                $d->hour = $h;
                $d->minute = $m;
                $d->second = 0;
                
                return $d->format('Y-m-d H:i');
            });

            $lecturasMap = $grouped->map(function($items, $key) {
                return [
                    'valor' => round($items->avg('valor'), 1),
                    'min' => round($items->min('valor'), 1),
                    'max' => round($items->max('valor'), 1),
                    'fecha' => Carbon::parse($key)->toIso8601String(),
                    'label' => Carbon::parse($key)->format('H:i')
                ];
            })->values();

            return response()->json($lecturasMap);
        }
    }


    /**
     * Retorna la última lectura registrada de cada variable para un nodo específico.
     */
    public function latest(Request $request)
    {
        $request->validate([
            'node_id' => 'required'
        ]);

        $node = Node::findOrFail($request->node_id);
        $subvariables = $node->subvariables;

        $latestReadings = [];
        foreach ($subvariables as $sub) {
            $latest = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $latestReadings[] = [
                'subvariable_id' => $sub->id,
                'clave_mqtt' => $sub->clave_mqtt,
                'nombre' => $sub->nombre,
                'unidad' => $sub->unidad,
                'valor' => $latest ? $latest->valor : null,
                'fecha' => $latest ? $latest->created_at->toIso8601String() : null
            ];
        }

        return response()->json($latestReadings);
    }

    /**
     * Retorna las últimas N lecturas crudas fusionadas para poblar gráficos en vivo.
     */
    public function recent(Request $request)
    {
        $request->validate([
            'serial_number' => 'required'
        ]);

        $node = Node::where('serial_number', $request->serial_number)->first();
        if (!$node) {
            return response()->json([]);
        }

        $limit = $request->input('limit', 50);
        $subvariables = $node->subvariables;
        
        $merged = [];

        foreach ($subvariables as $sub) {
            $lecturas = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($lecturas as $l) {
                $date = $l->created_at->copy()->setTimezone('America/Guayaquil');
                $key = $date->format('Y-m-d H:i:s');
                if (!isset($merged[$key])) {
                    $timeFormatted = $date->format('g:i:s') . ($date->format('A') === 'AM' ? 'a.m.' : 'p.m.');
                    $dateFormatted = $date->format('d/m/Y') . ', ' . $timeFormatted;
                    
                    $merged[$key] = [
                        'dateTime' => $dateFormatted,
                        'shortTime' => $timeFormatted
                    ];
                }
                $merged[$key][$sub->clave_mqtt] = $l->valor;
            }
        }
        
        // Fill missing keys for a complete payload
        foreach ($merged as $key => &$row) {
            foreach ($subvariables as $sub) {
                if (!array_key_exists($sub->clave_mqtt, $row)) {
                    $row[$sub->clave_mqtt] = null;
                }
            }
        }
        
        $result = array_values($merged);
        
        // Sort ascending by key (original database timestamp) rather than formatted date string
        usort($result, function($a, $b) use ($merged) {
            $keyA = array_search($a, $merged);
            $keyB = array_search($b, $merged);
            return strcmp($keyA, $keyB);
        });

        // Carry forward previous known values to fix second-boundary splits
        $lastKnown = [];
        foreach ($result as &$row) {
            foreach ($subvariables as $sub) {
                $key = $sub->clave_mqtt;
                if ($row[$key] === null && isset($lastKnown[$key])) {
                    $row[$key] = $lastKnown[$key];
                } elseif ($row[$key] !== null) {
                    $lastKnown[$key] = $row[$key];
                }
            }
        }

        if (count($result) > $limit) {
            $result = array_slice($result, -$limit);
        }

        return response()->json($result);
    }

    /**
     * Retorna historial reciente y unificado para combinaciones específicas de nodos y variables (Dashboard Avanzado).
     */
    public function liveHistory(Request $request)
    {
        $request->validate([
            'selections' => 'required|array',
            'selections.*.serial_number' => 'required|string',
            'selections.*.clave_mqtt' => 'required|string',
        ]);

        $limit = 30; // Mostrar los últimos 30 ticks en la gráfica
        $merged = [];

        foreach ($request->selections as $sel) {
            $node = Node::where('serial_number', $sel['serial_number'])->first();
            if (!$node) continue;

            $sub = $node->subvariables()->where('clave_mqtt', $sel['clave_mqtt'])->first();
            if (!$sub) continue;

            $lecturas = Lectura::where('node_id', $node->id)
                ->where('subvariable_id', $sub->id)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // La clave única en la gráfica será serial_number + "_" + clave_mqtt
            $dataKey = $sel['serial_number'] . '_' . $sel['clave_mqtt'];

            foreach($lecturas as $l) {
                $date = $l->created_at->copy()->setTimezone('America/Guayaquil');
                $timeKey = $date->format('Y-m-d H:i:s');
                if(!isset($merged[$timeKey])) {
                    $merged[$timeKey] = [
                        'time' => $date->format('H:i:s'), 
                        'sortTime' => $date->timestamp
                    ];
                }
                $merged[$timeKey][$dataKey] = floatval($l->valor);
            }
        }

        $result = array_values($merged);
        
        // Ordenar cronológicamente ascendente
        usort($result, function($a, $b) {
            return $a['sortTime'] <=> $b['sortTime'];
        });

        if (count($result) > $limit) {
            $result = array_slice($result, -$limit);
        }

        return response()->json($result);
    }
}
