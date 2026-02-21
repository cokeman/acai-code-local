import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import * as https from 'https';
import { ProjectConfig } from './configReader';
import { parseHtml } from './htmlParser';

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

function sendToApi(url: string, body: string, token: string, moduleId: string): void {
  const parsed = new URL(url);

  const options: https.RequestOptions = {
    hostname: parsed.hostname,
    port: 443,
    path: `${parsed.pathname}${parsed.search}`,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Acai-Token': token,
      'Content-Length': Buffer.byteLength(body),
    },
  };

  const req = https.request(options, (res) => {
    let data = '';
    res.on('data', (chunk: Buffer) => { data += chunk.toString(); });
    res.on('end', () => {
      if (res.statusCode && res.statusCode >= 200 && res.statusCode < 300) {
        let hasError = false;
        try {
          const parsed = JSON.parse(data);
          if (parsed.error) { hasError = true; }
        } catch { /* not JSON, treat as success */ }

        if (hasError) {
          vscode.window.showWarningMessage(`Acai: Error compilando ${moduleId}`);
        } else {
          vscode.window.setStatusBarMessage(`$(check) Acai: ${moduleId} compilado`, 3000);
        }
      } else {
        vscode.window.showWarningMessage(
          `Acai: Error compilando ${moduleId} (HTTP ${res.statusCode})`
        );
      }
    });
  });

  req.on('error', (err: Error) => {
    vscode.window.showErrorMessage(`Acai: Error de red — ${err.message}`);
  });

  req.write(body);
  req.end();
}

export async function compileModule(config: ProjectConfig, filePath: string): Promise<void> {
  const moduleDir = path.dirname(filePath);
  const moduleId = path.basename(moduleDir);

  const html = readFileOrEmpty(path.join(moduleDir, 'index-base.tpl'));
  const style = readFileOrEmpty(path.join(moduleDir, 'style.css'));
  const javascript = readFileOrEmpty(path.join(moduleDir, 'script.js'));
  const hook = readFileOrEmpty(path.join(moduleDir, 'hook.php'));
  const builderJson = readJsonOrNull(path.join(moduleDir, 'builder.json'));

  // Parsear HTML base → Twig (htmlParsed) + vars editables
  // Misma lógica que remoteParser.js / save.js de acai-code
  let htmlParsed = html;
  let vars: any = {};

  try {
    const previousSchema = builderJson?.vars || {};
    const parseResult = await parseHtml(html, previousSchema);
    htmlParsed = parseResult.htmlParsed;
    vars = parseResult.vars;
  } catch (err: any) {
    vscode.window.showWarningMessage(`Acai: Error parseando HTML, enviando sin parsear — ${err.message}`);
  }

  const { acai, docker, tunnel } = config;
  const tunnelUrl = `${tunnel.tunnelHost}:${tunnel.tunnelPort}`;

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

  // Construir moduleData — misma estructura que save.js de acai-code
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

  sendToApi(url, JSON.stringify(moduleData), acai.token, moduleId);
}
