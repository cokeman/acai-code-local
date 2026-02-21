import * as vscode from 'vscode';
import * as path from 'path';
import { readProjectConfig, ProjectConfig } from './configReader';
import { AcaiTreeProvider } from './treeProvider';
import { compileModule } from './moduleCompiler';
import { createModule } from './createModule';

let config: ProjectConfig | undefined;

export function activate(context: vscode.ExtensionContext): void {
  const workspaceFolders = vscode.workspace.workspaceFolders;
  if (!workspaceFolders || workspaceFolders.length === 0) { return; }

  const root = workspaceFolders[0].uri.fsPath;

  try {
    config = readProjectConfig(root);
  } catch (err: any) {
    vscode.window.showErrorMessage(`Acai: No se pudo leer la configuración — ${err.message}`);
    return;
  }

  const treeProvider = new AcaiTreeProvider(config.modulesPath, config.hooksPath);

  context.subscriptions.push(
    vscode.window.registerTreeDataProvider('acaiModulesTree', treeProvider),
  );

  // ── Commands ──

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

      // Verify the file is inside our modules path
      const relative = path.relative(config.modulesPath, doc.fileName);
      if (relative.startsWith('..') || path.isAbsolute(relative)) { return; }

      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }

      debounceTimer = setTimeout(() => {
        compileModule(config!, doc.fileName);
      }, 500);
    }),
  );
}

export function deactivate(): void {
  // nothing to clean up
}
