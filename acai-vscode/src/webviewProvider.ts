import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';
import * as crypto from 'crypto';
import { log } from './extension';

let currentPanel: vscode.WebviewPanel | undefined;

export function openDashboard(context: vscode.ExtensionContext, onMessage?: (msg: any) => void): void {
  // Si ya hay un panel abierto, enfocarlo
  if (currentPanel) {
    currentPanel.reveal(vscode.ViewColumn.One);
    return;
  }

  const distPath = path.join(context.extensionPath, 'resources', 'webview');
  const indexPath = path.join(distPath, 'index.html');

  if (!fs.existsSync(indexPath)) {
    vscode.window.showErrorMessage(
      'Acai: No se encontró el dashboard. Ejecuta "npm run build:webview" primero.'
    );
    return;
  }

  const panel = vscode.window.createWebviewPanel(
    'acaiDashboard',
    'Acai Dashboard',
    vscode.ViewColumn.One,
    {
      enableScripts: true,
      retainContextWhenHidden: true,
      localResourceRoots: [vscode.Uri.file(distPath)],
    }
  );

  panel.iconPath = vscode.Uri.file(
    path.join(context.extensionPath, 'resources', 'acai-icon.svg')
  );

  panel.webview.html = buildHtml(panel.webview, distPath, indexPath);

  if (onMessage) {
    panel.webview.onDidReceiveMessage(onMessage, null, context.subscriptions);
  }

  panel.onDidDispose(() => {
    currentPanel = undefined;
  }, null, context.subscriptions);

  currentPanel = panel;
  log.info('Dashboard WebView abierto');
}

function buildHtml(
  webview: vscode.Webview,
  distPath: string,
  indexPath: string,
): string {
  let html = fs.readFileSync(indexPath, 'utf-8');
  const nonce = crypto.randomBytes(16).toString('hex');

  // Reemplazar rutas de assets con URIs de WebView
  const assetsUri = webview.asWebviewUri(
    vscode.Uri.file(path.join(distPath, 'assets'))
  );
  html = html.replace(/(?:\.\/assets|\/assets)/g, assetsUri.toString());

  // Inyectar la base URL del servidor antes del primer <script>
  const baseUrlScript =
    `<script nonce="${nonce}">window.__DOCKER_GUI_URL__ = 'http://localhost:9090';</script>`;
  html = html.replace(/<script/, `${baseUrlScript}\n<script`);

  // Inyectar CSP en el <head>
  const csp = [
    `default-src 'none'`,
    `connect-src http://localhost:* ws://localhost:*`,
    `script-src 'nonce-${nonce}' ${webview.cspSource}`,
    `style-src ${webview.cspSource} 'unsafe-inline'`,
    `img-src ${webview.cspSource} https: data:`,
    `font-src ${webview.cspSource}`,
  ].join('; ');

  html = html.replace(
    '<head>',
    `<head>\n<meta http-equiv="Content-Security-Policy" content="${csp}">`
  );

  // Añadir nonce a todos los <script> existentes (Vite genera scripts sin nonce)
  html = html.replace(/<script(?![^>]*nonce)/g, `<script nonce="${nonce}"`);

  return html;
}
