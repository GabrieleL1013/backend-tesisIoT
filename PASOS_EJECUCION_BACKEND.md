# Pasos De Ejecucion Del Backend

## Requisitos

1. PHP disponible en PATH.
2. Composer instalado.
3. PostgreSQL corriendo localmente.
4. Base de datos configurada en `.env`.

## Configuracion Actual De Base De Datos

El backend esta configurado para usar PostgreSQL local:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tesisiot
DB_USERNAME=Rion
DB_PASSWORD=0220
```

## Configuracion MQTT Actual

Ejemplo para escuchar ambos brokers:

```env
MQTT_CONNECTIONS="[{\"name\":\"rak-chirpstack\",\"host\":\"172.29.101.43\",\"port\":1883,\"username\":null,\"password\":null,\"qos\":0,\"topics\":[\"application/+/device/+/event/up\"]},{\"name\":\"uleam-water\",\"host\":\"10.150.253.2\",\"port\":1883,\"username\":\"mqtt-uleam\",\"password\":\"Mqtt-Uleam2025$\",\"qos\":1,\"topics\":[\"iot_uleam/uleam\"]}]"
```

## Paso A Paso

### 1. Instalar dependencias PHP

```bash
composer install
```

### 2. Limpiar configuracion en cache

```bash
php artisan config:clear
```

### 3. Ejecutar migraciones

```bash
php artisan migrate
```

### 4. Crear usuario admin, interfaces y nodos base

Seeder completo:

```bash
php artisan db:seed
```

Si quieres ejecutar seeders puntuales:

```bash
php artisan db:seed --class=RolesAndSuperuserSeeder
php artisan db:seed --class=AppInterfacesSeeder
php artisan db:seed --class=HeltecWaterNodeSeeder
```

### 5. Iniciar servidor Laravel

```bash
php artisan serve
```

Por defecto queda en:

```text
http://127.0.0.1:8000
```

### 6. Iniciar servidor WebSocket Reverb

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

### 7. Iniciar listener MQTT

```bash
php artisan mqtt:subscribe
```

### 8. O iniciar todo junto si tu entorno lo soporta

```bash
composer dev:iot
```

## Credenciales Del Dashboard

```text
Correo: admin@uleam.edu.ec
Clave: uleamiot2026
```

## Comandos Utiles De Verificacion

### Verificar conexion de BD

```bash
php artisan migrate:status
```

### Verificar que el listener MQTT este recibiendo datos

```bash
php artisan mqtt:subscribe
```

Debes ver mensajes como:

1. broker conectado
2. topic suscrito
3. payload recibido
4. nodo procesado
5. claves guardadas o ignoradas

### Verificar rutas API

Ejemplo:

```text
GET http://127.0.0.1:8000/api/nodos
GET http://127.0.0.1:8000/api/roles
POST http://127.0.0.1:8000/api/login
```

## Problemas Frecuentes

### 1. No conecta a PostgreSQL

Revisar:

1. servicio PostgreSQL encendido
2. credenciales en `.env`
3. `php -m` debe mostrar `pdo_pgsql` y `pgsql`

### 2. No se reciben datos MQTT

Revisar:

1. brokers activos
2. topics correctos
3. conectividad de red
4. credenciales MQTT

### 3. Se reciben datos pero no se guardan en BD

Revisar en el log del listener:

1. nodo detectado
2. claves recibidas
3. claves ignoradas
4. si la razon es `subvariable_not_mapped`
5. si la razon es `save_frequency`

### 4. El frontend no muestra nodos

Revisar:

1. que `GET /api/nodos` responda correctamente
2. que existan nodos en la BD
3. que el frontend este apuntando al backend correcto
