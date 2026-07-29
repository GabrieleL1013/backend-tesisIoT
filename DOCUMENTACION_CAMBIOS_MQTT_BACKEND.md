# Documentacion De Cambios MQTT En Backend

## Objetivo

Adaptar el backend Laravel para:

1. Escuchar telemetria MQTT desde multiples brokers.
2. Procesar payloads reales provenientes de ChirpStack y de Raspberry Pi con Python.
3. Guardar historico en PostgreSQL.
4. Emitir eventos en tiempo real para el frontend.
5. Dejar trazabilidad clara en terminal para depuracion.

## Cambios Principales

### 1. Soporte para multiples conexiones MQTT

Archivo:

- `config/mqtt.php`

Se agrego soporte para `MQTT_CONNECTIONS`, permitiendo definir varias conexiones MQTT en una sola variable de entorno.

Cada conexion puede tener:

1. `name`
2. `host`
3. `port`
4. `username`
5. `password`
6. `qos`
7. `topics`

Tambien se mantiene compatibilidad con la configuracion simple de un solo broker.

### 2. Listener MQTT multi-broker

Archivo:

- `app/Console/Commands/SubscribeToMqttCommand.php`

Cambios:

1. El comando `php artisan mqtt:subscribe` ahora puede abrir varias conexiones.
2. Si un broker falla, ya no detiene por completo la escucha de los demas brokers.
3. Imprime trazas detalladas por cada mensaje recibido.

### 3. Normalizacion de payloads MQTT

Archivo:

- `app/Services/TelemetryPayloadNormalizer.php`

Soporta:

1. Payloads planos.
2. Payloads ChirpStack con `object`.
3. Payloads con `data` en JSON o base64.

Ademas:

1. Usa `Sensor` cuando viene de Raspberry/Python.
2. Usa preferentemente `deviceInfo.devEui` para nodos LoRa via ChirpStack.
3. Ignora metadatos y campos de estado no persistibles.
4. Parsea fechas desde:
   - `timestamp`
   - `ts`
   - `dateTime`
   - `dt`
   - `time`

### 4. Filtro de campos de estado

Para payloads de ChirpStack y payloads enriquecidos, se ignoran campos como:

1. `sensor_status`
2. `flags`
3. campos terminados en `_valid`
4. campos terminados en `_high`

Motivo:

No son metricas historicas principales y no deben contaminar la tabla `lecturas`.

### 5. Servicio de ingesta unificado

Archivo:

- `app/Services/TelemetryIngestionService.php`

Responsabilidades:

1. Normalizar payload.
2. Detectar nodo.
3. Validar subvariables asociadas.
4. Guardar lecturas segun `save_frequency`.
5. Emitir evento WebSocket.
6. Devolver informacion de depuracion.

### 6. Endpoint HTTP reutilizando la misma logica

Archivo:

- `app/Http/Controllers/Api/LecturaController.php`

El endpoint `store()` usa la misma logica central que el listener MQTT.

### 7. Logging detallado por terminal

Archivo:

- `app/Console/Commands/SubscribeToMqttCommand.php`

Ahora se imprime:

1. Broker y topic.
2. Payload recibido.
3. Nodo detectado.
4. Fecha y timestamp.
5. Claves recibidas.
6. Claves guardadas.
7. Claves ignoradas y motivo.
8. Confirmacion de evento WebSocket.

### 8. Seeder para nodo Heltec LoRa de agua

Archivo:

- `database/seeders/HeltecWaterNodeSeeder.php`

Este seeder crea:

1. Nodo:
   - `serial_number = 8aebbb6d6256a64f`
   - `nombre = Heltec V2`
   - `categoria = Agua`
2. Subvariables:
   - `ph`
   - `dissolved_oxygen_mg_l`
   - `ph_voltage_mv`
   - `dissolved_oxygen_voltage_mv`
   - `turbidity_adc_raw`
   - `turbidity_sensor_voltage_mv`

### 9. Seeder para roles y superusuario

Archivo:

- `database/seeders/RolesAndSuperuserSeeder.php`

Este seeder crea:

1. Roles base:
   - `Superusuario`
   - `Administrador`
   - `Tecnico`
   - `Consulta`
2. Usuario administrador:
   - correo `admin@uleam.edu.ec`
   - clave `uleamiot2026`

Tambien se actualizo `AdminUserSeeder` para usar `role_id` en vez del campo legado `rol`.

### 10. Base de datos migrada a PostgreSQL local

Configuracion usada:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tesisiot
DB_USERNAME=Rion
DB_PASSWORD=0220
```

Ademas se habilitaron en PHP:

1. `pdo_pgsql`
2. `pgsql`

## Brokers Y Payloads Trabajados

### Broker ChirpStack

1. Host: `172.29.101.43`
2. Topic: `application/+/device/+/event/up`
3. Tipo de payload: evento MQTT de ChirpStack

Ejemplo de metricas utiles extraidas del `object`:

1. `ph`
2. `dissolved_oxygen_mg_l`
3. `ph_voltage_mv`
4. `dissolved_oxygen_voltage_mv`
5. `turbidity_adc_raw`
6. `turbidity_sensor_voltage_mv`

### Broker institucional de sensores de agua

1. Host: `10.150.253.2`
2. Puerto: `1883`
3. Usuario: `mqtt-uleam`
4. Password: `Mqtt-Uleam2025$`
5. Topic: `iot_uleam/uleam`

Payload soportado:

```json
{
  "Sensor": "ULEAMCENTRAL02",
  "temperatura": null,
  "ph": 12.11,
  "oxigeno_disuelto": 4.2,
  "potenciometro_1": 1.38,
  "potenciometro_2": 1.67,
  "ec_us_cm": 0,
  "ec_temperature_c": 25.5,
  "salinity_ppm": 0,
  "tds_ppm": 0,
  "turbidity_ntu": 1000.0,
  "turbidity_temperature_c": 27.5,
  "timestamp": 1785344886,
  "dateTime": "29/07/2026 12:08:06"
}
```

## Variables De Entorno MQTT Recomendadas

Ejemplo para ambos brokers:

```env
MQTT_CONNECTIONS="[{\"name\":\"rak-chirpstack\",\"host\":\"172.29.101.43\",\"port\":1883,\"username\":null,\"password\":null,\"qos\":0,\"topics\":[\"application/+/device/+/event/up\"]},{\"name\":\"uleam-water\",\"host\":\"10.150.253.2\",\"port\":1883,\"username\":\"mqtt-uleam\",\"password\":\"Mqtt-Uleam2025$\",\"qos\":1,\"topics\":[\"iot_uleam/uleam\"]}]"
```

## Estado Actual

Quedo listo:

1. Backend en PostgreSQL local.
2. Roles y superusuario inicial.
3. Interfaces base del sistema.
4. Nodo LoRa Heltec de agua y sus subvariables.
5. Listener MQTT con trazas detalladas.
6. Soporte para multiples brokers.

## Credenciales Iniciales Del Dashboard

```text
Correo: admin@uleam.edu.ec
Clave: uleamiot2026
```
