import os
import time
import json
import threading
import requests
import random
import socket
from paho.mqtt.client import Client as MQTTClient
from paho.mqtt.properties import Properties
from paho.mqtt.packettypes import PacketTypes

try:
    from paho.mqtt.enums import CallbackAPIVersion
    HAS_PAHO_V2 = True
except ImportError:
    HAS_PAHO_V2 = False
    CallbackAPIVersion = None

try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    pass

# IP y puerto centralizados para la comunicación con la API
API_HOST = os.getenv("API_HOST", "127.0.0.1")
API_PORT = os.getenv("API_PORT", "8000")

API_BASE_URL = f"http://{API_HOST}:{API_PORT}/api"
API_URL_NODOS = f"{API_BASE_URL}/nodos"
API_URL_LECTURAS = f"{API_BASE_URL}/lecturas"
DEFAULT_REGION = "us915_0"

MQTT_RC_MESSAGES = {
    0: "Conexión exitosa",
    1: "Conexión rechazada: Versión de protocolo MQTT incorrecta o no soportada",
    2: "Conexión rechazada: Identificador de cliente (client_id) no válido",
    3: "Conexión rechazada: Servidor broker no disponible o fuera de servicio",
    4: "Conexión rechazada: Usuario o contraseña incorrectos",
    5: "Conexión rechazada: No autorizado para acceder al broker",
    128: "Error no especificado (MQTT v5)",
    134: "Usuario o contraseña inválidos (MQTT v5)",
    135: "No autorizado (MQTT v5)",
    142: "Sesión no válida (MQTT v5)",
}

def get_rc_description(rc):
    if isinstance(rc, int):
        return MQTT_RC_MESSAGES.get(rc, f"Código de retorno/razón no estándar (rc={rc})")
    return str(rc)


def normalizar_payload(payload_bruto, serial_esperado):
    """
    Analiza un JSON entrante y lo estandariza al formato que espera Laravel.
    Retorna un diccionario limpio o None si el formato es totalmente desconocido.
    """
    payload_limpio = {}

    # 1. Detectar formato ChirpStack v4 (El hardware físico de la universidad)
    if "deviceInfo" in payload_bruto and "object" in payload_bruto:
        dev_eui = payload_bruto["deviceInfo"].get("devEui")
        
        # Ignorar mayúsculas/minúsculas al comparar el serial
        if dev_eui and str(dev_eui).lower() != str(serial_esperado).lower():
            return None # Es de otro sensor
            
        payload_limpio["Sensor"] = serial_esperado
        
        # Extraer los datos físicos reales (pH, oxígeno, turbidez)
        datos = payload_bruto.get("object", {})
            
        # Fusionamos los datos limpios
        payload_limpio.update(datos)
        return payload_limpio

    # 2. Detectar formato estricto (El de tu simulador de Zorin/Windows)
    elif "Sensor" in payload_bruto:
        if str(payload_bruto["Sensor"]).lower() != str(serial_esperado).lower():
            return None
        return payload_bruto

    # 3. Detectar formato genérico o descuidado
    elif "temperatura" in payload_bruto or "humedad" in payload_bruto:
        payload_limpio["Sensor"] = serial_esperado
        payload_limpio.update(payload_bruto)
        return payload_limpio

    # Si no encaja en nada conocido
    return None


