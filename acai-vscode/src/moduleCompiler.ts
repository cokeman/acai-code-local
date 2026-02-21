import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import * as https from 'https';
import { ProjectConfig } from './configReader';

function readFileOrEmpty(filePath: string): string {
  try {
    return fs.readFileSync(filePath, 'utf-8');
  } catch {
    return '';
  }
}

function getModuleId(moduleDir: string): string {
  return path.basename(moduleDir);
}

export function compileModule(config: ProjectConfig, filePath: string): void {
  const moduleDir = path.dirname(filePath);
  const moduleId = getModuleId(moduleDir);

  const html = readFileOrEmpty(path.join(moduleDir, 'index-base.tpl'));
  const style = readFileOrEmpty(path.join(moduleDir, 'style.css'));
  const javascript = readFileOrEmpty(path.join(moduleDir, 'script.js'));
  const hook = readFileOrEmpty(path.join(moduleDir, 'hook.php'));

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

  const body = JSON.stringify({
    id: moduleId,
    html,
    htmlParsed: html,
    style,
    javascript,
    hook,
    editMode: true,
  });

  const parsed = new URL(url);

  const options: https.RequestOptions = {
    hostname: parsed.hostname,
    port: 443,
    path: `${parsed.pathname}${parsed.search}`,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Acai-Token': acai.token,
      'Content-Length': Buffer.byteLength(body),
    },
  };

  const req = https.request(options, (res) => {
    let data = '';
    res.on('data', (chunk: Buffer) => { data += chunk.toString(); });
    res.on('end', () => {
      if (res.statusCode && res.statusCode >= 200 && res.statusCode < 300) {
        vscode.window.setStatusBarMessage(`$(check) Acai: ${moduleId} compilado`, 3000);
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
