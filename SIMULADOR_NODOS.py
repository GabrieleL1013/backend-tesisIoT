import os
import time
import json
import calendar
import random
import threading
import requests
from datetime import datetime
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

# Configuración del Backend de Laravel
API_HOST = os.getenv("API_HOST", "127.0.0.1")
API_PORT = os.getenv("API_PORT", "8000")
API_URL = f"http://{API_HOST}:{API_PORT}/api/nodos/internal"


class VirtualNode(threading.Thread):
    def __init__(self, node_data):
        super().__init__()
        self.node_data = node_data
        self.is_running = True
        self.client = None

        serial = str(node_data.get('serial_number') or f"node-{random.randint(100,999)}").strip()
        self.serial_number = serial
        self.client_id = f"sim-all-{serial}-{random.randint(100, 999)}"
        self.broker = str(node_data.get('broker') or 'broker.hivemq.com').strip()
        self.port = int(node_data.get('port') or 1883)

        loc_slug = node_data.get('location_slug') if (node_data.get('location_slug') and isinstance(node_data.get('location_slug'), str)) else serial
        self.topic_data = node_data.get('topic_data') or f"iot_uleam/{loc_slug}"

        self.use_v5 = bool(node_data.get('use_mqtt_v5', False))
        self.username = node_data.get('username')
        self.password = node_data.get('password')

    def on_connect(self, client, userdata, flags, rc, properties=None):
        nombre = self.node_data.get('nombre', 'Nodo')
        if rc == 0:
            print(f"✅ [{nombre} | {self.serial_number}] Conectado a broker {self.broker}:{self.port}")
        else:
            print(f"❌ [{nombre} | {self.serial_number}] Error de conexión MQTT (rc={rc})")

    def on_disconnect(self, client, userdata, rc, properties=None):
        nombre = self.node_data.get('nombre', 'Nodo')
        print(f"⚠️ [{nombre} | {self.serial_number}] Desconectado del broker")

    def connect_mqtt(self):
        brokers_to_try = [self.broker]
        if self.broker != 'broker.hivemq.com':
            brokers_to_try.append('broker.hivemq.com')

        for b in brokers_to_try:
            try:
                protocol = 5 if self.use_v5 else 4
                if HAS_PAHO_V2:
                    cb_ver = CallbackAPIVersion.VERSION2 if self.use_v5 else CallbackAPIVersion.VERSION1
                    self.client = MQTTClient(callback_api_version=cb_ver, client_id=self.client_id, protocol=protocol)
                else:
                    self.client = MQTTClient(client_id=self.client_id, protocol=protocol)

                if self.username and self.password and b == self.broker:
                    self.client.username_pw_set(self.username, self.password)

                self.client.on_connect = self.on_connect
                self.client.on_disconnect = self.on_disconnect

                port_to_use = self.port if b == self.broker else 1883

                if self.use_v5:
                    properties = Properties(PacketTypes.CONNECT)
                    properties.SessionExpiryInterval = 3600
                    self.client.connect(b, port_to_use, keepalive=60, properties=properties)
                else:
                    self.client.connect(b, port_to_use, keepalive=60)

                self.client.loop_start()
                if b != self.broker:
                    nombre = self.node_data.get('nombre', 'Nodo')
                    print(f"🔄 [{nombre} | {self.serial_number}] Broker configurado ({self.broker}) no disponible. Usando broker público de respaldo: broker.hivemq.com:1883")
                return True
            except Exception as e:
                nombre = self.node_data.get('nombre', 'Nodo')
                print(f"⚠️ [{nombre} | {self.serial_number}] Error conectando a broker {b}: {e}")
                if self.client:
                    try:
                        self.client.loop_stop()
                    except Exception:
                        pass

        return False

    def build_payload(self):
        now = datetime.now()
        date_time = now.strftime("%Y-%m-%d %H:%M:%S")
        timestamp = calendar.timegm(time.gmtime())

        payload = {
            "Sensor": self.serial_number,
            "timestamp": timestamp,
            "dateTime": date_time
        }

        # Generar valores aleatorios para las lecturas/variables configuradas del nodo
        lecturas = self.node_data.get('lecturas', [])
        for l in lecturas:
            key = l.get('data_type')
            tipo = str(l.get('tipo') or '').lower()

            if not key:
                continue

            min_exp = l.get('minExpected')
            max_exp = l.get('maxExpected')

            if min_exp is not None and max_exp is not None:
                try:
                    min_val = float(min_exp)
                    max_val = float(max_exp)
                except ValueError:
                    min_val, max_val = 10.0, 100.0

                if min_val > max_val:
                    min_val, max_val = max_val, min_val

                R = max_val - min_val if max_val > min_val else (max_val * 0.1 if max_val != 0 else 10.0)
                p = random.random()

                if p > 0.10:  # 90% dentro del rango normal
                    val = random.uniform(min_val, max_val)
                elif p > 0.03:  # 7% pequeñas desviaciones (1-5%)
                    dev = random.uniform(0.01, 0.05) * R
                    val = max_val + dev if random.choice([True, False]) else min_val - dev
                else:  # 3% variaciones moderadas (5-15%)
                    dev = random.uniform(0.05, 0.15) * R
                    val = max_val + dev if random.choice([True, False]) else min_val - dev

                payload[key] = round(val, 2)
            else:
                # Valores aleatorios por defecto según el tipo o clave de variable
                if 'temp' in tipo or 'temp' in key:
                    payload[key] = round(random.uniform(20.0, 36.0), 2)
                elif 'hum' in tipo or 'hum' in key or 'suelo' in tipo:
                    payload[key] = round(random.uniform(45.0, 92.0), 2)
                elif 'aqi' in tipo or 'aire' in tipo:
                    payload[key] = round(random.uniform(15.0, 75.0), 2)
                elif 'co2' in tipo:
                    payload[key] = round(random.uniform(400.0, 550.0), 2)
                elif 'pres' in tipo or 'pres' in key:
                    payload[key] = round(random.uniform(1005.0, 1020.0), 2)
                elif 'viento' in tipo or 'wind' in key:
                    payload[key] = round(random.uniform(0.0, 45.0), 2)
                elif 'lluvia' in tipo or 'rain' in key:
                    payload[key] = round(random.uniform(0.0, 15.0), 2)
                elif 'luz' in tipo or 'lux' in key or 'light' in key:
                    payload[key] = round(random.uniform(100.0, 1200.0), 2)
                else:
                    payload[key] = round(random.uniform(10.0, 100.0), 2)

        return payload

    def run(self):
        if not self.connect_mqtt():
            return

        nombre = self.node_data.get('nombre', 'Nodo')
        print(f"▶️ [{nombre} | {self.serial_number}] Simulador en vivo activo. Tópico: {self.topic_data}")
        while self.is_running:
            try:
                payload = self.build_payload()
                msg_json = json.dumps(payload)
                self.client.publish(self.topic_data, payload=msg_json, qos=0)
                print(f"📨 [{nombre} | {self.serial_number}] -> {self.topic_data}: {msg_json}")
            except Exception as e:
                print(f"❌ [{nombre} | {self.serial_number}] Error publicando: {e}")

            time.sleep(5)

    def stop(self):
        self.is_running = False
        if self.client:
            self.client.loop_stop()
            self.client.disconnect()
        nombre = self.node_data.get('nombre', 'Nodo')
        print(f"⏹️ [{nombre} | {self.serial_number}] Simulador detenido.")


