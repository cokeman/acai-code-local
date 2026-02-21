import * as vscode from 'vscode';
import * as path from 'path';
import { readProjectConfig, ProjectConfig } from './configReader';
import { AcaiTreeProvider } from './treeProvider';
import { compileModule } from './moduleCompiler';
import { createModule } from './createModule';

let config: ProjectConfig | undefined;

// Output channel global para logs
export const log = vscode.window.createOutputChannel('Acai', { log: true });

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

  const treeProvider = new AcaiTreeProvider(config.modulesPath, config.hooksPath, config.acai.domain);

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

  // ── On-save watcher with debounce ──

  let debounceTimer: ReturnType<typeof setTimeout> | undefined;

  context.subscriptions.push(
    vscode.workspace.onDidSaveTextDocument((doc) => {
      if (!config) { return; }

      const fileName = path.basename(doc.fileName);
      if (fileName !== 'index-base.tpl') { return; }

      const relative = path.relative(config.modulesPath, doc.fileName);
      if (relative.startsWith('..') || path.isAbsolute(relative)) { return; }

      log.info(`Guardado detectado: ${doc.fileName}`);

      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }

      debounceTimer = setTimeout(() => {
        compileModule(config!, doc.fileName);
      }, 500);
    }),
  );

  log.show(true); // Mostrar el panel Output automáticamente
}

export function deactivate(): void {
  // nothing to clean up
}
