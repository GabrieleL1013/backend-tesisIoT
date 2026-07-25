<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\Node;
use App\Models\SubvariableTemplate;
use App\Models\Lectura;
use App\Events\LecturaRecibida;
use Exception;

class SubscribeToMqttCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to MQTT broker and save telemetry to DB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ----- MODO PRUEBA PÚBLICA (HiveMQ) -----
        $server   = 'broker.hivemq.com';
        $port     = 1883;
        $clientId = 'laravel-subscriber-test-' . rand(1000, 9999);
        $clean_session = true;

        $connectionSettings = (new ConnectionSettings)
            ->setKeepAliveInterval(60);

        // -- CUANDO VAYAS A LA ULEAM Y ESTÉS EN SU RED WIFI, DESCOMENTA ESTO Y BORRA LO DE ARRIBA --
        /*
        $server   = '10.150.253.2';
        $port     = 1883;
        $clientId = 'laravel-subscriber-' . rand(1000, 9999);
        $username = 'mqtt-uleam';
        $password = 'Mqtt-Uleam2025$';
        $clean_session = true;

        $connectionSettings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setKeepAliveInterval(60);
        */

        try {
            $mqtt = new MqttClient($server, $port, $clientId, MqttClient::MQTT_3_1_1);
            $mqtt->connect($connectionSettings, $clean_session);
            
            $this->info("Conectado exitosamente al broker MQTT: {$server}:{$port}");
            
            $mqtt->subscribe('iot_uleam_test_tesis/#', function ($topic, $message) {
                $this->info("📦 Mensaje recibido en [$topic]: $message");
                $this->processMessage($message);
            }, 0);

            $mqtt->loop(true);
            $mqtt->disconnect();
        } catch (Exception $e) {
            $this->error("Error MQTT: " . $e->getMessage());
        }
    }

    protected function processMessage($message)
    {
        $payload = json_decode($message, true);
        if (!$payload || !isset($payload['Sensor'])) {
            return;
        }

        $node = Node::where('serial_number', $payload['Sensor'])->first();
        if (!$node) {
            $this->warn("⚠️ Nodo no encontrado en BD para el serial: " . $payload['Sensor'] . ". Emitiendo sólo a WebSockets para prueba.");
            $node = new Node(['serial_number' => $payload['Sensor']]);
        }

        $savedData = [];
        
        foreach ($payload as $key => $value) {
            if (in_array($key, ['Sensor', 'timestamp', 'dateTime'])) {
                continue;
            }

            $subvariable = SubvariableTemplate::where('clave_mqtt', $key)->first();
            
            if ($subvariable && $node->exists) {
                Lectura::create([
                    'node_id' => $node->id,
                    'subvariable_id' => $subvariable->id,
                    'valor' => $value
                ]);
            }
            $savedData[$key] = $value;
        }
        
        if (!empty($savedData)) {
            $savedData['timestamp'] = $payload['timestamp'] ?? time();
            $savedData['dateTime'] = $payload['dateTime'] ?? now()->toDateTimeString();
            
            // Broadcast event to Frontend
            event(new LecturaRecibida($node, $savedData));
            $this->info("✅ Evento emitido para el nodo: " . $node->serial_number);
        }
    }
}
