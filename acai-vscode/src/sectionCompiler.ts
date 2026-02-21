import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import * as http from 'http';
import { ProjectConfig } from './configReader';
import { parseHtml } from './htmlParser';
import { getProjectStatus } from './dockerCheck';
import { log } from './extension';

function readFileOrEmpty(filePath: string): string {
  try {
    return fs.readFileSync(filePath, 'utf-8');
  } catch {
    return '';
  }
}

function getLocalModuleIds(modulesPath: string): string[] {
  try {
    return fs.readdirSync(modulesPath)
      .filter(d => fs.statSync(path.join(modulesPath, d)).isDirectory());
  } catch {
    return [];
  }
}

function getLocalTables(workspaceRoot: string): any[] {
  const schemaDir = path.join(workspaceRoot, 'cms', 'data', 'schema');
  try {
    const files = fs.readdirSync(schemaDir).filter(f => f.endsWith('.ini.php'));
    const tables: any[] = [];
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
        if (trimmed === '[enlace]') { hasEnlace = true; }
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

function postJson(url: string, body: string): Promise<any> {
  return new Promise((resolve, reject) => {
    const parsed = new URL(url);

    log.info(`POST ${parsed.host}${parsed.pathname} (${Buffer.byteLength(body)} bytes)`);

    const options: http.RequestOptions = {
      hostname: parsed.hostname,
      port: parsed.port,
      path: `${parsed.pathname}${parsed.search}`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(body),
        'User-Agent': 'AcaiVSCode/0.1.0',
      },
    };

    const req = http.request(options, (res) => {
      let data = '';
      res.on('data', (chunk: Buffer) => { data += chunk.toString(); });
      res.on('end', () => {
        log.info(`Response: HTTP ${res.statusCode}`);
        log.info(`Body: ${data.substring(0, 1000)}`);

        if (res.statusCode && res.statusCode >= 200 && res.statusCode < 300) {
          try {
            resolve(JSON.parse(data));
          } catch {
            resolve({ success: true, raw: data });
          }
        } else {
          reject(new Error(`HTTP ${res.statusCode}`));
        }
      });
    });

    req.on('error', (err: Error) => reject(err));
    req.write(body);
    req.end();
  });
}

export async function compileSection(config: ProjectConfig, filePath: string): Promise<void> {
  const sectionDir = path.dirname(filePath);
  const dirName = path.basename(sectionDir);
  const tableName = dirName.replace('custom-', '');

  log.info(`── Compilando sección: ${dirName} (tabla: ${tableName}) ──`);

  const html = readFileOrEmpty(path.join(sectionDir, 'index-base.tpl'));
  const style = readFileOrEmpty(path.join(sectionDir, 'style.css'));
  const javascript = readFileOrEmpty(path.join(sectionDir, 'script.js'));

  log.info(`Archivos: html=${html.length}c style=${style.length}c js=${javascript.length}c`);

  // Obtener URL del Docker local
  const { acai } = config;
  const status = await getProjectStatus(acai.domain);
  if (!status.running || !status.webUrl) {
    log.error('Docker no está levantado');
    vscode.window.showWarningMessage('Acai: Docker no está levantado, no se puede compilar la sección');
    return;
  }

  const viewerUrl = `${status.webUrl}/cms/lib/viewer_functions.php`;
  const modulePath = `/modulos/${dirName}/`;
  log.info(`Docker local: ${status.webUrl}`);

  // Parsear HTML → Twig (solo parseComponents, sin generateBuilderVars)
  let parsedContent = html;

  if (html) {
    try {
      log.info('Parseando HTML → Twig...');
      const moduleIds = getLocalModuleIds(config.modulesPath);
      const tables = getLocalTables(config.workspaceRoot);
      const parseResult = await parseHtml(html, undefined, moduleIds, tables);
      parsedContent = parseResult.htmlParsed;
      log.info(`Parse OK — ${parsedContent.length}c`);

      // Guardar index.tpl parseado en local
      const indexTplPath = path.join(sectionDir, 'index.tpl');
      fs.writeFileSync(indexTplPath, parsedContent, 'utf-8');
      log.info(`Guardado local: ${indexTplPath}`);
    } catch (err: any) {
      log.error(`Parse error: ${err.message}`);
    }
  }

  // 1. Guardar template HTML via saveLexicalData
  if (html) {
    log.info('Guardando template via saveLexicalData...');
    const payload = {
      action_ws: 'saveLexicalData',
      token: acai.token,
      tokenHash: acai.tokenHash,
      content: parsedContent,
      rawDataSended: true,
      endPointFolder: dirName,
      parserType: '2',
      aditionalFiles: [
        { path: modulePath, fileName: 'index.tpl', content: parsedContent },
        { path: modulePath, fileName: 'index-base.tpl', content: html },
      ],
    };

    try {
      const resp = await postJson(viewerUrl, JSON.stringify(payload));
      if (resp.success) {
        log.info('saveLexicalData OK');
      } else {
        log.error(`saveLexicalData error: ${JSON.stringify(resp)}`);
        vscode.window.showWarningMessage(`Acai: Error guardando template de ${dirName}`);
        return;
      }
    } catch (err: any) {
      log.error(`saveLexicalData falló: ${err.message}`);
      vscode.window.showErrorMessage(`Acai: Error de red guardando ${dirName} — ${err.message}`);
      return;
    }
  }

  // 2. Guardar CSS y JS via saveFileBuilder
  const filesToSave: [string, string][] = [];
  if (javascript) { filesToSave.push(['script.js', javascript]); }
  if (style) { filesToSave.push(['style.css', style]); }

  for (const [fileName, content] of filesToSave) {
    log.info(`Guardando ${fileName} via saveFileBuilder...`);
    const payload = {
      action_ws: 'saveFileBuilder',
      token: acai.token,
      tokenHash: acai.tokenHash,
      fileName,
      content,
      rawDataSended: true,
      rootFolder: false,
      path: modulePath,
    };

    try {
      const resp = await postJson(viewerUrl, JSON.stringify(payload));
      if (resp.success) {
        log.info(`${fileName} OK`);
      } else {
        log.error(`saveFileBuilder error (${fileName}): ${JSON.stringify(resp)}`);
      }
    } catch (err: any) {
      log.error(`saveFileBuilder falló (${fileName}): ${err.message}`);
    }
  }

  vscode.window.setStatusBarMessage(`$(check) Acai: ${dirName} compilado`, 3000);
}
