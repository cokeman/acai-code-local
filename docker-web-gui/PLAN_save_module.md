# Plan: Portar generateModuleFromString (PHP → Python) a server.py

## Contexto
El builder de Acai Code tiene una función PHP `generateModuleFromString()` que genera módulos desde datos crudos (HTML, CSS, JS, vars) y los sube al servidor remoto. Se quiere portar esta lógica a `server.py` como un nuevo endpoint API para poder generar y subir módulos desde la GUI local sin depender del backend PHP.

## Flujo que se porta

```
1. Recibir datos del módulo (HTML, CSS, JS, hook, vars, config)
2. Construir la estructura config (builder.json) con mapeo de vars → campos BD
3. Escribir archivos temporales (index.tpl, style.css, script.js, hook.php, builder.json, thumbnail)
4. Comprimir en ZIP → base64
5. POST a viewer_functions.php con action_ws=saveModule
6. Limpiar archivos temporales
7. Devolver resultado
```

## Archivo a modificar
**`docker-web-gui/server.py`**

### Funciones helper nuevas (fuera de la clase Handler)

1. **`get_free_next_field_name(prefix, busy_fields)`** — Port de `getFreeNextFieldName()`. Itera 1-10, devuelve primer `prefix+i` libre.

2. **`parse_list_options_field(options)`** — Port de `parseListOptionsField()`. Parsea opciones tipo `"valor|label,valor2|label2"`.

3. **`build_module_config(data)`** — Port de la lógica de config+vars de `generateModuleFromString()`. Construye el dict `config` con mapeo de tipos:
   - `textfield`/`link`/`headfield` → `title`
   - `textbox`/`wysiwyg` → `text`
   - `list` → `list`
   - `upload` → `image`
   - Soporta vars simples y multi (grupos repetibles)

4. **`compress_module_dir(module_path)`** — Port de `compressPlugin()`. ZIP recursivo de la carpeta → devuelve bytes del ZIP.

5. **`save_module_remote(domain, ssl_enabled, token, token_hash, folder_name, zip_bytes, replace=True)`** — Port de `saveModule()`. Hace POST con `action_ws=saveModule`, `content=base64(zip)`, `zip=true`.

### Nuevo endpoint POST

**`/api/acai/save-module`** — Handler `handle_acai_save_module(body)`

**Input (body JSON):**
```json
{
  "domain": "example.com",
  "ssl": true,
  "token": "...",
  "tokenHash": "...",
  "id": "mi-modulo",
  "html": "<div>...</div>",
  "htmlParsed": "<div>...</div>",
  "style": ".clase { ... }",
  "javascript": "console.log(...)",
  "hook": "<?php ...",
  "label": "Mi Módulo",
  "description": "Descripción",
  "vars": [...],
  "image": "data:image/jpeg;base64,...",
  "editMode": false,
  "tailWind": false,
  "notParseComponents": "0",
  "staticVars": {}
}
```

**Output:**
```json
{
  "success": true,
  "module_id": "mi-modulo",
  "response": { ... }
}
```

**Flujo del handler:**
1. Validar campos requeridos (domain, token, tokenHash, id, html)
2. Llamar `build_module_config(body)` para construir config
3. Crear directorio temporal con `tempfile.mkdtemp()`
4. Escribir archivos: index.tpl, index-base.tpl, style.css, script.js, hook.php, builder.json, thumbnail.jpg
5. Comprimir con `compress_module_dir()`
6. Subir con `save_module_remote()`
7. Limpiar directorio temporal con `shutil.rmtree()`
8. Devolver resultado

### Registro de ruta en do_POST

Añadir en el elif chain de `do_POST()`:
```python
elif path == "/api/acai/save-module":
    self.handle_acai_save_module(body)
```

## Funciones existentes que se reusan
- `acai_web_request()` (línea 215) — Para el POST de saveModule al servidor remoto
- `send_json()` / `send_error_json()` — Para respuestas
- Patrón de `base_payload = {"token": ..., "tokenHash": ...}` igual que en `handle_acai_pull_web`

## Notas de implementación
- Se usa `tempfile.mkdtemp()` en vez de `modulos/cache/` ya que no hay filesystem PHP local
- Se omite `compileTWIG()` (notParseComponents=2) — es lógica PHP-only, no se puede replicar en Python
- El thumbnail por defecto se descarga de `https://cms.cocosolution.com/img/module_base.jpg` o se usa el base64 del body
- No se necesita lógica de colisión de IDs (prefijo dominio + timestamp) ya que el frontend controla el ID

## Verificación
1. Arrancar `python3 server.py`
2. Hacer POST a `/api/acai/save-module` con datos de prueba (token/tokenHash de una sesión activa)
3. Verificar que el módulo aparece en el panel de módulos de la web Acai

## Pendiente
- Buscar y revisar el código PHP fuente (`generateModuleFromString`, `getFreeNextFieldName`, `parseListOptionsField`, `compressPlugin`, `saveModule`) para copiar la lógica exacta de mapeo de vars/tipos.
