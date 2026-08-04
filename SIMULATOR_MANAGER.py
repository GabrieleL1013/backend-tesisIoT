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

# Configuración del Backend de Laravel
API_URL = "http://127.0.0.1:8000/api/nodos/internal"

class VirtualNode(threading.Thread):
    def __init__(self, node_data):
        super().__init__()
        self.node_data = node_data
        self.is_running = True
        self.client = None
        self.client_id = node_data.get('client_id') or f"sim-{node_data.get('serial_number')}-{random.randint(100,999)}"
        self.broker = node_data.get('broker') or 'broker.hivemq.com'
        self.port = int(node_data.get('port') or 1883)
        
        loc_slug = node_data.get('location_slug') if (node_data.get('location_slug') and isinstance(node_data.get('location_slug'), str)) else (node_data.get('serial_number') or 'general')
        self.topic_data = node_data.get('topic_data') or f"iot_uleam/{loc_slug}"
            
        self.use_v5 = bool(node_data.get('use_mqtt_v5', False))
        self.username = node_data.get('username')
        self.password = node_data.get('password')
        
    def on_connect(self, client, userdata, flags, rc, properties=None):
        if rc == 0:
            print(f"✅ [{self.node_data['nombre']}] Conectado a {self.broker}:{self.port}")
        else:
            print(f"❌ [{self.node_data['nombre']}] Error de conexión (rc={rc})")

    def on_disconnect(self, client, userdata, rc, properties=None):
        print(f"⚠️ [{self.node_data['nombre']}] Desconectado del broker")

    def connect_mqtt(self):
        try:
            protocol = 5 if self.use_v5 else 4
            self.client = MQTTClient(client_id=self.client_id, protocol=protocol)
            
            if self.username and self.password:
                self.client.username_pw_set(self.username, self.password)
                
            self.client.on_connect = self.on_connect
            self.client.on_disconnect = self.on_disconnect
            
            if self.use_v5:
                properties = Properties(PacketTypes.CONNECT)
                properties.SessionExpiryInterval = 3600
                self.client.connect(self.broker, self.port, keepalive=60, properties=properties)
            else:
                self.client.connect(self.broker, self.port, keepalive=60)
                
            self.client.loop_start()
            return True
        except Exception as e:
            print(f"❌ [{self.node_data['nombre']}] Error iniciando MQTT: {e}")
            return False

    def build_payload(self):
        now = datetime.now()
        date_time = now.strftime("%Y-%m-%d %H:%M:%S")
        timestamp = calendar.timegm(time.gmtime())
        
        payload = {
            "Sensor": self.node_data.get('serial_number'),
            "timestamp": timestamp,
            "dateTime": date_time
        }
        
        # Generar valores aleatorios para las lecturas configuradas
        lecturas = self.node_data.get('lecturas', [])
        for l in lecturas:
            key = l.get('data_type')
            tipo = str(l.get('tipo')).lower()
            
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
                
                if p > 0.10: # 90% normal
                    val = random.uniform(min_val, max_val)
                elif p > 0.03: # 7% (desvía hasta 5%)
                    dev = random.uniform(0.01, 0.05) * R
                    val = max_val + dev if random.choice([True, False]) else min_val - dev
                elif p > 0.01: # 2% (desvía entre 5% y 25%)
                    dev = random.uniform(0.05, 0.25) * R
                    val = max_val + dev if random.choice([True, False]) else min_val - dev
                else: # 1% (desvía entre 100% y 500%)
                    dev = random.uniform(1.0, 5.0) * R
                    val = max_val + dev if random.choice([True, False]) else min_val - dev
                    
                # Prevent extreme negative values if physical impossible, but let's keep it simple
                payload[key] = round(val, 2)
            else:
                # Fallback to old behavior if no min/max provided
                if 'temp' in tipo or 'temp' in key:
                    payload[key] = round(random.uniform(20.0, 35.0), 2)
                elif 'hum' in tipo or 'hum' in key or 'suelo' in tipo:
                    payload[key] = round(random.uniform(50.0, 90.0), 2)
                elif 'aqi' in tipo or 'aire' in tipo:
                    payload[key] = round(random.uniform(20.0, 80.0), 2)
                elif 'co2' in tipo:
                    payload[key] = round(random.uniform(400.0, 500.0), 2)
                elif 'pres' in tipo or 'pres' in key:
                    payload[key] = round(random.uniform(1010.0, 1015.0), 2)
                else:
                    payload[key] = round(random.uniform(10.0, 100.0), 2)
                
        return payload

    def run(self):
        if not self.connect_mqtt():
            return
            
        print(f"▶️ [{self.node_data['nombre']}] Simulador iniciado.")
        while self.is_running:
            try:
                payload = self.build_payload()
                msg_json = json.dumps(payload)
                self.client.publish(self.topic_data, payload=msg_json, qos=0)
                print(f"📨 [{self.node_data['nombre']}] -> {self.topic_data}: {msg_json}")
            except Exception as e:
                print(f"❌ [{self.node_data['nombre']}] Error publicando: {e}")
                
            # Publicar cada 5 segundos
            time.sleep(5)
            
    def stop(self):
        self.is_running = False
        if self.client:
            self.client.loop_stop()
            self.client.disconnect()
        print(f"⏹️ [{self.node_data['nombre']}] Simulador detenido.")


