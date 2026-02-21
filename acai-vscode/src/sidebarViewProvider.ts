import * as vscode from 'vscode';
import * as crypto from 'crypto';

export class AcaiSidebarProvider implements vscode.WebviewViewProvider {
  public static readonly viewId = 'acaiSidebarPanel';

  constructor(private readonly extensionUri: vscode.Uri) {}

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
      padding: 8px 12px;
      margin: 0;
      font-family: var(--vscode-font-family);
      font-size: var(--vscode-font-size);
      color: var(--vscode-foreground);
    }
    .btn {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
      padding: 6px 10px;
      margin-bottom: 6px;
      border: none;
      border-radius: 4px;
      background: var(--vscode-button-secondaryBackground);
      color: var(--vscode-button-secondaryForeground);
      font-size: 12px;
      cursor: pointer;
      text-align: left;
    }
    .btn:hover {
      background: var(--vscode-button-secondaryHoverBackground);
    }
    .btn.primary {
      background: var(--vscode-button-background);
      color: var(--vscode-button-foreground);
    }
    .btn.primary:hover {
      background: var(--vscode-button-hoverBackground);
    }
    .btn .icon {
      font-size: 14px;
      width: 16px;
      text-align: center;
      flex-shrink: 0;
    }
    .section-label {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--vscode-descriptionForeground);
      margin: 10px 0 6px;
    }
    .section-label:first-child {
      margin-top: 0;
    }
  </style>
</head>
<body>
  <div class="section-label">Acciones</div>
  <button class="btn primary" data-cmd="acai.openDashboard">
    <span class="icon">⬡</span> Abrir Dashboard
  </button>
<button class="btn" data-cmd="acai.refreshTree">
    <span class="icon">↻</span> Refrescar Árbol
  </button>

  <script nonce="${nonce}">
    const vscode = acquireVsCodeApi();
    document.querySelectorAll('.btn[data-cmd]').forEach(btn => {
      btn.addEventListener('click', () => {
        vscode.postMessage({ command: btn.dataset.cmd });
      });
    });
  </script>
</body>
</html>`;
  }
}