class NodeListener(threading.Thread):
    def __init__(self, node_data):
        super().__init__()
        self.node_data = node_data
        self.is_running = True
        self.client = None
        # Prefix client_id to avoid collision with simulators
        self.client_id = f"listener-{node_data.get('serial_number')}-{random.randint(100,999)}" if node_data.get('serial_number') else f"listener-{random.randint(1000,9999)}"
        self.broker = node_data.get('broker') or 'broker.hivemq.com'
        self.port = int(node_data.get('port') or 1883)
        self.topic_data = node_data.get('topic_data')
        self.use_v5 = bool(node_data.get('use_mqtt_v5', False))
        self.username = node_data.get('username')
        self.password = node_data.get('password')
        self.region = node_data.get('region') or DEFAULT_REGION
        
    def on_connect(self, client, userdata, flags, reason_code, properties=None):
        node_name = self.node_data.get('nombre', 'Desconocido')
        if reason_code == 0:
            print(f"✅ [{node_name}] LISTENER Conectado exitosamente a {self.broker}:{self.port}")
            if self.topic_data:
                self.client.subscribe(self.topic_data)
                print(f"👂 [{node_name}] Suscrito a topic: '{self.topic_data}'")
            else:
                print(f"⚠️ [{node_name}] Conectado pero no tiene 'topic_data' configurado para suscribirse.")
        else:
            reason_desc = get_rc_description(reason_code)
            print(
                f"❌ [{node_name}] LISTENER Fallo de conexión MQTT con {self.broker}:{self.port}\n"
                f"   ➔ Código rc: {reason_code}\n"
                f"   ➔ Diagnóstico: {reason_desc}\n"
                f"   ➔ Configuración: MQTT v{5 if self.use_v5 else 4}, Usuario: '{self.username or 'Sin usuario'}'"
            )

    def on_disconnect(self, client, userdata, disconnect_flags, reason_code, properties=None):
        node_name = self.node_data.get('nombre', 'Desconocido')
        if reason_code == 0:
            print(f"ℹ️ [{node_name}] LISTENER Desconectado limpiamente del broker ({self.broker}).")
        else:
            reason_desc = get_rc_description(reason_code)
            print(
                f"⚠️ [{node_name}] LISTENER Desconexión imprevista del broker {self.broker}:{self.port}\n"
                f"   ➔ Código rc: {reason_code} ({reason_desc})"
            )

    def on_message(self, client, userdata, msg):
        try:
            payload_bruto = json.loads(msg.payload.decode('utf-8'))
            serial_number = self.node_data.get('serial_number')

            # Pasamos el JSON por el normalizador
            payload_normalizado = normalizar_payload(payload_bruto, serial_number)

            if not payload_normalizado:
                print(
                    f"⚠️ [{self.node_data['nombre']}] "
                    f"Mensaje ignorado o no reconocido en {msg.topic}: {payload_bruto}"
                )
                return

            if not payload_normalizado.get('region'):
                payload_normalizado['region'] = self.region

            print(
                f"📥 [{self.node_data['nombre']}] "
                f"Recibido y Traducido: {payload_normalizado}"
            )

            headers = {'Content-Type': 'application/json'}

            res = requests.post(
                API_URL_LECTURAS,
                json=payload_normalizado,
                headers=headers,
                timeout=5
            )

            if res.status_code in [200, 201]:
                print("   ✅ Datos guardados y emitidos vía WebSocket.")
            else:
                print(
                    f"   ❌ Error API Backend: "
                    f"{res.status_code} - {res.text}"
                )

        except json.JSONDecodeError as e:
            print(f"   ❌ Payload MQTT no es JSON válido: {e}")

        except requests.RequestException as e:
            print(f"   ❌ Error comunicando con la API: {e}")

        except Exception as e:
            print(f"   ❌ Error procesando mensaje MQTT: {e}")

        
    def run(self):
        node_name = self.node_data.get('nombre', 'Desconocido')
        print(f"🔄 [{node_name}] Intentando conectar a broker MQTT {self.broker}:{self.port}...")
        try:
            protocol = 5 if self.use_v5 else 4
            
            if HAS_PAHO_V2 and CallbackAPIVersion:
                self.client = MQTTClient(CallbackAPIVersion.VERSION2, client_id=self.client_id, protocol=protocol)
            else:
                self.client = MQTTClient(client_id=self.client_id, protocol=protocol)
            
            if self.username and self.password:
                self.client.username_pw_set(self.username, self.password)
                
            self.client.on_connect = self.on_connect
            self.client.on_disconnect = self.on_disconnect
            self.client.on_message = self.on_message
            
            if self.use_v5:
                properties = Properties(PacketTypes.CONNECT)
                properties.SessionExpiryInterval = 3600
                self.client.connect(self.broker, self.port, keepalive=60, properties=properties)
            else:
                self.client.connect(self.broker, self.port, keepalive=60)
                
            self.client.loop_start()
            while self.is_running:
                time.sleep(1)
        except socket.gaierror as e:
            print(
                f"❌ [{node_name}] Error de resolución DNS / Dominio invalido:\n"
                f"   ➔ Servidor broker '{self.broker}' no pudo ser localizado.\n"
                f"   ➔ Detalle del sistema: {e}"
            )
        except ConnectionRefusedError as e:
            print(
                f"❌ [{node_name}] Conexión rechazada en socket:\n"
                f"   ➔ El broker en '{self.broker}:{self.port}' rechazó la conexión (¿Broker no iniciado o puerto incorrecto?).\n"
                f"   ➔ Detalle del sistema: {e}"
            )
        except TimeoutError as e:
            print(
                f"❌ [{node_name}] Tiempo de espera de red agotado (Timeout):\n"
                f"   ➔ El broker '{self.broker}:{self.port}' no respondió a tiempo (¿Red inalcanzable o puerto bloqueado?).\n"
                f"   ➔ Detalle del sistema: {e}"
            )
        except Exception as e:
            print(
                f"❌ [{node_name}] Error al iniciar conexión listener:\n"
                f"   ➔ Tipo de excepción: {type(e).__name__}\n"
                f"   ➔ Detalle: {e}"
            )

    def stop(self):
        self.is_running = False
        if self.client:
            self.client.loop_stop()
            self.client.disconnect()
        print(f"⏹️ [{self.node_data.get('nombre', 'Desconocido')}] Listener detenido.")


