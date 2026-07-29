# Documentacion De Cambios MQTT

## Objetivo

Adaptar el backend Laravel para recibir telemetria MQTT desde distintos origenes IoT, procesar payloads reales, guardar historico en base de datos y emitir eventos en tiempo real para el frontend.

## Problema Inicial

El sistema estaba limitado a un flujo MQTT simple:

1. Un solo broker/topic en `.env`.
2. Soporte incompleto para payloads reales de Raspberry Pi y ChirpStack.
3. Riesgo de interpretar metadatos como metricas.
4. Sin trazabilidad suficiente en consola para saber si los datos realmente se guardaban en la base de datos.

## Cambios Realizados

### 1. Soporte para multiples brokers MQTT

Archivo:

- `backend-tesisIoT/config/mqtt.php`

Cambios:

1. Se mantuvo compatibilidad con configuracion simple usando:
   - `MQTT_HOST`
   - `MQTT_PORT`
   - `MQTT_USERNAME`
   - `MQTT_PASSWORD`
   - `MQTT_TOPICS`
2. Se agrego soporte para multiples conexiones usando `MQTT_CONNECTIONS`.
3. Cada conexion puede definir:
   - `name`
   - `host`
   - `port`
   - `username`
   - `password`
   - `qos`
   - `topics`

Motivo:

Poder escuchar varios Raspberry o brokers al mismo tiempo sin cambiar el `.env` manualmente para cada caso.

### 2. Comando MQTT capaz de escuchar varios brokers

Archivo:

- `backend-tesisIoT/app/Console/Commands/SubscribeToMqttCommand.php`

Cambios:

1. El comando `php artisan mqtt:subscribe` ahora puede abrir varias conexiones MQTT.
2. Se conecta a cada broker definido en `MQTT_CONNECTIONS`.
3. Se suscribe a los topics configurados por conexion.
4. Mantiene un bucle de escucha para todas las conexiones activas.

Motivo:

Unificar la escucha de telemetria proveniente de:

1. Raspberry con ChirpStack.
2. Broker institucional con sensores de agua.
3. Futuros nodos MQTT adicionales.

### 3. Normalizacion robusta del payload

Archivo:

- `backend-tesisIoT/app/Services/TelemetryPayloadNormalizer.php`

Cambios:

1. Soporte para payload plano MQTT.
2. Soporte para payload tipo ChirpStack.
3. Soporte para JSON dentro de `data` y para `data` en base64.
4. Se reconoce `Sensor` como identificador del nodo.
5. Se soportan multiples fuentes de fecha/hora:
   - `timestamp`
   - `ts`
   - `dateTime`
   - `dt`
   - `time`
6. Se parsea correctamente `dateTime` en formato:
   - `d/m/Y H:i:s`
   - `Y-m-d H:i:s`
7. Se ignoran campos no persistibles como:
   - `Sensor`
   - `timestamp`
   - `dateTime`
   - `ts`
   - `dt`
   - `sensor_status`
   - metadatos de ChirpStack

Motivo:

Los payloads reales de Raspberry y ChirpStack no tienen exactamente la misma estructura y no deben guardarse campos auxiliares como si fueran metricas.

### 4. Servicio unificado de ingesta de telemetria

Archivo:

- `backend-tesisIoT/app/Services/TelemetryIngestionService.php`

Cambios:

1. Se centralizo la logica de ingesta.
2. El servicio:
   - normaliza el payload
   - detecta el nodo por `serial_number`
   - valida si el nodo existe
   - valida si la subvariable esta asociada al nodo
   - guarda la lectura si corresponde
   - emite evento WebSocket
3. Se respeta `save_frequency` para controlar persistencia.
4. Ahora devuelve informacion de depuracion:
   - claves recibidas
   - claves guardadas
   - claves ignoradas
   - razon de descarte
   - si se intento guardar
   - si se emitio evento

Motivo:

Evitar logica duplicada entre el endpoint HTTP y el listener MQTT, y ademas obtener trazabilidad clara del procesamiento.

### 5. Endpoint HTTP reutilizando la misma logica

Archivo:

- `backend-tesisIoT/app/Http/Controllers/Api/LecturaController.php`

Cambios:

1. El metodo `store()` ahora delega el procesamiento en `TelemetryIngestionService`.

Motivo:

Mantener consistencia entre telemetria enviada por HTTP y por MQTT.

### 6. Logger detallado por terminal

Archivo:

- `backend-tesisIoT/app/Console/Commands/SubscribeToMqttCommand.php`

Cambios:

Ahora el comando imprime:

1. Broker y topic de origen.
2. Payload completo recibido.
3. Nodo detectado.
4. Fecha y timestamp del payload.
5. Claves recibidas.
6. Claves guardadas en base de datos.
7. Claves ignoradas y motivo.
8. Si la persistencia fue bloqueada por `save_frequency`.
9. Si el evento WebSocket fue emitido correctamente.

Motivo:

Poder verificar desde terminal si:

1. El broker esta enviando datos.
2. El backend entiende el payload.
3. El nodo existe en BD.
4. Las subvariables estan mapeadas.
5. La lectura se guardo realmente.

### 7. Ajuste del `.env.example`

