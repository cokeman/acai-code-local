#!/bin/bash
set -euo pipefail

# =============================================================================
# docker-web.sh — Levanta un entorno Docker (PHP+Apache+MariaDB) para cualquier
# proyecto web. Genera los archivos necesarios en <proyecto>/.docker/
# =============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ---- Funciones de utilidad ----

msg()  { echo -e "${GREEN}[docker-web]${NC} $*"; }
warn() { echo -e "${YELLOW}[docker-web]${NC} $*"; }
err()  { echo -e "${RED}[docker-web]${NC} $*" >&2; }

usage() {
    cat <<'EOF'
Uso:
  docker-web.sh <ruta-proyecto> [opciones]

Opciones:
  --sql <archivo.sql>         Importar SQL local
  --remote-db <host>          Host del servidor remoto para mysqldump
  --remote-user <usuario>     Usuario del servidor remoto
  --remote-pass <password>    Password del servidor remoto
  --remote-dbname <nombre>    Nombre de la BD remota
  --remote-port <puerto>      Puerto MySQL remoto (default: 3306)
  --redis                     Incluir contenedor Redis
  --acai                      Modo Acai: web-base compartida + overlays
  --stop                      Parar los contenedores del proyecto
  --destroy                   Parar y eliminar volúmenes del proyecto
  --list                      Listar proyectos docker-web activos
  --rebuild                   Forzar rebuild de la imagen
  -h, --help                  Mostrar esta ayuda

Ejemplos:
  docker-web.sh ./mi-web --sql ./bbdd.sql
  docker-web.sh ./mi-web --remote-db 1.2.3.4 --remote-user user --remote-pass 'pass' --remote-dbname mydb
  docker-web.sh ./mi-web --sql ./bbdd.sql --redis
  docker-web.sh ./mi-web --stop
  docker-web.sh --list
EOF
    exit 0
}

# Busca el siguiente puerto libre a partir de un puerto base
find_free_port() {
    local port="$1"
    while lsof -iTCP:"$port" -sTCP:LISTEN &>/dev/null; do
        port=$((port + 1))
    done
    echo "$port"
}

# ---- Parsear argumentos ----

PROJECT_DIR=""
SQL_FILE=""
REMOTE_HOST=""
REMOTE_USER=""
REMOTE_PASS=""
REMOTE_DBNAME=""
REMOTE_PORT="3306"
WITH_REDIS=false
ACAI_MODE=false
DO_STOP=false
DO_DESTROY=false
DO_LIST=false
DO_REBUILD=false

# Manejar --list sin ruta de proyecto
if [[ "${1:-}" == "--list" ]]; then
    DO_LIST=true
elif [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
elif [[ -z "${1:-}" ]]; then
    usage
else
    PROJECT_DIR="$(cd "$1" && pwd)"
    shift
fi

while [[ $# -gt 0 ]]; do
    case "$1" in
        --sql)         SQL_FILE="$2"; shift 2 ;;
        --remote-db)   REMOTE_HOST="$2"; shift 2 ;;
        --remote-user) REMOTE_USER="$2"; shift 2 ;;
        --remote-pass) REMOTE_PASS="$2"; shift 2 ;;
        --remote-dbname) REMOTE_DBNAME="$2"; shift 2 ;;
        --remote-port) REMOTE_PORT="$2"; shift 2 ;;
        --redis)       WITH_REDIS=true; shift ;;
        --acai)        ACAI_MODE=true; shift ;;
        --stop)        DO_STOP=true; shift ;;
        --destroy)     DO_DESTROY=true; shift ;;
        --list)        DO_LIST=true; shift ;;
        --rebuild)     DO_REBUILD=true; shift ;;
        -h|--help)     usage ;;
        *)             err "Opcion desconocida: $1"; usage ;;
    esac
done

# ---- Comando: --list ----

if $DO_LIST; then
    msg "Proyectos docker-web activos:"
    echo ""
    # Buscar contenedores con label docker-web
    containers=$(docker ps --filter "label=docker-web=true" --format '{{.Names}}\t{{.Status}}\t{{.Ports}}' 2>/dev/null || true)
    if [[ -z "$containers" ]]; then
        echo "  (ninguno)"
    else
        printf "  %-30s %-25s %s\n" "NOMBRE" "ESTADO" "PUERTOS"
        printf "  %-30s %-25s %s\n" "------" "------" "-------"
        while IFS=$'\t' read -r name status ports; do
            printf "  %-30s %-25s %s\n" "$name" "$status" "$ports"
        done <<< "$containers"
    fi
    echo ""
    exit 0
