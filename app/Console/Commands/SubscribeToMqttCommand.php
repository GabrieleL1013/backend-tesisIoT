<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Services\TelemetryIngestionService;
use Exception;

class SubscribeToMqttCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:subscribe {--topic=* : Topics MQTT a suscribir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to MQTT broker and save telemetry to DB';

    /**
     * Execute the console command.
     */
    public function handle(TelemetryIngestionService $telemetryIngestionService)
    {
        $requestedTopics = $this->option('topic');
        $requestedTopics = is_array($requestedTopics) && $requestedTopics !== [] ? $requestedTopics : null;
        $cleanSession = true;
        $connections = config('mqtt.connections', []);

        if ($connections === []) {
            $this->warn('No hay conexiones MQTT configuradas.');
            return self::FAILURE;
        }

        $clients = [];

        try {
            foreach ($connections as $connection) {
                $server = $connection['host'];
                $port = (int) $connection['port'];
                $clientId = $connection['client_id'] . '-' . rand(1000, 9999);
                $topics = $requestedTopics ?? ($connection['topics'] ?? []);

                if (empty($topics)) {
                    $this->warn('Conexion MQTT sin topics: ' . ($connection['name'] ?? $server));
                    continue;
                }

                $connectionSettings = (new ConnectionSettings)
                    ->setKeepAliveInterval((int) ($connection['keep_alive'] ?? 60));

                if (!empty($connection['username'])) {
                    $connectionSettings = $connectionSettings->setUsername((string) $connection['username']);
                }

                if (!empty($connection['password'])) {
                    $connectionSettings = $connectionSettings->setPassword((string) $connection['password']);
                }

                $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1_1);
                $mqtt->connect($connectionSettings, $cleanSession);
                $this->info('Conectado exitosamente al broker MQTT [' . ($connection['name'] ?? $server) . "]: {$server}:{$port}");

                foreach ($topics as $topic) {
                    $mqtt->subscribe($topic, function ($topic, $message) use ($telemetryIngestionService, $connection) {
                        $this->info('Mensaje recibido [' . ($connection['name'] ?? 'mqtt') . "] en [$topic]");
                        $this->processMessage($telemetryIngestionService, $topic, $message);
                    }, (int) ($connection['qos'] ?? 0));
                    $this->line('Suscrito [' . ($connection['name'] ?? $server) . "]: {$topic}");
                }

                $clients[] = $mqtt;
            }

            if ($clients === []) {
                $this->warn('No se pudo inicializar ninguna suscripcion MQTT.');
                return self::FAILURE;
            }

            while (true) {
                $loopStartedAt = microtime(true);

                foreach ($clients as $mqtt) {
                    $mqtt->loopOnce($loopStartedAt, true);
                }
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Error MQTT: " . $e->getMessage());

            foreach ($clients as $mqtt) {
                try {
                    $mqtt->disconnect();
                } catch (Exception) {
                }
            }

            return self::FAILURE;
        }
    }

    protected function processMessage(TelemetryIngestionService $telemetryIngestionService, string $topic, string $message): void
    {
        $payload = json_decode($message, true);

        if (!is_array($payload)) {
            $this->warn('Payload MQTT ignorado: no es JSON válido.');
            return;
        }

        $this->line('Payload: ' . $message);

        $result = $telemetryIngestionService->ingestFromPayload($payload, $topic);

        if ($result['status'] !== 'processed') {
            $this->warn($result['reason']);
            return;
        }

        if (!$result['node_exists']) {
            $this->warn('Nodo no registrado en BD: ' . $result['serial_number'] . '. Se emitió solo a WebSocket.');
        }

        $this->info('Procesado nodo ' . $result['serial_number'] . ' | métricas=' . $result['metrics_count'] . ' | guardadas=' . $result['saved_metrics_count']);
        $this->line('Fecha payload: ' . $result['dateTime'] . ' | timestamp=' . $result['timestamp']);
        $this->line('Claves recibidas: ' . $this->formatList($result['received_metric_keys']));
        $this->line('Claves guardadas: ' . $this->formatList($result['saved_metric_keys']));

        if (!empty($result['ignored_metric_keys'])) {
            foreach ($result['ignored_metric_keys'] as $key => $reason) {
                $this->warn("Ignorada [$key]: $reason");
            }
        }

        if (!$result['save_attempted']) {
            $this->warn('No se guardo en BD en este ciclo: bloqueado por save_frequency del nodo.');
        }

        if ($result['broadcasted']) {
            $this->info('Evento WebSocket emitido correctamente.');
        }
    }

    protected function formatList(array $values): string
    {
        return empty($values) ? '(ninguna)' : implode(', ', $values);
    }
}