Archivo:

- `backend-tesisIoT/.env.example`

Cambios:

1. Se agrego ejemplo para el broker institucional:
   - host `10.150.253.2`
   - puerto `1883`
   - usuario `mqtt-uleam`
   - password `Mqtt-Uleam2025$`
   - topic `iot_uleam/uleam`
2. Se documento el uso de `MQTT_CONNECTIONS`.

Motivo:

Facilitar despliegue y pruebas en otros entornos.

### 8. Tests del normalizador

Archivo:

- `backend-tesisIoT/tests/Unit/TelemetryPayloadNormalizerTest.php`

Cambios:

Se agregaron pruebas para:

1. Payload plano simple.
2. Payload tipo ChirpStack.
3. Payload real del sistema de sensores de agua `ULEAMCENTRAL02`.

Motivo:

Validar que:

1. `sensor_status` no se trate como metricas.
2. `dateTime` se interprete correctamente.
3. Metricas similares como temperaturas distintas no se mezclen.

## Payloads Trabajados

### Raspberry con ChirpStack

Topic:

- `application/+/device/+/event/up`

Origen:

- Raspberry con RAK2287 y ChirpStack.

Tipo de payload:

1. Eventos MQTT de ChirpStack.
2. Posible `object` o `data` base64.

### Raspberry con sensores de agua

Topic:

- `iot_uleam/uleam`

Broker:

- `10.150.253.2:1883`

Credenciales:

- Usuario: `mqtt-uleam`
- Password: `Mqtt-Uleam2025$`

Payload real soportado:

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
  "dateTime": "29/07/2026 12:08:06",
  "sensor_status": {
    "board": true,
    "temperature": false,
    "ph": true,
    "dissolved_oxygen": true,
    "potentiometer_1": true,
    "potentiometer_2": true,
    "ec_sensor": true,
    "turbidity_sensor": true
  }
}
```

## Archivo Python Revisado

Archivo:

- `iot_sensor_system.py`

Resultado:

No fue necesario modificarlo.

Motivo:

El script ya publica un JSON valido y suficientemente claro para el backend actual.

## Rol De La Base De Datos

La base de datos cumple estas funciones:

1. Registrar los nodos existentes.
2. Registrar las subvariables permitidas por cada nodo.
3. Guardar el historico de lecturas.

Tablas implicadas:

1. `nodes`
   - identifica el dispositivo por `serial_number`
2. `subvariable_templates`
   - define las metricas disponibles
3. `node_subvariable_template`
   - relaciona nodos con subvariables
4. `lecturas`
   - almacena cada lectura historica

Si una clave llega por MQTT pero no existe asociada al nodo, se ignora y no se persiste.

## Requisitos De Base De Datos Para ULEAMCENTRAL02

Debe existir un nodo con:

- `serial_number = ULEAMCENTRAL02`

Y deben existir subvariables asociadas con estas `clave_mqtt` exactas:

1. `temperatura`
2. `ph`
3. `oxigeno_disuelto`
4. `potenciometro_1`
5. `potenciometro_2`
6. `ec_us_cm`
7. `ec_temperature_c`
8. `salinity_ppm`
9. `tds_ppm`
10. `turbidity_ntu`
11. `turbidity_temperature_c`

## Configuracion Recomendada En `.env`

Para escuchar solo el broker del sistema de agua:

```env
MQTT_CONNECTIONS="[{\"name\":\"uleam-water\",\"host\":\"10.150.253.2\",\"port\":1883,\"username\":\"mqtt-uleam\",\"password\":\"Mqtt-Uleam2025$\",\"qos\":1,\"topics\":[\"iot_uleam/uleam\"]}]"
```

Para escuchar varios brokers a la vez:

```env
MQTT_CONNECTIONS="[{\"name\":\"rak-chirpstack\",\"host\":\"172.29.101.43\",\"port\":1883,\"username\":null,\"password\":null,\"qos\":0,\"topics\":[\"application/+/device/+/event/up\"]},{\"name\":\"uleam-water\",\"host\":\"10.150.253.2\",\"port\":1883,\"username\":\"mqtt-uleam\",\"password\":\"Mqtt-Uleam2025$\",\"qos\":1,\"topics\":[\"iot_uleam/uleam\"]}]"
```

Nota:

Si uno de los brokers no esta disponible, la conexion correspondiente puede fallar al iniciar. En pruebas se confirmo que el broker del sistema de agua si responde y el de ChirpStack fallaba cuando ese Raspberry estaba apagado.

## Estado Actual

Actualmente queda funcionando:

1. Conexion al broker `10.150.253.2`.
2. Suscripcion a `iot_uleam/uleam`.
3. Recepcion del payload real del sistema de agua.
4. Normalizacion correcta de metricas y timestamps.
5. Logger detallado por terminal.
6. Base preparada para persistir historico si el nodo y subvariables estan mapeados.

## Resumen Ejecutivo

Se extendio el backend para soportar multiples brokers MQTT, normalizar payloads heterogeneos, ignorar metadatos no persistibles, registrar lecturas historicas en base de datos y mostrar trazas detalladas en consola para verificar si la telemetria se recibe, se interpreta y se guarda correctamente.
