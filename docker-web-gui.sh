#!/bin/bash
# docker-web-gui.sh — Lanzador de la GUI web para docker-web.sh
# Arranca el servidor Python y abre el navegador.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
GUI_DIR="$SCRIPT_DIR/docker-web-gui"
SERVER="$GUI_DIR/server.py"
PORT="${1:-9090}"
PID_FILE="$GUI_DIR/.server.pid"

GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

msg()  { echo -e "${GREEN}[docker-web-gui]${NC} $*"; }
err()  { echo -e "${RED}[docker-web-gui]${NC} $*" >&2; }

# Verificar que existe server.py
if [[ ! -f "$SERVER" ]]; then
    err "No se encontro $SERVER"
    exit 1
fi

# Si ya hay un servidor corriendo, abrir directamente
if [[ -f "$PID_FILE" ]]; then
    OLD_PID=$(cat "$PID_FILE")
    if kill -0 "$OLD_PID" 2>/dev/null; then
        msg "Servidor ya corriendo (PID $OLD_PID)"
        open "http://localhost:$PORT"
        exit 0
    else
        rm -f "$PID_FILE"
    fi
fi

# Arrancar servidor en background
msg "Arrancando servidor en puerto $PORT..."
python3 "$SERVER" "$PORT" &
SERVER_PID=$!
echo "$SERVER_PID" > "$PID_FILE"

# Esperar a que el servidor responda (max 5 segundos)
for i in $(seq 1 50); do
    if curl -s "http://localhost:$PORT/" > /dev/null 2>&1; then
        msg "Servidor listo en http://localhost:$PORT (PID $SERVER_PID)"
        open "http://localhost:$PORT"

        # Trap para limpiar al salir
        cleanup() {
            msg "Parando servidor (PID $SERVER_PID)..."
            kill "$SERVER_PID" 2>/dev/null || true
            rm -f "$PID_FILE"
        }
        trap cleanup EXIT INT TERM

        msg "Presiona Ctrl+C para parar el servidor"
        wait "$SERVER_PID"
        exit 0
    fi
    sleep 0.1
done

# Si no respondio, algo fallo
err "El servidor no respondio despues de 5 segundos"
kill "$SERVER_PID" 2>/dev/null || true
rm -f "$PID_FILE"
exit 1
