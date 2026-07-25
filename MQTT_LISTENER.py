import time
import json
import threading
import requests
import random
import socket
from paho.mqtt.client import Client as MQTTClient
from paho.mqtt.properties import Properties
from paho.mqtt.packettypes import PacketTypes

API_URL_NODOS = "http://127.0.0.1:8000/api/nodos"
API_URL_LECTURAS = "http://127.0.0.1:8000/api/lecturas"
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
        
    def on_connect(self, client, userdata, flags, rc, properties=None):
        node_name = self.node_data.get('nombre', 'Desconocido')
        if rc == 0:
            print(f"✅ [{node_name}] LISTENER Conectado exitosamente a {self.broker}:{self.port}")
            if self.topic_data:
                self.client.subscribe(self.topic_data)
                print(f"👂 [{node_name}] Suscrito a topic: '{self.topic_data}'")
            else:
                print(f"⚠️ [{node_name}] Conectado pero no tiene 'topic_data' configurado para suscribirse.")
        else:
            reason_desc = get_rc_description(rc)
            print(
                f"❌ [{node_name}] LISTENER Fallo de conexión MQTT con {self.broker}:{self.port}\n"
                f"   ➔ Código rc: {rc}\n"
                f"   ➔ Diagnóstico: {reason_desc}\n"
                f"   ➔ Configuración: MQTT v{5 if self.use_v5 else 4}, Usuario: '{self.username or 'Sin usuario'}'"
            )

    def on_disconnect(self, client, userdata, rc, properties=None):
        node_name = self.node_data.get('nombre', 'Desconocido')
        if rc == 0:
            print(f"ℹ️ [{node_name}] LISTENER Desconectado limpiamente del broker ({self.broker}).")
        else:
            reason_desc = get_rc_description(rc)
            print(
                f"⚠️ [{node_name}] LISTENER Desconexión imprevista del broker {self.broker}:{self.port}\n"
                f"   ➔ Código rc: {rc} ({reason_desc})"
            )

    def on_message(self, client, userdata, msg):
        try:
            payload = json.loads(msg.payload.decode('utf-8'))

            if not payload.get('Sensor'):
                print(
                    f"⚠️ [{self.node_data['nombre']}] "
                    f"Mensaje descartado en {msg.topic}: falta Sensor"
                )
                return

            if not payload.get('region'):
                payload['region'] = self.region

            if str(payload['Sensor']) != str(
                self.node_data.get('serial_number')
            ):
                return

            print(
                f"📥 [{self.node_data['nombre']}] "
                f"Recibido en {msg.topic}: {payload}"
            )

            headers = {'Content-Type': 'application/json'}

            res = requests.post(
                API_URL_LECTURAS,
                json=payload,
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
        
        # 1. Iniciar nuevos listeners
        for node_id, node_data in current_node_ids.items():
            if node_id not in self.active_listeners:
                print(f"➕ Iniciando listener para: {node_data['nombre']}")
                listener = NodeListener(node_data)
                self.active_listeners[node_id] = listener
                listener.start()
                
        # 2. Detener listeners de nodos eliminados/inactivos
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