fi

# A partir de aquí se requiere PROJECT_DIR
if [[ -z "$PROJECT_DIR" ]]; then
    err "Se requiere la ruta del proyecto"
    usage
fi

PROJECT_NAME=$(basename "$PROJECT_DIR" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_-]/_/g')
DOCKER_DIR="$PROJECT_DIR/.docker"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEB_BASE_DIR="${WEB_BASE_DIR:-$SCRIPT_DIR/web-base}"

# ---- Funcion: restaurar archivos parcheados ----

restore_files() {
    [[ -f "$PROJECT_DIR/.acai" ]] && return
    local settings_backup="$DOCKER_DIR/settings.dat.php.backup"
    local settings_file
    settings_file=$(find "$PROJECT_DIR" -path "*/cms/data/settings.dat.php" -type f 2>/dev/null | head -1)
    if [[ -n "$settings_file" && -f "$settings_backup" ]]; then
        cp "$settings_backup" "$settings_file"
        msg "Restaurado settings.dat.php original"
    fi

    local htaccess_backup="$DOCKER_DIR/htaccess.backup"
    if [[ -f "$htaccess_backup" && -f "$PROJECT_DIR/.htaccess" ]]; then
        cp "$htaccess_backup" "$PROJECT_DIR/.htaccess"
        msg "Restaurado .htaccess original"
    fi
}

# ---- Comando: --stop ----

if $DO_STOP; then
    if [[ -f "$DOCKER_DIR/docker-compose.yml" ]]; then
        msg "Parando contenedores de '$PROJECT_NAME'..."
        docker compose -f "$DOCKER_DIR/docker-compose.yml" -p "dw-${PROJECT_NAME}" down
        restore_files
        msg "Contenedores parados."
    else
        warn "No se encontro $DOCKER_DIR/docker-compose.yml"
    fi
    exit 0
fi

# ---- Comando: --destroy ----

if $DO_DESTROY; then
    if [[ -f "$DOCKER_DIR/docker-compose.yml" ]]; then
        msg "Eliminando contenedores y volumenes de '$PROJECT_NAME'..."
        docker compose -f "$DOCKER_DIR/docker-compose.yml" -p "dw-${PROJECT_NAME}" down -v
        restore_files
        msg "Contenedores y volumenes eliminados."
    else
        warn "No se encontro $DOCKER_DIR/docker-compose.yml"
    fi
    exit 0
fi

# ---- Exportar SQL remoto si se pide ----

if [[ -n "$REMOTE_HOST" ]]; then
    if [[ -z "$REMOTE_USER" || -z "$REMOTE_PASS" || -z "$REMOTE_DBNAME" ]]; then
        err "Para --remote-db se requieren --remote-user, --remote-pass y --remote-dbname"
        exit 1
    fi

    SQL_FILE="$DOCKER_DIR/remote-dump.sql"
    mkdir -p "$DOCKER_DIR"

    msg "Exportando BD '$REMOTE_DBNAME' desde $REMOTE_HOST..."
    if command -v mysqldump &>/dev/null; then
        mysqldump -h "$REMOTE_HOST" -P "$REMOTE_PORT" \
            -u "$REMOTE_USER" -p"$REMOTE_PASS" \
            --single-transaction --routines --triggers \
            "$REMOTE_DBNAME" > "$SQL_FILE"
    elif docker image inspect mariadb:10.11 &>/dev/null || docker pull mariadb:10.11 &>/dev/null; then
        docker run --rm mariadb:10.11 \
            mysqldump -h "$REMOTE_HOST" -P "$REMOTE_PORT" \
            -u "$REMOTE_USER" -p"$REMOTE_PASS" \
            --single-transaction --routines --triggers \
            "$REMOTE_DBNAME" > "$SQL_FILE"
    else
        err "No se encontro mysqldump ni se pudo usar Docker para exportar"
        exit 1
    fi

    SQL_SIZE=$(du -h "$SQL_FILE" | cut -f1)
    msg "Dump completado: $SQL_FILE ($SQL_SIZE)"