class SimulatorManager:
    def __init__(self):
        self.active_simulators = {}

    def fetch_simulated_nodes(self):
        try:
            response = requests.get(API_URL, timeout=10)
            if response.status_code == 200:
                nodes = response.json()
                # Filtrar solo los nodos activos y que son simulados
                simulated = [n for n in nodes if n.get('estado') == True and n.get('is_simulated') == True]
                return simulated
            else:
                print(f"⚠️ Error consultando API: {response.status_code}")
        except Exception as e:
            print(f"⚠️ No se pudo conectar al backend Laravel ({API_URL}): {e}")
        return []

    def sync_simulators(self):
        print("🔄 Sincronizando nodos simulados desde la base de datos...")
        nodes = self.fetch_simulated_nodes()
        current_node_ids = {str(n['id']): n for n in nodes}
        
        # 1. Iniciar nuevos simuladores o ACTUALIZAR los existentes si cambió su configuración/variables
        for node_id, node_data in current_node_ids.items():
            if node_id not in self.active_simulators:
                print(f"➕ Detectado nuevo nodo simulado: {node_data['nombre']}")
                sim = VirtualNode(node_data)
                self.active_simulators[node_id] = sim
                sim.start()
            else:
                # Comprobar si cambiaron las lecturas o la configuración del nodo
                existing_sim = self.active_simulators[node_id]
                if existing_sim.node_data != node_data:
                    print(f"✏️ Cambios detectados en nodo simulado: {node_data['nombre']}. Reiniciando con las nuevas variables...")
                    existing_sim.stop()
                    new_sim = VirtualNode(node_data)
                    self.active_simulators[node_id] = new_sim
                    new_sim.start()
                
        # 2. Detener simuladores que fueron eliminados o desactivados
        ids_to_remove = []
        for node_id, sim in self.active_simulators.items():
            if node_id not in current_node_ids:
                print(f"➖ Nodo simulado eliminado o desactivado: {sim.node_data['nombre']}")
                sim.stop()
                ids_to_remove.append(node_id)
                
        for i in ids_to_remove:
            del self.active_simulators[i]

    def run(self):
        print("🚀 INICIANDO SIMULATOR MANAGER...")
        try:
            while True:
                self.sync_simulators()
                # Revisar cambios en la BD cada 10 segundos para mayor reactividad
                time.sleep(10)
        except KeyboardInterrupt:
            print("\n⛔ Apagando Manager y deteniendo todos los simuladores...")
            for sim in self.active_simulators.values():
                sim.stop()
            print("👋 Adiós.")

if __name__ == '__main__':
    manager = SimulatorManager()
    manager.run()
