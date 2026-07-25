from paho.mqtt.client import Client as MQTTClient
from paho.mqtt.properties import Properties
from paho.mqtt.packettypes import PacketTypes
from datetime import datetime
import random
import time
import json
import calendar

# === Configuración del cliente MQTT ===
BROKER = 'broker.hivemq.com' # Cambiado temporalmente a HiveMQ para pruebas
PORT = 1883
USERNAME = 'mqtt-uleam'
PASSWORD = 'Mqtt-Uleam2025$'

USE_MQTT_V5 = False
CLIENT_ID = f'python-mqtt-{random.randint(0, 1000)}'

# === Datos del dispositivo ===
DEVICE_SERIAL = "ULEAM-V2-01"  # Serial del dispositivo
LOCATION_SLUG = "uleam_v2"  # Slug de la ubicación
TOPIC_DATA = f"iot_uleam_test_tesis/{LOCATION_SLUG}"  # Topic donde se publicarán los datos

class MQTTPublisher:
    def __init__(self):
        self.client = MQTTClient(client_id=CLIENT_ID, protocol=5 if USE_MQTT_V5 else 4)
        self.client.username_pw_set(USERNAME, PASSWORD)
        self.client.on_connect = self.on_connect
        self.client.on_disconnect = self.on_disconnect
        self.client.on_publish = self.on_publish
        self.client.reconnect_delay_set(min_delay=1, max_delay=30)

    def on_connect(self, client, userdata, flags, rc, properties=None):
        if rc == 0:
            print(f"✅ Conectado exitosamente al broker como '{CLIENT_ID}'")
        else:
            print(f"❌ Error al conectar: {rc}")

    def on_disconnect(self, client, userdata, rc, properties=None):
        if rc != 0:
            print("⚠️  Desconexión inesperada. Reconectando...")
        else:
            print("🔌 Desconectado del broker")

    def on_publish(self, client, userdata, mid):
        print(f"📤 Mensaje publicado con ID: {mid}")

    def connect(self):
        try:
            print(f"🔌 Conectando a {BROKER}:{PORT}...")
            if USE_MQTT_V5:
                properties = Properties(PacketTypes.CONNECT)
                properties.SessionExpiryInterval = 3600
                self.client.connect(BROKER, PORT, keepalive=60, properties=properties)
            else:
                self.client.connect(BROKER, PORT, keepalive=60)
        except Exception as e:
            print(f"❌ Error al conectar: {e}")

    def build_message(self):
        now = datetime.now()
        date_time = now.strftime("%d/%m/%Y %H:%M:%S")
        
        # Enviar el mensaje al TOPIC con la siguiente estructura:
        message = {
            # Sensor corresponde al serial del dispositivo que se establece en la plataforma
            "Sensor": DEVICE_SERIAL, # DEVICE_SERIAL = "DEVICE0001"
            # ... (Lecturas) deben corresponder al "Tipo de Dato" que se registra en la plataforma, se recomienda tener lecturas sin espacios
            "temperatura": random.randint(1, 50),
            "humedad": random.randint(50, 90),
            # ...
            "timestamp": calendar.timegm(time.gmtime()),
            "dateTime": date_time
        }
        return json.dumps(message)

    def publish(self, topic, message):
        try:
            result = self.client.publish(topic, payload=message, qos=1)
            if result.rc != 0:
                print(f"❌ Error al publicar en {topic}: {result.rc}")
            else:
                print(f"📨 Publicado en {topic}")
        except Exception as e:
            print(f"❌ Error al publicar: {e}")

    def run(self):
        self.connect()
        self.client.loop_start()
        try:
            print("📊 Iniciando publicación de datos en el topic correspondiente...")
            while True:
                data_message = self.build_message()
                print(f"📦 {data_message}")
                self.publish(TOPIC_DATA, data_message)
                time.sleep(5)  # Publica datos cada 3 segundos
        except KeyboardInterrupt:
            print("\n⛔ Detenido por el usuario.")
        finally:
            self.client.disconnect()
            self.client.loop_stop()


if __name__ == '__main__':
    publisher = MQTTPublisher()
    publisher.run()