fi

# ---- Validar SQL ----

if [[ -n "$SQL_FILE" && ! -f "$SQL_FILE" ]]; then
    err "Archivo SQL no encontrado: $SQL_FILE"
    exit 1
fi

# ---- Buscar puertos libres ----

WEB_PORT=$(find_free_port 8080)
HTTPS_PORT=$(find_free_port 8443)
DB_PORT=$(find_free_port 33060)

msg "Puertos asignados: web=$WEB_PORT, https=$HTTPS_PORT, db=$DB_PORT"

# ---- Crear directorio .docker ----

mkdir -p "$DOCKER_DIR"

# Copiar SQL al directorio .docker si existe y no esta ya ahi
if [[ -n "$SQL_FILE" ]]; then
    SQL_BASENAME=$(basename "$SQL_FILE")
    SQL_DEST="$DOCKER_DIR/$SQL_BASENAME"
    if [[ "$(realpath "$SQL_FILE")" != "$(realpath "$SQL_DEST" 2>/dev/null || echo '')" ]]; then
        cp "$SQL_FILE" "$SQL_DEST"
    fi

    # Limpiar DEFINERs para evitar errores de privilegios al importar
    if grep -q 'DEFINER=' "$SQL_DEST" 2>/dev/null; then
        sed -i '' 's/DEFINER=[^ ]* //g' "$SQL_DEST"
        msg "DEFINERs eliminados del SQL"
    fi

    SQL_IN_DOCKER="$SQL_BASENAME"
else
    SQL_IN_DOCKER=""
fi

# ---- Variables de entorno (reusar si ya existen para no romper volumenes) ----

if [[ -f "$DOCKER_DIR/.env" ]]; then
    # Reusar credenciales existentes
    source "$DOCKER_DIR/.env"
    msg "Reusando credenciales existentes de .env"
else
    DB_SERVER=db
    DB_DATABASE="${PROJECT_NAME}_db"
    DB_USERNAME="${PROJECT_NAME}_user"
    DB_PASSWORD="docker_$(openssl rand -hex 8)"
    DB_ROOT_PASSWORD="root_$(openssl rand -hex 8)"

    cat > "$DOCKER_DIR/.env" <<EOF
DB_SERVER=db
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
EOF

    msg "Generado .env con nuevas credenciales"
fi

# ---- Generar Dockerfile ----

REDIS_EXT=""
if $WITH_REDIS; then
    REDIS_EXT='
# Instalar extension Redis
RUN pecl install redis && docker-php-ext-enable redis'
fi

cat > "$DOCKER_DIR/Dockerfile" <<EOF
FROM php:8.2-apache

