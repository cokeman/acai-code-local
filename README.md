# Acai Code Local

Herramienta local para gestionar webs de Acai Code desde macOS. Incluye una GUI web para lanzar entornos Docker, sincronizar módulos/hooks/layout desde el servidor remoto, y gestionar proyectos locales.

## Componentes

- **docker-web.sh** — Script bash para levantar entornos Docker (Apache + MariaDB) con webs locales o remotas Acai.
- **docker-web-gui/** — Servidor HTTP local (Python, sin dependencias externas) con GUI web para gestionar los proyectos Docker.
  - Login con credenciales Acai Code
  - Pull de webs remotas (módulos, hooks, layout, base de datos)
  - Lanzar/parar/destruir contenedores
  - Watcher de cambios en módulos
  - Proxy local a los contenedores

## Requisitos

- macOS
- Docker Desktop
- Python 3

## Uso

```bash
# Arrancar la GUI
python3 docker-web-gui/server.py

# Con auto-reload (desarrollo)
python3 docker-web-gui/server.py --reload
```

La GUI se abre en `http://localhost:9090`.

## Configuración

La configuración se guarda en `~/.docker-web-gui/config.json`. Las credenciales se almacenan en el Keychain de macOS.
