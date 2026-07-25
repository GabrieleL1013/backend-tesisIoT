from paho.mqtt.client import Client as MQTTClient
import random
import time
import json
import calendar
from datetime import datetime

# ==============================================================================
# 🛠️ GUÍA DE INTEGRACIÓN DE NUEVOS SENSORES (PARA DESARROLLADORES DE HARDWARE)
# ==============================================================================
# Para conectar cualquier sensor físico (ESP32, Arduino, Raspberry Pi) a la
# plataforma IoT ULEAM, tu código en el microcontrolador debe seguir estas reglas:
#
# 1. BROKER MQTT: 'broker.hivemq.com' (O la IP del servidor de la universidad)
# 2. PUERTO: 1883
# 3. TOPIC: 'iot_uleam_test_tesis/cualquier_cosa' (el backend escucha 'iot_uleam_test_tesis/#')
#
# 4. ESTRUCTURA DEL JSON REQUERIDO:
#    Todo mensaje debe ser un JSON válido que contenga OBLIGATORIAMENTE el campo "Sensor"
#    con el Número de Serie que registraste en la página web.
#
#    Ejemplo:
#    {
#      "Sensor": "TU_NUMERO_DE_SERIE",
#      "clave_mqtt_metrica_1": 25.5,
#      "clave_mqtt_metrica_2": 60.2,
#      "timestamp": 1720272000,
#      "dateTime": "2026-07-06 12:00:00"
#    }
# ==============================================================================

BROKER = 'broker.hivemq.com'
PORT = 1883
CLIENT_ID = f'python-mqtt-unified-{random.randint(0, 1000)}'
TOPIC_DATA = "iot_uleam_test_tesis/general"

class MQTTPublisher:
    def __init__(self):
        self.client = MQTTClient(client_id=CLIENT_ID, protocol=4)
        self.client.on_connect = self.on_connect
        self.client.on_publish = self.on_publish

    def on_connect(self, client, userdata, flags, rc, properties=None):
        if rc == 0:
            print(f"✅ Conectado al broker PÚBLICO HiveMQ como '{CLIENT_ID}'")
        else:
            print(f"❌ Error al conectar: {rc}")

    def on_publish(self, client, userdata, mid):
        pass # Silenciado para no llenar la consola

    def connect(self):
        print(f"🔌 Conectando a {BROKER}:{PORT}...")
        self.client.connect(BROKER, PORT, keepalive=60)

    def run(self):
        self.connect()
        self.client.loop_start()
        try:
            print(f"📊 Publicando datos de sensores virtuales en {TOPIC_DATA}...")
            while True:
                now = datetime.now()
                date_time = now.strftime("%Y-%m-%d %H:%M:%S")
                timestamp = calendar.timegm(time.gmtime())
                
                # --- SENSOR 1: CLIMA (ULEAMAQI) ---
                msg_clima = {
                    "Sensor": "ULEAMAQI",
                    "temperatura": round(random.uniform(22.0, 30.0), 1),
                    "humedad": round(random.uniform(55.0, 85.0), 1),
                    "timestamp": timestamp,
                    "dateTime": date_time
                }
                
                # --- SENSOR 2: CALIDAD DE AIRE (ULEAM-AIR-01) ---
                msg_aire = {
                    "Sensor": "ULEAM-AIR-01",
                    "aqi": round(random.uniform(40.0, 60.0), 1),
                    "co2": round(random.uniform(400.0, 420.0), 1),
                    "press": round(random.uniform(1010.0, 1015.0), 1),
                    "timestamp": timestamp,
                    "dateTime": date_time
                }
                
                # Publicar ambos simuladores
                self.client.publish(TOPIC_DATA, payload=json.dumps(msg_clima), qos=0)
                self.client.publish(TOPIC_DATA, payload=json.dumps(msg_aire), qos=0)
                
                print(f"📦 Payload Clima: {json.dumps(msg_clima)}")
                print(f"📦 Payload Aire:  {json.dumps(msg_aire)}")
                print("-" * 50)
                
                time.sleep(5)
                
        except KeyboardInterrupt:
            print("\n⛔ Simuladores Detenidos.")
        finally:
            self.client.disconnect()
            self.client.loop_stop()

if __name__ == '__main__':
    publisher = MQTTPublisher()
    publisher.run()