# Instalar dependencias para GD
RUN apt-get update && apt-get install -y \\
    libfreetype6-dev \\
    libjpeg62-turbo-dev \\
    libpng-dev \\
    libwebp-dev \\
    libzip-dev \\
    && rm -rf /var/lib/apt/lists/*

# Configurar y instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \\
    && docker-php-ext-install -j\$(nproc) gd mysqli pdo pdo_mysql zip
${REDIS_EXT}

# short_open_tag
RUN echo "short_open_tag = On" > /usr/local/etc/php/conf.d/short-tags.ini

# Modulos Apache
RUN a2enmod rewrite headers deflate expires

# AllowOverride All para .htaccess
RUN sed -i '/<Directory \\/var\\/www\\/>/,/<\\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf && \\
    sed -i '/<Directory \\/var\\/www\\/>/,/<\\/Directory>/ s/Options Indexes FollowSymLinks/Options Indexes FollowSymLinks/' /etc/apache2/apache2.conf

# ServerName y MIME types extra
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && \\
    echo "AddType text/css .vue" >> /etc/apache2/conf-available/custom-mime.conf && \\
    a2enconf custom-mime

# Cliente MariaDB, curl, ImageMagick (mogrify) y WebP (cwebp)
RUN apt-get update && apt-get install -y mariadb-client curl imagemagick webp && rm -rf /var/lib/apt/lists/*

# Bore para tuneles TCP (web + BD)
RUN ARCH=\$(dpkg --print-architecture) && \\
    if [ "\$ARCH" = "amd64" ]; then BORE_ARCH="x86_64"; else BORE_ARCH="aarch64"; fi && \\
    curl -L "https://github.com/ekzhang/bore/releases/download/v0.6.0/bore-v0.6.0-\${BORE_ARCH}-unknown-linux-musl.tar.gz" \\
    -o /tmp/bore.tar.gz && \\
    tar -xzf /tmp/bore.tar.gz -C /usr/local/bin/ && \\
    chmod +x /usr/local/bin/bore && \\
    rm /tmp/bore.tar.gz

WORKDIR /var/www/html

# SSL autofirmado para desarrollo local
RUN a2enmod ssl && \\
    openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \\
      -keyout /etc/ssl/private/localhost.key \\
      -out /etc/ssl/certs/localhost.crt \\
      -subj "/CN=localhost" && \\
    sed -i 's|SSLCertificateFile.*|SSLCertificateFile /etc/ssl/certs/localhost.crt|' /etc/apache2/sites-available/default-ssl.conf && \\
    sed -i 's|SSLCertificateKeyFile.*|SSLCertificateKeyFile /etc/ssl/private/localhost.key|' /etc/apache2/sites-available/default-ssl.conf && \\
    a2ensite default-ssl

EXPOSE 80 443
EOF

msg "Generado Dockerfile"

# ---- Generar init.sh ----

cat > "$DOCKER_DIR/init.sh" <<'INITEOF'
#!/bin/bash

echo "Esperando a que la base de datos este lista..."
sleep 5

TABLE_COUNT=$(mysql -h"$DB_SERVER" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" --skip-ssl -e "SHOW TABLES;" 2>/dev/null | wc -l)

if [ "$TABLE_COUNT" -le 1 ]; then
    SQL_FILE=$(find /docker-entrypoint-init.d/ -name "*.sql" -type f 2>/dev/null | head -1)
    if [ -n "$SQL_FILE" ]; then
        echo "Importando base de datos desde $SQL_FILE..."
        mysql -h"$DB_SERVER" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" --skip-ssl < "$SQL_FILE"
        echo "Base de datos importada correctamente."
    else
        echo "Advertencia: No se encontro archivo .sql para importar"
    fi
else
    echo "La base de datos ya contiene $TABLE_COUNT tablas. Omitiendo importacion."
fi

echo "Inicializacion completada."
INITEOF

chmod +x "$DOCKER_DIR/init.sh"
msg "Generado init.sh"

# ---- Generar tunnel.sh ----

cat > "$DOCKER_DIR/tunnel.sh" <<'TUNNELEOF'
#!/bin/bash
BORE_SERVER="46.101.52.52"

# Bore: tunel TCP para la web (puerto 443/SSL)
TUNNEL_URL_FILE="/tunnel-url/tunnel-url.txt"
echo "" > "$TUNNEL_URL_FILE"

BORE_WEB_LOG="/tmp/bore-web.log"
bore local 443 --to "$BORE_SERVER" > "$BORE_WEB_LOG" 2>&1 &

(
    for i in $(seq 1 30); do
        sleep 0.5
        if [ -f "$BORE_WEB_LOG" ]; then
            addr=$(sed 's/\x1b\[[0-9;]*m//g' "$BORE_WEB_LOG" | grep -oE "${BORE_SERVER}:[0-9]+" | head -1)
            if [ -n "$addr" ]; then
                echo "$addr" > "$TUNNEL_URL_FILE"
                echo "[bore] Web tunnel: $addr"
                break
            fi
        fi
    done
) &

echo "[bore] Bore web iniciado en background"

# Bore: tunel TCP para la base de datos (puerto 3306)
BORE_URL_FILE="/tunnel-url/bore-db-url.txt"
echo "" > "$BORE_URL_FILE"

BORE_DB_LOG="/tmp/bore-db.log"
bore local 3306 --local-host db --to "$BORE_SERVER" > "$BORE_DB_LOG" 2>&1 &

(
    for i in $(seq 1 30); do
        sleep 0.5
        if [ -f "$BORE_DB_LOG" ]; then
            addr=$(sed 's/\x1b\[[0-9;]*m//g' "$BORE_DB_LOG" | grep -oE "${BORE_SERVER}:[0-9]+" | head -1)
            if [ -n "$addr" ]; then
                echo "$addr" > "$BORE_URL_FILE"
                echo "[bore] DB tunnel: $addr"
                break
            fi
        fi
    done
) &

echo "[bore] Bore DB iniciado en background"
TUNNELEOF

chmod +x "$DOCKER_DIR/tunnel.sh"
touch "$DOCKER_DIR/tunnel-url.txt"
touch "$DOCKER_DIR/bore-db-url.txt"
msg "Generado tunnel.sh"

# ---- Generar docker-compose.yml ----

# Construir seccion de volumenes del web
if $ACAI_MODE; then
    # Validar que web-base existe
    if [[ ! -d "$WEB_BASE_DIR" ]]; then
        err "web-base no encontrada en $WEB_BASE_DIR"
        exit 1
    fi
    mkdir -p "$PROJECT_DIR/template/estandar/modulos" "$PROJECT_DIR/template/estandar/images" "$PROJECT_DIR/hooks" "$PROJECT_DIR/cms/uploads" "$PROJECT_DIR/cms/lib/plugins" "$PROJECT_DIR/cms/data/schema"
    WEB_VOLUMES="      - ${WEB_BASE_DIR}:/var/www/html
      - ${WEB_BASE_DIR}:/web-base-src:ro
      - ./init.sh:/docker-entrypoint-init.d/init.sh
      - ${PROJECT_DIR}/template/estandar/modulos:/var/www/html/template/estandar/modulos
      - ${PROJECT_DIR}/template/estandar/images:/var/www/html/template/estandar/images
      - ${PROJECT_DIR}/hooks:/var/www/html/hooks
      - ${PROJECT_DIR}/cms/uploads:/var/www/html/cms/uploads
      - ${PROJECT_DIR}/cms/lib/plugins:/var/www/html/cms/lib/plugins
      - ${PROJECT_DIR}/cms/data/schema:/var/www/html/cms/data/schema"
    # Configs parcheadas (se generan mas abajo)
    WEB_VOLUMES+="
      - ./settings.dat.php:/var/www/html/cms/data/settings.dat.php
      - ./.htaccess:/var/www/html/.htaccess"
    # Caches escribibles (originales se copian desde /web-base-src al arrancar)
    WEB_VOLUMES+="
      - acai_cache:/var/www/html/cms/data/cache
      - acai_js_cache:/var/www/html/template/estandar/js/minified
      - acai_css_cache:/var/www/html/template/estandar/css/minified"
else
    WEB_VOLUMES="      - ${PROJECT_DIR}:/var/www/html
      - ./init.sh:/docker-entrypoint-init.d/init.sh"
fi

# Montar SQL si existe
if [[ -n "$SQL_IN_DOCKER" ]]; then
    WEB_VOLUMES="${WEB_VOLUMES}
      - ./${SQL_IN_DOCKER}:/docker-entrypoint-init.d/${SQL_IN_DOCKER}"
fi

# Tunnel volumes
WEB_VOLUMES="${WEB_VOLUMES}
      - ./tunnel.sh:/tunnel-url/tunnel.sh
      - ./tunnel-url.txt:/tunnel-url/tunnel-url.txt
      - ./bore-db-url.txt:/tunnel-url/bore-db-url.txt"

# Redis (comparte red con web via network_mode para que 127.0.0.1:6379 funcione)
REDIS_SERVICE=""
REDIS_DEPENDS=""
if $WITH_REDIS; then
    REDIS_SERVICE="
  redis:
    image: redis:7-alpine
    container_name: dw-${PROJECT_NAME}-redis
    labels:
      docker-web: \"true\"
      docker-web-project: \"${PROJECT_NAME}\"
      docker-web-dir: \"${PROJECT_DIR}\"
    restart: unless-stopped
    network_mode: \"service:web\"
    depends_on:
      - web
    healthcheck:
      test: [\"CMD\", \"redis-cli\", \"ping\"]
      interval: 10s
      timeout: 5s
      retries: 3"
fi

# Depends_on del web
WEB_DEPENDS="    depends_on:
      db:
        condition: service_healthy"

# Environment del web
WEB_ENV="      - DB_SERVER=\${DB_SERVER}
      - DB_DATABASE=\${DB_DATABASE}
      - DB_USERNAME=\${DB_USERNAME}
      - DB_PASSWORD=\${DB_PASSWORD}"

# Pre-comando acai: copiar originales de web-base a volumes escribibles
ACAI_PRECMD=""
if $ACAI_MODE; then
    ACAI_PRECMD="cp -rp /web-base-src/template/estandar/js/minified/* /var/www/html/template/estandar/js/minified/ 2>/dev/null; cp -rp /web-base-src/template/estandar/css/minified/* /var/www/html/template/estandar/css/minified/ 2>/dev/null; chown -R www-data:www-data /var/www/html/template/estandar/js/minified /var/www/html/template/estandar/css/minified /var/www/html/cms/data/cache 2>/dev/null; mkdir -p /var/www/html/cms/uploads/webp 2>/dev/null; chown -R www-data:www-data /var/www/html/cms/uploads 2>/dev/null; "
fi

cat > "$DOCKER_DIR/docker-compose.yml" <<EOF
services:
  web:
    build: .
    container_name: dw-${PROJECT_NAME}-web
    labels:
      docker-web: "true"
      docker-web-project: "${PROJECT_NAME}"
      docker-web-dir: "${PROJECT_DIR}"
    ports:
      - "${WEB_PORT}:80"
      - "${HTTPS_PORT}:443"
    volumes:
${WEB_VOLUMES}
    environment:
${WEB_ENV}
${WEB_DEPENDS}
    command: >
      bash -c "${ACAI_PRECMD}chmod +x /docker-entrypoint-init.d/init.sh &&
               /docker-entrypoint-init.d/init.sh &&
               chmod +x /tunnel-url/tunnel.sh && /tunnel-url/tunnel.sh &&
               apache2-foreground"

  db:
    image: mariadb:10.11
    container_name: dw-${PROJECT_NAME}-db
    labels:
      docker-web: "true"
      docker-web-project: "${PROJECT_NAME}"
      docker-web-dir: "${PROJECT_DIR}"
    environment:
      - MYSQL_ROOT_PASSWORD=\${DB_ROOT_PASSWORD}
      - MYSQL_DATABASE=\${DB_DATABASE}
      - MYSQL_USER=\${DB_USERNAME}
      - MYSQL_PASSWORD=\${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "${DB_PORT}:3306"
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 5
${REDIS_SERVICE}
volumes:
  db_data:$(if $ACAI_MODE; then echo "
  acai_cache:
  acai_js_cache:
  acai_css_cache:"; fi)
EOF

msg "Generado docker-compose.yml"

# ---- Parchear settings.dat.php del CMS (si existe) ----

if $ACAI_MODE; then
    SETTINGS_SRC="$WEB_BASE_DIR/cms/data/settings.dat.php"
    SETTINGS_DEST="$DOCKER_DIR/settings.dat.php"
    if [[ -f "$SETTINGS_SRC" ]]; then
        cp "$SETTINGS_SRC" "$SETTINGS_DEST"
        sed -i '' -E '/^\[mysql\]/,/^\[/ {
            s/^(hostname = ).*/\1"db"/
            s/^(database = ).*/\1"'"${DB_DATABASE}"'"/
            s/^(username = ).*/\1"'"${DB_USERNAME}"'"/
            s/^(password = ).*/\1"'"${DB_PASSWORD}"'"/
        }' "$SETTINGS_DEST"
        msg "settings.dat.php generado en .docker/"
    fi
