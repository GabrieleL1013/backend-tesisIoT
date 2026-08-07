<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\SubvariableTemplate;
use App\Models\Category;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    private function canSeeCredentials(Request $request): bool
    {
        if (auth('sanctum')->check()) {
            return true;
        }
        $clientIp = $request->ip();
        return in_array($clientIp, ['127.0.0.1', '::1', 'localhost']);
    }

    private function processBilingualNode(array $data, string $lang): array
    {
        $isEn = str_starts_with(strtolower($lang), 'en');

        if (isset($data['nombre'])) {
            if ($isEn) {
                $data['nombre_en'] = $data['nombre'];
                $trans = TranslationService::translate($data['nombre'], 'en', 'es');
                $data['nombre'] = !empty($trans) ? $trans : $data['nombre'];
            } else {
                $trans = TranslationService::translate($data['nombre'], 'es', 'en');
                $data['nombre_en'] = !empty($trans) ? $trans : $data['nombre'];
            }
        }

        return $data;
    }

    public function count()
    {
        return response()->json(['count' => Node::count()]);
    }

    public function index(Request $request)
    {
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');
        $showCreds = $request->boolean('include_credentials') && auth('sanctum')->check();

        $categories = Category::all();

        $catQuery = $request->query('categoria');
        $nodeIdQuery = $request->query('id');

        $query = Node::with(['subvariables.metricTemplate', 'location', 'lecturas' => function($q) {
            $q->latest('created_at')->limit(1);
        }]);

        if (!empty($catQuery)) {
            $catNorm = strtolower(trim($catQuery));
            $catObj = $categories->first(function($c) use ($catNorm) {
                return strtolower(trim($c->nombre ?? '')) === $catNorm ||
                       strtolower(trim($c->nombre_en ?? '')) === $catNorm ||
                       strtolower(trim($c->nombre_es ?? '')) === $catNorm;
            });

            $validCatNames = array_filter([
                $catQuery,
                $catObj ? $catObj->nombre : null,
                $catObj ? $catObj->nombre_en : null,
                $catObj ? $catObj->nombre_es : null,
            ]);

            $query->whereIn('categoria', array_unique($validCatNames));
        }

        if (!empty($nodeIdQuery)) {
            $query->where('id', $nodeIdQuery);
        }

        $nodes = $query->get();

        $mapped = $nodes->map(function ($node) use ($showCreds, $isEn, $categories) {
            $nombreLocalized = $isEn ? (!empty($node->nombre_en) ? $node->nombre_en : $node->nombre) : $node->nombre;

            $catEs = $node->categoria;
            $catObj = $categories->first(fn($c) => $c->nombre === $catEs || $c->nombre_en === $catEs);
            $catEn = $catObj ? ($catObj->nombre_en ?? TranslationService::translate($catEs, 'es', 'en')) : (TranslationService::translate($catEs, 'es', 'en') ?? $catEs);
            $categoriaLocalized = $isEn ? (!empty($catEn) ? $catEn : $catEs) : $catEs;

            $locEs = $node->location->nombre ?? 'Campus Uleam Manta';
            $locEn = $node->location->nombre_en ?? ($isEn ? (TranslationService::translate($locEs, 'es', 'en') ?? $locEs) : $locEs);
            $ubicacionLocalized = $isEn ? (!empty($locEn) ? $locEn : $locEs) : $locEs;

            $lecturas = $node->subvariables->map(function ($sub) use ($isEn) {
                $tipoEs = $sub->nombre;
                $tipoEn = $sub->nombre_en ?? ($isEn ? (TranslationService::translate($tipoEs, 'es', 'en') ?? $tipoEs) : $tipoEs);
                return [
                    'sensor' => $sub->metricTemplate ? ($isEn ? ($sub->metricTemplate->nombre_en ?? $sub->metricTemplate->nombre) : $sub->metricTemplate->nombre) : 'Sensor Integrado',
                    'data_type' => $sub->clave_mqtt,
                    'tipo' => $isEn ? (!empty($tipoEn) ? $tipoEn : $tipoEs) : $tipoEs,
                    'tipo_es' => $tipoEs,
                    'tipo_en' => $tipoEn,
                    'unidad' => $sub->unidad,
                    'icono' => $sub->icono,
                    'minExpected' => $sub->min_expected,
                    'maxExpected' => $sub->max_expected
                ];
            });

            $isOnline = false;
            if ($node->lecturas->isNotEmpty()) {
                $lastReadingTime = \Carbon\Carbon::parse($node->lecturas->first()->created_at);
                $isOnline = $lastReadingTime->diffInSeconds(now()) <= 60;
            }

            return [
                'id' => (string)$node->id,
                'nombre' => $nombreLocalized,
                'nombre_es' => $node->nombre,
                'nombre_en' => $node->nombre_en ?? $node->nombre,
                'serial_number' => $node->serial_number,
                'ubicacion_id' => (string)$node->location_id,
                'ubicacion_nombre' => $ubicacionLocalized,
                'ubicacion_nombre_es' => $locEs,
                'ubicacion_nombre_en' => $locEn,
                'latitud' => $node->location->latitud ?? '-0.951389',
                'longitud' => $node->location->longitud ?? '-80.702476',
                'categoria' => $categoriaLocalized,
                'categoria_es' => $catEs,
                'categoria_en' => $catEn,
                'lecturas' => $lecturas->toArray(),
                'estado' => (bool)$node->estado,
                'is_online' => $isOnline,
                'has_data' => $node->lecturas->isNotEmpty(),
                'broker' => $showCreds ? $node->broker : null,
                'port' => $showCreds ? $node->port : null,
                'topic_data' => $showCreds ? $node->topic_data : null,
                'client_id' => $showCreds ? $node->client_id : null,
                'username' => $showCreds ? $node->username : null,
                'password' => $showCreds ? $node->password : null,
                'location_slug' => $showCreds ? $node->location_slug : null,
                'use_mqtt_v5' => $showCreds ? $node->use_mqtt_v5 : null,
                'is_simulated' => $showCreds ? $node->is_simulated : null,
                'save_frequency' => $showCreds ? $node->save_frequency : null,
                'instability_alert_interval' => $showCreds ? $node->instability_alert_interval : null
            ];
        });

        return response()->json($mapped);
    }

    public function internalIndex(Request $request)
    {
        $clientIp = $request->ip();
        $isLocalhost = in_array($clientIp, ['127.0.0.1', '::1', 'localhost']);
        $internalSecret = $request->header('X-Internal-Secret');
        $expectedSecret = env('INTERNAL_API_SECRET', config('app.key'));

        $isAuthorized = $isLocalhost || (!empty($internalSecret) && !empty($expectedSecret) && hash_equals($expectedSecret, $internalSecret));

        if (!$isAuthorized && !auth('sanctum')->check()) {
            return response()->json(['message' => 'No autorizado para acceder a configuración interna de MQTT.'], 403);
        }

        $nodes = Node::with(['subvariables.metricTemplate', 'location', 'lecturas' => function($q) {
            $q->latest('created_at')->limit(1);
        }])->get();

        $mapped = $nodes->map(function ($node) {
            $lecturas = $node->subvariables->map(function ($sub) {
                return [
                    'sensor' => $sub->metricTemplate->nombre ?? 'Sensor Integrado',
                    'data_type' => $sub->clave_mqtt,
                    'tipo' => $sub->nombre,
                    'unidad' => $sub->unidad,
                    'icono' => $sub->icono,
                    'minExpected' => $sub->min_expected,
                    'maxExpected' => $sub->max_expected
                ];
            });

            $isOnline = false;
            if ($node->lecturas->isNotEmpty()) {
                $lastReadingTime = \Carbon\Carbon::parse($node->lecturas->first()->created_at);
                $isOnline = $lastReadingTime->diffInSeconds(now()) <= 60;
            }

            return [
                'id' => (string)$node->id,
                'nombre' => $node->nombre,
                'serial_number' => $node->serial_number,
                'ubicacion_id' => (string)$node->location_id,
                'ubicacion_nombre' => $node->location->nombre ?? 'Campus Uleam Manta',
                'latitud' => $node->location->latitud ?? '-0.951389',
                'longitud' => $node->location->longitud ?? '-80.702476',
                'categoria' => $node->categoria,
                'lecturas' => $lecturas,
                'estado' => $node->estado,
                'is_online' => $isOnline,
                'broker' => $node->broker,
                'port' => $node->port,
                'topic_data' => $node->topic_data,
                'client_id' => $node->client_id,
                'username' => $node->username,
                'password' => $node->password,
                'location_slug' => $node->location_slug,
                'use_mqtt_v5' => $node->use_mqtt_v5,
                'is_simulated' => $node->is_simulated,
                'save_frequency' => $node->save_frequency,
                'instability_alert_interval' => $node->instability_alert_interval
            ];
        });

        return response()->json($mapped);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'serial_number' => 'required|string|unique:nodes,serial_number',
            'ubicacion_id' => 'required',
            'categoria' => 'required|string',
            'lecturas' => 'nullable|array',
            'broker' => 'nullable|string',
            'port' => 'nullable|numeric',
            'topic_data' => 'nullable|string',
            'client_id' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'location_slug' => 'nullable|string',
            'use_mqtt_v5' => 'boolean',
            'is_simulated' => 'boolean',
            'save_frequency' => 'nullable|integer|min:1',
            'instability_alert_interval' => 'nullable|integer|min:30'
        ]);

        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        
        $nodeData = [
            'nombre' => $request->nombre,
            'serial_number' => $request->serial_number,
            'categoria' => $request->categoria,
            'location_id' => $request->ubicacion_id,
            'estado' => true,
            'broker' => $request->broker,
            'port' => $request->port,
            'topic_data' => $request->topic_data,
            'client_id' => $request->client_id,
            'username' => $request->username,
            'password' => $request->password,
            'location_slug' => $request->location_slug,
            'use_mqtt_v5' => $request->use_mqtt_v5 ?? false,
            'is_simulated' => $request->is_simulated ?? false,
            'save_frequency' => $request->save_frequency ?? 30,
            'instability_alert_interval' => $request->instability_alert_interval ?? 300
        ];

        $nodeData = $this->processBilingualNode($nodeData, $lang);

        $node = Node::create($nodeData);

        if (!empty($request->lecturas)) {
            $subvariableIds = [];
            foreach ($request->lecturas as $lectura) {
                $sub = SubvariableTemplate::where('clave_mqtt', $lectura['data_type'])
                    ->whereHas('metricTemplate', function ($q) use ($lectura) {
                        $q->where('nombre', $lectura['sensor']);
                    })->first();

                if ($sub) {
                    $subvariableIds[] = $sub->id;
                }
            }
            $node->subvariables()->sync($subvariableIds);
        }

        return response()->json($node, 201);
    }

    public function show(Request $request, $id)
    {
        $showCreds = $this->canSeeCredentials($request);
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));
        $isEn = str_starts_with($lang, 'en');

        $node = Node::with(['subvariables.metricTemplate', 'location'])->findOrFail($id);
        
        $lecturas = $node->subvariables->map(function ($sub) use ($isEn) {
            $tipoEs = $sub->nombre;
            $tipoEn = $sub->nombre_en ?? ($isEn ? (\App\Services\TranslationService::translate($tipoEs, 'es', 'en') ?? $tipoEs) : $tipoEs);
            return [
                'sensor' => $sub->metricTemplate ? $sub->metricTemplate->nombre : 'Sensor Integrado',
                'data_type' => $sub->clave_mqtt,
                'tipo' => $isEn ? (!empty($tipoEn) ? $tipoEn : $tipoEs) : $tipoEs,
                'tipo_es' => $tipoEs,
                'tipo_en' => $tipoEn,
                'unidad' => $sub->unidad,
                'icono' => $sub->icono,
                'minExpected' => $sub->min_expected,
                'maxExpected' => $sub->max_expected
            ];
        });

        return response()->json([
            'id' => (string)$node->id,
            'nombre' => $node->nombre,
            'nombre_es' => $node->nombre,
            'nombre_en' => $node->nombre_en ?? $node->nombre,
            'serial_number' => $node->serial_number,
            'ubicacion_id' => (string)$node->location_id,
            'ubicacion_nombre' => $node->location->nombre ?? 'Campus Uleam Manta',
            'latitud' => $node->location->latitud ?? '-0.951389',
            'longitud' => $node->location->longitud ?? '-80.702476',
            'categoria' => $node->categoria,
            'categoria_es' => $node->categoria,
            'lecturas' => $lecturas,
            'estado' => $node->estado,
            'broker' => $showCreds ? $node->broker : null,
            'port' => $showCreds ? $node->port : null,
            'topic_data' => $showCreds ? $node->topic_data : null,
            'client_id' => $showCreds ? $node->client_id : null,
            'username' => $showCreds ? $node->username : null,
            'password' => $showCreds ? $node->password : null,
            'location_slug' => $node->location_slug,
            'use_mqtt_v5' => $node->use_mqtt_v5,
            'is_simulated' => $node->is_simulated,
            'save_frequency' => $node->save_frequency,
            'instability_alert_interval' => $node->instability_alert_interval
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string',
            'serial_number' => 'required|string|unique:nodes,serial_number,' . $id,
            'ubicacion_id' => 'required',
            'categoria' => 'required|string',
            'lecturas' => 'nullable|array',
            'broker' => 'nullable|string',
            'port' => 'nullable|numeric',
            'topic_data' => 'nullable|string',
            'client_id' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'location_slug' => 'nullable|string',
            'use_mqtt_v5' => 'boolean',
            'is_simulated' => 'boolean',
            'save_frequency' => 'nullable|integer|min:1',
            'instability_alert_interval' => 'nullable|integer|min:30'
        ]);

        $node = Node::findOrFail($id);
        $lang = strtolower($request->query('lang', $request->header('Accept-Language', 'es')));

        $nodeData = [
            'nombre' => $request->nombre,
            'serial_number' => $request->serial_number,
            'categoria' => $request->categoria,
            'location_id' => $request->ubicacion_id,
            'broker' => $request->broker,
            'port' => $request->port,
            'topic_data' => $request->topic_data,
            'client_id' => $request->client_id,
            'username' => $request->username,
            'password' => $request->password,
            'location_slug' => $request->location_slug,
            'use_mqtt_v5' => $request->use_mqtt_v5 ?? false,
            'is_simulated' => $request->is_simulated ?? false,
            'save_frequency' => $request->save_frequency ?? 30,
            'instability_alert_interval' => $request->instability_alert_interval ?? 300
        ];

        $nodeData = $this->processBilingualNode($nodeData, $lang);

        $node->update($nodeData);

        if (!empty($request->lecturas)) {
            $subvariableIds = [];
            foreach ($request->lecturas as $lectura) {
                $sub = SubvariableTemplate::where('clave_mqtt', $lectura['data_type'])
                    ->whereHas('metricTemplate', function ($q) use ($lectura) {
                        $q->where('nombre', $lectura['sensor']);
                    })->first();

                if ($sub) {
                    $subvariableIds[] = $sub->id;
                }
            }
            $node->subvariables()->sync($subvariableIds);
        } else {
            $node->subvariables()->detach();
        }

        return response()->json($node);
    }

    public function destroy($id)
    {
        $node = Node::findOrFail($id);
        $node->delete();

        return response()->json(['message' => 'Nodo eliminado correctamente.']);
    }
}
