import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import * as https from 'https';
import { ProjectConfig } from './configReader';
import { parseHtml } from './htmlParser';
import { log } from './extension';

function readFileOrEmpty(filePath: string): string {
  try {
    return fs.readFileSync(filePath, 'utf-8');
  } catch {
    return '';
  }
}

function readJsonOrNull(filePath: string): any | null {
  try {
    return JSON.parse(fs.readFileSync(filePath, 'utf-8'));
  } catch {
    return null;
  }
}

interface SchemaTable {
  tableName: string;
  enlace: string | boolean;
}

function getLocalTables(workspaceRoot: string): SchemaTable[] {
  const schemaDir = path.join(workspaceRoot, 'cms', 'data', 'schema');
  try {
    const files = fs.readdirSync(schemaDir).filter(f => f.endsWith('.ini.php'));
    const tables: SchemaTable[] = [];

    for (const file of files) {
      const content = fs.readFileSync(path.join(schemaDir, file), 'utf-8');
      let tableName = '';
      let hasEnlace = false;

      for (const line of content.split('\n')) {
        const trimmed = line.trim();
        if (trimmed.startsWith('tableName')) {
          const match = trimmed.match(/^tableName\s*=\s*"?([^"]*)"?/);
          if (match) { tableName = match[1].trim(); }
        }
        if (trimmed === '[enlace]') {
          hasEnlace = true;
        }
      }

      if (tableName) {
        tables.push({ tableName, enlace: hasEnlace ? tableName : '' });
      }
    }

    return tables;
  } catch {
    return [];
  }
}

function getLocalModuleIds(modulesPath: string): string[] {
  try {
    return fs.readdirSync(modulesPath)
      .filter(d => {
        const fullPath = path.join(modulesPath, d);
        return fs.statSync(fullPath).isDirectory();
      });
  } catch {
    return [];
  }
}

function sendToApi(url: string, body: string, token: string, moduleId: string): void {
  const parsed = new URL(url);

  log.info(`POST ${parsed.hostname}${parsed.pathname} (${Buffer.byteLength(body)} bytes)`);

  const options: https.RequestOptions = {
    hostname: parsed.hostname,
    port: 443,
    path: `${parsed.pathname}${parsed.search}`,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Acai-Token': token,
      'Content-Length': Buffer.byteLength(body),
      'User-Agent': 'AcaiVSCode/0.1.0',
    },
  };

  const req = https.request(options, (res) => {
    let data = '';
    res.on('data', (chunk: Buffer) => { data += chunk.toString(); });
    res.on('end', () => {
      log.info(`Response: HTTP ${res.statusCode}`);
      log.info(`Body: ${data.substring(0, 1000)}`);

      if (res.statusCode && res.statusCode >= 200 && res.statusCode < 300) {
        let hasError = false;
        try {
          const resp = JSON.parse(data);
          if (resp.error) {
            hasError = true;
            log.error(`API error: ${resp.error}`);
          }
        } catch { /* not JSON, treat as success */ }

        if (hasError) {
          vscode.window.showWarningMessage(`Acai: Error compilando ${moduleId}`);
        } else {
          vscode.window.setStatusBarMessage(`$(check) Acai: ${moduleId} compilado`, 3000);
        }
      } else {
        log.error(`HTTP error: ${res.statusCode} — ${data.substring(0, 500)}`);
        vscode.window.showWarningMessage(
          `Acai: Error compilando ${moduleId} (HTTP ${res.statusCode})`
        );
      }
    });
  });

  req.on('error', (err: Error) => {
    log.error(`Network error: ${err.message}`);
    vscode.window.showErrorMessage(`Acai: Error de red — ${err.message}`);
  });

  req.write(body);
  req.end();
}

export async function compileModule(config: ProjectConfig, filePath: string): Promise<void> {
  const moduleDir = path.dirname(filePath);
  const moduleId = path.basename(moduleDir);

  log.info(`── Compilando: ${moduleId} ──`);

  const html = readFileOrEmpty(path.join(moduleDir, 'index-base.tpl'));
  const style = readFileOrEmpty(path.join(moduleDir, 'style.css'));
  const javascript = readFileOrEmpty(path.join(moduleDir, 'script.js'));
  const hook = readFileOrEmpty(path.join(moduleDir, 'hook.php'));
  const builderJson = readJsonOrNull(path.join(moduleDir, 'builder.json'));

  log.info(`Archivos: html=${html.length}c style=${style.length}c js=${javascript.length}c hook=${hook.length}c builder.json=${builderJson ? 'sí' : 'no'}`);

  // Parsear HTML base → Twig (htmlParsed) + vars editables
  let htmlParsed = html;
  let vars: any = {};

  try {
    log.info('Parseando HTML → Twig...');
    const previousSchema = builderJson?.vars || {};

    // Leer IDs de módulos y tablas del filesystem para que el parser detecte tags embebidos
    const moduleIds = getLocalModuleIds(config.modulesPath);
    const tables = getLocalTables(config.workspaceRoot);
    log.info(`Módulos locales: ${moduleIds.length}, Tablas: ${tables.length}`);

    const parseResult = await parseHtml(html, previousSchema, moduleIds, tables);
    htmlParsed = parseResult.htmlParsed;
    vars = parseResult.vars;
    log.info(`Parse OK — htmlParsed=${htmlParsed.length}c vars=${Object.keys(vars).length} campos`);
  } catch (err: any) {
    log.error(`Parse error: ${err.message}\n${err.stack}`);
    vscode.window.showWarningMessage(`Acai: Error parseando HTML — ${err.message}`);
  }

  const { acai, docker, tunnel } = config;
  const tunnelUrl = `${tunnel.tunnelHost}:${tunnel.tunnelPort}`;

  log.info(`Config: domain=${acai.domain} tunnel=${tunnelUrl} db=${docker.dbDatabase}`);

  const params = new URLSearchParams({
    menu: 'apartados',
    action: 'edit',
    generateModuleFromString: '1',
    localPrimaryDomain: acai.domain,
    localOverrideDomain: tunnelUrl,
    localDbUser: docker.dbUsername,
    localDbPass: docker.dbPassword,
    localDbName: docker.dbDatabase,
    localDbHost: tunnel.boreDbHost,
    localDbPort: tunnel.boreDbPort,
  });

  const url = `https://acai.cms.cocosolution.com/admin.php?${params.toString()}`;

  const moduleData: any = {
    id: moduleId,
    html,
    htmlParsed,
    style,
    javascript,
    hook,
    vars,
    editMode: true,
    tailWind: true,
    notParseComponents: '2',
    label: builderJson?.label || 'Sin título',
    description: builderJson?.description || '',
    onlyAdminModule: builderJson?.onlyAdminModule || false,
    requiredPlugins: builderJson?.requiredPlugins || '',
    MJMLModule: builderJson?.MJMLModule || false,
  };

  log.info('Enviando al CMS...');
  sendToApi(url, JSON.stringify(moduleData), acai.token, moduleId);
}