else
    SETTINGS_FILE=$(find "$PROJECT_DIR" -path "*/cms/data/settings.dat.php" -type f 2>/dev/null | head -1)
    if [[ -n "$SETTINGS_FILE" ]]; then
        SETTINGS_BACKUP="$DOCKER_DIR/settings.dat.php.backup"

        # Solo hacer backup si no existe uno ya (para no sobreescribir el original)
        if [[ ! -f "$SETTINGS_BACKUP" ]]; then
            cp "$SETTINGS_FILE" "$SETTINGS_BACKUP"
            msg "Backup de settings.dat.php creado"
        fi

        # Parchear: apuntar la BD al contenedor Docker
        sed -i '' -E '/^\[mysql\]/,/^\[/ {
            s/^(hostname = ).*/\1"db"/
            s/^(database = ).*/\1"'"${DB_DATABASE}"'"/
            s/^(username = ).*/\1"'"${DB_USERNAME}"'"/
            s/^(password = ).*/\1"'"${DB_PASSWORD}"'"/
        }' "$SETTINGS_FILE"

        msg "settings.dat.php parcheado para Docker (hostname=db)"
    fi
fi

# ---- Parchear .htaccess: desactivar redirect HTTPS (si existe) ----

if $ACAI_MODE; then
    HTACCESS_SRC="$WEB_BASE_DIR/.htaccess"
    HTACCESS_DEST="$DOCKER_DIR/.htaccess"
    if [[ -f "$HTACCESS_SRC" ]]; then
        cp "$HTACCESS_SRC" "$HTACCESS_DEST"
        sed -i '' '/RewriteCond %{SERVER_PORT} 80/{
            s/^/# [docker-web] /
            n
            s/^/# [docker-web] /
        }' "$HTACCESS_DEST"
        msg ".htaccess generado en .docker/"
    fi
