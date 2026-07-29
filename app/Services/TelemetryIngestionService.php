<?php

namespace App\Services;

use App\Events\LecturaRecibida;
use App\Models\Lectura;
use App\Models\Node;
use Illuminate\Support\Facades\Cache;

class TelemetryIngestionService
{
    public function __construct(
        protected TelemetryPayloadNormalizer $normalizer,
    ) {
    }

    public function ingestFromPayload(array $payload, ?string $topic = null): array
    {
        $normalized = $this->normalizer->normalize($payload, $topic);

        if ($normalized === null) {
            return [
                'status' => 'ignored',
                'reason' => 'Payload sin serial o sin métricas numéricas.',
            ];
        }

        $node = Node::with('subvariables')
            ->where('serial_number', $normalized['serial_number'])
            ->first();

        $nodeExists = $node !== null;

        if (!$nodeExists) {
            $node = new Node([
                'serial_number' => $normalized['serial_number'],
                'nombre' => $normalized['serial_number'],
            ]);
            $node->setRelation('subvariables', collect());
        }

        $shouldSave = $node->exists && $this->shouldPersistReadings($node);
        $savedMetricsCount = 0;
        $savedData = [];
        $receivedMetricKeys = [];
        $savedMetricKeys = [];
        $ignoredMetricKeys = [];
        $subvariablesByKey = $node->subvariables->keyBy('clave_mqtt');

        foreach ($normalized['metrics'] as $key => $value) {
            $receivedMetricKeys[] = $key;
            $savedData[$key] = $value;

            if (!$shouldSave) {
                $ignoredMetricKeys[$key] = 'save_frequency';
                continue;
            }

            $subvariable = $subvariablesByKey->get($key);

            if (!$subvariable) {
                $ignoredMetricKeys[$key] = 'subvariable_not_mapped';
                continue;
            }

            Lectura::create([
                'node_id' => $node->id,
                'subvariable_id' => $subvariable->id,
                'valor' => $value,
            ]);

            $savedMetricsCount++;
            $savedMetricKeys[] = $key;
        }

        if ($savedData !== []) {
            $savedData['timestamp'] = $normalized['timestamp'];
            $savedData['dateTime'] = $normalized['dateTime'];

            event(new LecturaRecibida($node, $savedData));
        }

        return [
            'status' => 'processed',
            'serial_number' => $node->serial_number,
            'node_exists' => $nodeExists,
            'node_id' => $node->id,
            'metrics_count' => count($normalized['metrics']),
            'saved_metrics_count' => $savedMetricsCount,
            'received_metric_keys' => $receivedMetricKeys,
            'saved_metric_keys' => $savedMetricKeys,
            'ignored_metric_keys' => $ignoredMetricKeys,
            'save_attempted' => $shouldSave,
            'broadcasted' => $savedData !== [],
            'topic' => $topic,
            'timestamp' => $normalized['timestamp'],
            'dateTime' => $normalized['dateTime'],
        ];
    }

    protected function shouldPersistReadings(Node $node): bool
    {
        $cacheKey = 'last_save_time_node_' . $node->id;
        $lastSaveTime = (int) Cache::get($cacheKey, 0);
        $now = time();
        $frequency = max(1, (int) ($node->save_frequency ?? 30));

        if (($now - $lastSaveTime) < $frequency) {
            return false;
        }

        Cache::put($cacheKey, $now, $frequency * 2);

        return true;
    }
}
