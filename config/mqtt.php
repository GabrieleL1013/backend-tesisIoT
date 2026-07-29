<?php

$defaultTopics = array_values(array_filter(array_map(
    static fn (string $topic) => trim($topic),
    explode(',', (string) env('MQTT_TOPICS', 'application/+/device/+/event/up'))
)));

$defaultConnection = [
    'name' => 'default',
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'client_id' => env('MQTT_CLIENT_ID', 'laravel-mqtt-subscriber'),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'keep_alive' => (int) env('MQTT_KEEP_ALIVE', 60),
    'qos' => (int) env('MQTT_QOS', 0),
    'topics' => $defaultTopics,
];

$connections = json_decode((string) env('MQTT_CONNECTIONS', ''), true);

if (!is_array($connections) || $connections === []) {
    $connections = [$defaultConnection];
} else {
    $connections = array_map(static function (array $connection, int $index) use ($defaultConnection) {
        $topics = $connection['topics'] ?? $defaultConnection['topics'];

        if (is_string($topics)) {
            $topics = array_values(array_filter(array_map('trim', explode(',', $topics))));
        }

        return [
            'name' => $connection['name'] ?? ('connection_' . ($index + 1)),
            'host' => $connection['host'] ?? $defaultConnection['host'],
            'port' => (int) ($connection['port'] ?? $defaultConnection['port']),
            'client_id' => $connection['client_id'] ?? $defaultConnection['client_id'],
            'username' => $connection['username'] ?? null,
            'password' => $connection['password'] ?? null,
            'keep_alive' => (int) ($connection['keep_alive'] ?? $defaultConnection['keep_alive']),
            'qos' => (int) ($connection['qos'] ?? $defaultConnection['qos']),
            'topics' => is_array($topics) ? array_values(array_filter($topics)) : $defaultConnection['topics'],
        ];
    }, $connections, array_keys($connections));
}

return [
    ...$defaultConnection,
    'connections' => $connections,
];