else
    if [[ -f "$PROJECT_DIR/.htaccess" ]]; then
        HTACCESS_BACKUP="$DOCKER_DIR/htaccess.backup"

        if [[ ! -f "$HTACCESS_BACKUP" ]]; then
            cp "$PROJECT_DIR/.htaccess" "$HTACCESS_BACKUP"
            msg "Backup de .htaccess creado"
        fi

        # Comentar la regla generica de redirect puerto 80 -> HTTPS
        sed -i '' '/RewriteCond %{SERVER_PORT} 80/{
            s/^/# [docker-web] /
            n
            s/^/# [docker-web] /
        }' "$PROJECT_DIR/.htaccess"

        msg ".htaccess parcheado: desactivado redirect HTTPS"
    fi
fi

# ---- Agregar .docker a .gitignore si no esta ----

if ! $ACAI_MODE; then
    if [[ -f "$PROJECT_DIR/.gitignore" ]]; then
        if ! grep -qx '\.docker/' "$PROJECT_DIR/.gitignore" 2>/dev/null; then
            echo '.docker/' >> "$PROJECT_DIR/.gitignore"
            msg "Agregado .docker/ a .gitignore"
        fi
    fi
fi

# ---- Levantar contenedores ----

msg "Levantando contenedores para '$PROJECT_NAME'..."

