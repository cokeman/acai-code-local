import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';

export async function createModule(modulesPath: string): Promise<string | undefined> {
  const name = await vscode.window.showInputBox({
    prompt: 'Nombre del nuevo módulo',
    placeHolder: 'ej: bannerprincipal',
    validateInput: (value) => {
      if (!value || !value.trim()) {
        return 'El nombre no puede estar vacío';
      }
      if (!/^[a-z0-9_-]+$/.test(value.trim())) {
        return 'Solo minúsculas, números, guiones y guiones bajos';
      }
      return undefined;
    },
  });

  if (!name) { return undefined; }

  const dirPath = path.join(modulesPath, name.trim());

  if (fs.existsSync(dirPath)) {
    vscode.window.showWarningMessage(`El módulo "${name}" ya existe.`);
    return undefined;
  }

  fs.mkdirSync(dirPath, { recursive: true });

  const files = ['index-base.tpl', 'script.js', 'style.css', 'hook.php'];
  for (const file of files) {
    fs.writeFileSync(path.join(dirPath, file), '', 'utf-8');
  }

  const tplPath = path.join(dirPath, 'index-base.tpl');
  const doc = await vscode.workspace.openTextDocument(tplPath);
  await vscode.window.showTextDocument(doc);

  return dirPath;
}
