#!/usr/bin/env bash
# Inicia el Sistema Clínico Local con FrankenPHP.
# Por defecto escucha en TODAS las interfaces (0.0.0.0) para permitir
# acceso desde otros dispositivos de la red.
#
#   ./bin/iniciar.sh                -> http://0.0.0.0:8085  (LAN)
#   PORT=8090 ./bin/iniciar.sh      -> puerto 8090
#   BIND=127.0.0.1:9090 ./bin/iniciar.sh   -> solo local
set -euo pipefail
cd "$(dirname "$0")/.."

FRANKENPHP="${FRANKENPHP_BIN:-$(pwd)/frankenphp}"
PORT="${PORT:-8085}"
BIND="${BIND:-0.0.0.0:$PORT}"

if [ ! -x "$FRANKENPHP" ]; then
    echo "No se encuentra el binario de FrankenPHP en $FRANKENPHP" >&2
    exit 1
fi

# Detectar IP local para mostrar la URL accesible desde otros dispositivos.
HOSTONLY="${BIND%%:*}"
LAN_IP=""
for iface in /sys/class/net/*; do
    [ -e "$iface" ] || continue
    ip=$(ip -4 -o addr show "${iface##*/}" 2>/dev/null | awk '{print $4}' | cut -d/ -f1 | grep -v '^127\.' || true)
    if [ -n "$ip" ]; then LAN_IP="$ip"; break; fi
done

echo "▶ Sistema Clínico Local"
echo "   Local : http://127.0.0.1:$PORT"
if [ "$HOSTONLY" != "127.0.0.1" ]; then
  if [ -n "$LAN_IP" ]; then
    echo "   Red   : http://$LAN_IP:$PORT   (desde otros dispositivos de la red)"
  else
    echo "   Red   : conectado a todas las interfaces; consulta la IP de la máquina"
  fi
fi
echo "   Detener: Ctrl+C"

exec "$FRANKENPHP" php-server --listen="$BIND" --root=public