BUILD_FLAG=""
if $DO_REBUILD; then
    BUILD_FLAG="--build"
fi

docker compose -f "$DOCKER_DIR/docker-compose.yml" \
    --env-file "$DOCKER_DIR/.env" \
    -p "dw-${PROJECT_NAME}" \
    up -d $BUILD_FLAG

echo ""
msg "============================================"
msg "  Proyecto: ${PROJECT_NAME}"
msg "  Web:      http://localhost:${WEB_PORT}"
msg "  Web SSL:  https://localhost:${HTTPS_PORT}"
msg "  DB:       localhost:${DB_PORT}"
msg "  DB User:  ${DB_USERNAME}"
msg "  DB Pass:  ${DB_PASSWORD}"
if $WITH_REDIS; then
msg "  Redis:    disponible internamente en redis:6379"
fi

# Esperar URL del tunel web (max 15 segundos)
TUNNEL_URL=""
msg "  Tunnel:   esperando URL publica..."
for i in $(seq 1 15); do
    TUNNEL_URL=$(cat "$DOCKER_DIR/tunnel-url.txt" 2>/dev/null | tr -d '[:space:]')
    if [[ -n "$TUNNEL_URL" ]]; then
        break
    fi
    sleep 1
done
if [[ -n "$TUNNEL_URL" ]]; then
    msg "  Tunnel:   https://${TUNNEL_URL}"
else
    warn "  Tunnel:   no disponible (sin conexion a internet?)"
fi

msg "============================================"
msg ""
msg "Para parar:     docker-web.sh ${PROJECT_DIR} --stop"
msg "Para destruir:  docker-web.sh ${PROJECT_DIR} --destroy"
msg ""
