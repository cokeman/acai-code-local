import * as vscode from 'vscode';
import * as path from 'path';
import * as http from 'http';
import { readProjectConfig, ProjectConfig } from './configReader';
import { AcaiTreeProvider } from './treeProvider';
import { compileModule } from './moduleCompiler';
import { compileSection } from './sectionCompiler';
import { createModule } from './createModule';
import { openDashboard } from './webviewProvider';
import { AcaiSidebarProvider } from './sidebarViewProvider';
import { AcaiOfflineProvider } from './offlineViewProvider';

let config: ProjectConfig | undefined;

// Output channel global para logs
export const log = vscode.window.createOutputChannel('Acai', { log: true });

function checkServer(): void {
  const req = http.get('http://localhost:9090/api/settings', { timeout: 3000 }, (res) => {
    const online = res.statusCode !== undefined && res.statusCode < 500;
    vscode.commands.executeCommand('setContext', 'acai.serverOnline', online);
    log.info(`Server check: ${online ? 'online' : 'offline'}`);
    res.resume();
  });
  req.on('error', () => {
    vscode.commands.executeCommand('setContext', 'acai.serverOnline', false);
    log.info('Server check: offline');
  });
  req.on('timeout', () => {
    req.destroy();
    vscode.commands.executeCommand('setContext', 'acai.serverOnline', false);
    log.info('Server check: timeout');
  });
}

export function activate(context: vscode.ExtensionContext): void {
  log.info('Extensión activada');

  const workspaceFolders = vscode.workspace.workspaceFolders;
  if (!workspaceFolders || workspaceFolders.length === 0) { return; }

  const root = workspaceFolders[0].uri.fsPath;

  try {
    config = readProjectConfig(root);
    log.info(`Config cargada — domain: ${config.acai.domain}`);
  } catch (err: any) {
    vscode.window.showErrorMessage(`Acai: No se pudo leer la configuración — ${err.message}`);
    return;
  }

  // ── Sidebar views ──
  const sidebarProvider = new AcaiSidebarProvider(context.extensionUri);
  context.subscriptions.push(
    vscode.window.registerWebviewViewProvider(AcaiSidebarProvider.viewId, sidebarProvider),
  );

  const offlineProvider = new AcaiOfflineProvider();
  context.subscriptions.push(
    vscode.window.registerWebviewViewProvider(AcaiOfflineProvider.viewId, offlineProvider),
  );

  // ── Server check ──
  checkServer();

  context.subscriptions.push(
    vscode.commands.registerCommand('acai.checkServer', () => checkServer()),
  );

  const treeProvider = new AcaiTreeProvider(config.modulesPath, config.hooksPath, config.acai.domain, config.schemasPath);

  const treeView = vscode.window.createTreeView('acaiModulesTree', {
    treeDataProvider: treeProvider,
  });
  treeView.title = config.acai.domain;
  context.subscriptions.push(treeView);

  context.subscriptions.push(
    vscode.commands.registerCommand('acai.openFile', (filePath: string) => {
      vscode.workspace.openTextDocument(filePath).then(doc => {
        vscode.window.showTextDocument(doc);
      });
    }),
  );

  context.subscriptions.push(
    vscode.commands.registerCommand('acai.refreshTree', () => {
      treeProvider.refresh();
    }),
  );

  context.subscriptions.push(
    vscode.commands.registerCommand('acai.copyModuleId', (node: any) => {
      if (node?.moduleId) {
        vscode.env.clipboard.writeText(node.moduleId);
        vscode.window.setStatusBarMessage(`$(check) Copiado: ${node.moduleId}`, 3000);
      }
    }),
  );

  context.subscriptions.push(
    vscode.commands.registerCommand('acai.createModule', async () => {
      if (!config) { return; }
      const created = await createModule(config.modulesPath);
      if (created) {
        treeProvider.refresh();
      }
    }),
  );

  context.subscriptions.push(
    vscode.commands.registerCommand('acai.openDashboard', () => {
      openDashboard(context, (msg) => {
        if (msg.command === 'refreshTree') {
          treeProvider.refresh();
          log.info('Tree refrescado desde Dashboard');
        }
      });
    }),
  );

  // ── On-save watcher with debounce ──

  let debounceTimer: ReturnType<typeof setTimeout> | undefined;

  context.subscriptions.push(
    vscode.workspace.onDidSaveTextDocument((doc) => {
      if (!config) { return; }

      const fileName = path.basename(doc.fileName);
      const watchedFiles = ['index-base.tpl', 'script.js', 'style.css'];
      if (!watchedFiles.includes(fileName)) { return; }

      const relative = path.relative(config.modulesPath, doc.fileName);
      if (relative.startsWith('..') || path.isAbsolute(relative)) { return; }

      // Determinar si es sección general (custom-*) o módulo
      const dirName = path.basename(path.dirname(doc.fileName));
      const isSection = dirName.startsWith('custom-');

      log.info(`Guardado detectado (${isSection ? 'sección' : 'módulo'}): ${doc.fileName}`);

      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }

      debounceTimer = setTimeout(() => {
        if (isSection) {
          compileSection(config!, doc.fileName);
        } else {
          compileModule(config!, doc.fileName);
        }
      }, 500);
    }),
  );

  log.show(true); // Mostrar el panel Output automáticamente
}

export function deactivate(): void {
  // nothing to clean up
}
