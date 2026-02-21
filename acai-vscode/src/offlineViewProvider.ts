import * as vscode from 'vscode';
import * as crypto from 'crypto';

export class AcaiOfflineProvider implements vscode.WebviewViewProvider {
  public static readonly viewId = 'acaiOfflinePanel';

  resolveWebviewView(
    webviewView: vscode.WebviewView,
    _context: vscode.WebviewViewResolveContext,
    _token: vscode.CancellationToken,
  ): void {
    webviewView.webview.options = { enableScripts: true };

    const nonce = crypto.randomBytes(16).toString('hex');
    webviewView.webview.html = this.getHtml(webviewView.webview, nonce);

    webviewView.webview.onDidReceiveMessage((msg) => {
      if (msg.command) {
        vscode.commands.executeCommand(msg.command);
      }
    });
  }

  private getHtml(webview: vscode.Webview, nonce: string): string {
    const csp = [
      `default-src 'none'`,
      `style-src ${webview.cspSource} 'unsafe-inline'`,
      `script-src 'nonce-${nonce}'`,
    ].join('; ');

    return /* html */ `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Security-Policy" content="${csp}">
  <style>
    body {
      padding: 12px 14px;
      margin: 0;
      font-family: var(--vscode-font-family);
      font-size: var(--vscode-font-size);
      color: var(--vscode-foreground);
    }
    .icon {
      font-size: 28px;
      margin-bottom: 8px;
    }
    h2 {
      font-size: 13px;
      margin: 0 0 8px;
    }
    p {
      font-size: 12px;
      line-height: 1.5;
      color: var(--vscode-descriptionForeground);
      margin: 0 0 10px;
    }
    code {
      background: var(--vscode-textCodeBlock-background);
      padding: 1px 4px;
      border-radius: 3px;
      font-size: 11px;
    }
    .steps {
      background: var(--vscode-textCodeBlock-background);
      border-radius: 4px;
      padding: 8px 10px;
      margin: 0 0 12px;
      font-family: var(--vscode-editor-font-family);
      font-size: 11px;
      line-height: 1.6;
      white-space: pre-wrap;
      color: var(--vscode-foreground);
    }
    .step-label {
      font-size: 11px;
      color: var(--vscode-descriptionForeground);
      margin: 0 0 4px;
    }
    .btn {
      display: block;
      width: 100%;
      padding: 6px 10px;
      border: none;
      border-radius: 4px;
      background: var(--vscode-button-background);
      color: var(--vscode-button-foreground);
      font-size: 12px;
      cursor: pointer;
      margin-top: 4px;
    }
    .btn:hover {
      background: var(--vscode-button-hoverBackground);
    }
  </style>
</head>
<body>
  <div class="icon">&#9888;</div>
  <h2>Servidor no disponible</h2>
  <p>No se pudo conectar a <code>localhost:9090</code>. El servidor docker-web-gui debe estar activo.</p>

  <p class="step-label">1. Clona el repositorio:</p>
  <div class="steps">git clone https://github.com/acai-cms/docker-web-gui.git</div>

  <p class="step-label">2. Inicia el servidor:</p>
  <div class="steps">cd docker-web-gui
python3 server.py</div>

  <p class="step-label">3. Verifica:</p>
  <div class="steps">curl http://localhost:9090/api/ping</div>

  <button class="btn" data-cmd="acai.checkServer">Reintentar conexion</button>

  <script nonce="${nonce}">
    const vscode = acquireVsCodeApi();
    document.querySelectorAll('[data-cmd]').forEach(el => {
      el.addEventListener('click', () => {
        vscode.postMessage({ command: el.dataset.cmd });
      });
    });
  </script>
</body>
</html>`;
  }
}