class AllNodesSimulatorManager:
    def __init__(self):
        self.active_simulators = {}

    def fetch_all_active_nodes(self):
        try:
            response = requests.get(API_URL, timeout=10)
            if response.status_code == 200:
                nodes = response.json()
                if not isinstance(nodes, list):
                    return []
                
                # Aceptar todos los nodos salvo que estén explícitamente desactivados (estado == False/0)
                active_nodes = []
                for n in nodes:
                    est = n.get('estado')
                    if est not in [False, 0, "0", "false", "False"]:
                        active_nodes.append(n)
                return active_nodes
            else:
                print(f"⚠️ Error consultando API de nodos ({API_URL}): HTTP {response.status_code}")
        except Exception as e:
            print(f"⚠️ No se pudo conectar a la API backend ({API_URL}): {e}")
        return []

    def sync_simulators(self):
        nodes = self.fetch_all_active_nodes()
        current_node_ids = {str(n['id']): n for n in nodes}

        # 1. Iniciar nuevos simuladores o actualizar si cambiaron sus lecturas
        for node_id, node_data in current_node_ids.items():
            nombre = node_data.get('nombre', 'Sin nombre')
            serial = node_data.get('serial_number', 'N/A')

            if node_id not in self.active_simulators:
                print(f"➕ Iniciando simulación de datos para nodo: {nombre} ({serial})")
                sim = VirtualNode(node_data)
                self.active_simulators[node_id] = sim
                sim.start()
            else:
                existing_sim = self.active_simulators[node_id]
                if existing_sim.node_data != node_data:
                    print(f"✏️ Cambios detectados en nodo: {nombre} ({serial}). Reiniciando simulador...")
                    existing_sim.stop()
                    new_sim = VirtualNode(node_data)
                    self.active_simulators[node_id] = new_sim
                    new_sim.start()

        # 2. Detener simuladores de nodos eliminados o desactivados
        ids_to_remove = []
        for node_id, sim in self.active_simulators.items():
            if node_id not in current_node_ids:
                nombre = sim.node_data.get('nombre', 'Sin nombre')
                serial = sim.node_data.get('serial_number', 'N/A')
                print(f"➖ Deteniendo simulación de nodo desactivado: {nombre} ({serial})")
                sim.stop()
                ids_to_remove.append(node_id)

        for i in ids_to_remove:
            del self.active_simulators[i]

    def run(self):
        print("🚀 INICIANDO SIMULADOR DE DATOS ALEATORIOS PARA TODOS LOS NODOS...")
        try:
            while True:
                self.sync_simulators()
                time.sleep(10)
        except KeyboardInterrupt:
            print("\n⛔ Apagando simulador y deteniendo todos los nodos...")
            for sim in self.active_simulators.values():
                sim.stop()
            print("👋 Finalizado.")


if __name__ == '__main__':
    manager = AllNodesSimulatorManager()
    manager.run()
