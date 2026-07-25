"""
NODE_HEALTH_MONITOR.py
──────────────────────
Script de monitoreo que verifica periódicamente la salud de todos los nodos IoT.
Llama al endpoint /api/node-health/check del backend Laravel cada 60 segundos.

Detecta:
  1. Nodos offline (sin datos por 5+ minutos)
  2. Nodos inestables (>30% de lecturas fuera de rango esperado)

Uso:
  python NODE_HEALTH_MONITOR.py
"""

import requests
import time
import sys
from datetime import datetime

API_BASE = "http://127.0.0.1:8000/api"
CHECK_INTERVAL = 60  # segundos entre cada verificación

def log(msg, level="INFO"):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{timestamp}] [{level}] {msg}")

def check_health():
    """Llama al endpoint de health check del backend."""
    try:
        response = requests.post(f"{API_BASE}/node-health/check", timeout=30)
        if response.status_code == 200:
            data = response.json()
            alerts = data.get('alerts_created', 0)
            nodes = data.get('nodes_checked', 0)
            
            if alerts > 0:
                log(f"⚠ {alerts} alerta(s) generada(s) de {nodes} nodo(s) verificados", "ALERT")
            else:
                log(f"✓ {nodes} nodo(s) verificados — sin nuevas alertas")
        else:
            log(f"✗ Error HTTP {response.status_code}: {response.text[:200]}", "ERROR")
    except requests.exceptions.ConnectionError:
        log("✗ No se pudo conectar al backend Laravel. ¿Está corriendo?", "ERROR")
    except requests.exceptions.Timeout:
        log("✗ Timeout al conectar con el backend", "ERROR")
    except Exception as e:
        log(f"✗ Error inesperado: {e}", "ERROR")

def main():
    log("═══════════════════════════════════════════════")
    log("  NODE HEALTH MONITOR - IoT ULEAM")
    log("═══════════════════════════════════════════════")
    log(f"Intervalo de verificación: {CHECK_INTERVAL}s")
    log(f"Backend: {API_BASE}")
    log("Presiona Ctrl+C para detener")
    log("")

    while True:
        check_health()
        try:
            time.sleep(CHECK_INTERVAL)
        except KeyboardInterrupt:
            log("")
            log("Monitor detenido por el usuario.")
            sys.exit(0)

if __name__ == "__main__":
    main()