class MqttListenerManager:
    def __init__(self):
        self.active_listeners = {}

    def fetch_active_nodes(self):
        try:
            res = requests.get(API_URL_NODOS, timeout=10)
            if res.status_code == 200:
                nodes = res.json()
                # Escuchar TODOS los nodos activos (físicos y simulados)
                return [n for n in nodes if n.get('estado') == True]
            else:
                print(f"⚠️ Error API Backend ({API_URL_NODOS}): HTTP {res.status_code} - {res.text[:200]}")
        except requests.exceptions.ConnectionError as e:
            print(f"⚠️ No se pudo conectar al API de Laravel ({API_URL_NODOS}): Servidor inalcanzable ({e})")
        except requests.exceptions.Timeout:
            print(f"⚠️ Tiempo de espera agotado al consultar API de Laravel ({API_URL_NODOS})")
        except Exception as e:
            print(f"⚠️ Error inesperado consultando API de Laravel ({API_URL_NODOS}): {e}")
        return []

    def sync_listeners(self):
        print("🔄 Sincronizando oyentes MQTT desde la BD...")
        nodes = self.fetch_active_nodes()
        
        # Reportar nodos activos sin topic_data configurado
        for n in nodes:
            if not n.get('topic_data'):
                print(f"⚠️ [Nodo: {n.get('nombre', 'ID ' + str(n.get('id')))}] Está ACTIVO pero NO tiene 'topic_data' configurado.")

        current_node_ids = {str(n['id']): n for n in nodes if n.get('topic_data')}
        
        # 1. Iniciar nuevos listeners o ACTUALIZAR los existentes
        for node_id, node_data in current_node_ids.items():
            if node_id not in self.active_listeners:
                # Nodo completamente nuevo
                print(f"➕ Iniciando listener para nuevo nodo: {node_data['nombre']}")
                listener = NodeListener(node_data)
                self.active_listeners[node_id] = listener
                listener.start()
            else:
                # El nodo ya existe, comprobamos si algo cambió desde React/Laravel
                existing_listener = self.active_listeners[node_id]
                
                # Comparamos la configuración guardada con la recién descargada
                if existing_listener.node_data != node_data:
                    print(f"✏️ Cambios detectados en el nodo: {node_data['nombre']}. Reiniciando su conexión...")
                    
                    # Detenemos SOLO la conexión de este nodo
                    existing_listener.stop()
                    
                    # Creamos una nueva conexión con la data fresca
                    new_listener = NodeListener(node_data)
                    self.active_listeners[node_id] = new_listener
                    new_listener.start()
                
        # 2. Detener listeners de nodos eliminados o desactivados
        ids_to_remove = []
        for node_id, listener in self.active_listeners.items():
            if node_id not in current_node_ids:
                print(f"➖ Deteniendo listener de nodo eliminado/desactivado: {listener.node_data['nombre']}")
                listener.stop()
                ids_to_remove.append(node_id)
                
        for i in ids_to_remove:
            del self.active_listeners[i]

    def run(self):
        print("🚀 INICIANDO MQTT LISTENER MANAGER...")
        try:
            while True:
                self.sync_listeners()
                # Consultar DB cada 30 segundos
                time.sleep(30) 
        except KeyboardInterrupt:
            print("\n⛔ Apagando todos los listeners...")
            for l in self.active_listeners.values():
                l.stop()
            print("👋 Adiós.")


if __name__ == '__main__':
    manager = MqttListenerManager()
    manager.run()