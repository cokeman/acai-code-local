import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import { getProjectStatus } from './dockerCheck';

// ── Types ──

type NodeKind =
  | 'root-hooks'
  | 'root-sections'
  | 'root-modules'
  | 'hook'
  | 'section'
  | 'section-file'
  | 'category'
  | 'module'
  | 'module-file'
  | 'create-module'
  | 'message';

interface TreeNode {
  kind: NodeKind;
  label: string;
  filePath?: string;
  dirPath?: string;
  moduleId?: string;
  children?: TreeNode[];
  tooltip?: string;
  iconId?: string;
}

// ── Provider ──

export class AcaiTreeProvider implements vscode.TreeDataProvider<TreeNode> {
  private _onDidChangeTreeData = new vscode.EventEmitter<TreeNode | undefined | void>();
  readonly onDidChangeTreeData = this._onDidChangeTreeData.event;

  private _dockerRunning: boolean | null = null; // null = not checked yet

  constructor(
    private modulesPath: string,
    private hooksPath: string,
    private domain: string,
  ) {
    this.checkDocker();
  }

  refresh(): void {
    this.checkDocker();
  }

  private async checkDocker(): Promise<void> {
    const status = await getProjectStatus(this.domain);
    this._dockerRunning = status.running;
    this._onDidChangeTreeData.fire();
  }

  getTreeItem(node: TreeNode): vscode.TreeItem {
    if (node.kind === 'message') {
      const item = new vscode.TreeItem(node.label, vscode.TreeItemCollapsibleState.None);
      item.iconPath = new vscode.ThemeIcon('warning');
      item.tooltip = node.tooltip;
      return item;
    }

    const collapsible = node.children
      ? vscode.TreeItemCollapsibleState.Collapsed
      : vscode.TreeItemCollapsibleState.None;

    const item = new vscode.TreeItem(node.label, collapsible);

    if (node.filePath) {
      item.command = {
        command: 'acai.openFile',
        title: 'Abrir archivo',
        arguments: [node.filePath],
      };
      item.tooltip = node.filePath;
    }

    if (node.kind === 'create-module') {
      item.command = {
        command: 'acai.createModule',
        title: 'Crear Módulo',
      };
      item.iconPath = new vscode.ThemeIcon('add');
    }

    if (node.moduleId) {
      item.description = node.moduleId;
      item.contextValue = 'moduleWithId';
    }

    if (node.kind === 'root-hooks') {
      item.iconPath = new vscode.ThemeIcon('zap');
    } else if (node.kind === 'root-sections') {
      item.iconPath = new vscode.ThemeIcon('symbol-structure');
    } else if (node.kind === 'root-modules') {
      item.iconPath = new vscode.ThemeIcon('package');
    } else if (node.kind === 'category') {
      item.iconPath = new vscode.ThemeIcon('folder');
    } else if (node.kind === 'hook') {
      item.iconPath = new vscode.ThemeIcon('file-code');
    } else if (node.kind === 'section' || node.kind === 'module') {
      item.iconPath = new vscode.ThemeIcon('symbol-class');
    } else if (node.kind === 'section-file' || node.kind === 'module-file') {
      item.iconPath = getFileIcon(node.label);
    }

    return item;
  }

  getChildren(node?: TreeNode): TreeNode[] {
    if (!node) {
      return this.getRoots();
    }
    if (node.children) {
      return node.children;
    }
    return [];
  }

  // ── Build tree ──

  private getRoots(): TreeNode[] {
    if (this._dockerRunning === null) {
      return [{ kind: 'message', label: 'Comprobando Docker...', tooltip: 'Consultando estado del contenedor' }];
    }

    if (!this._dockerRunning) {
      return [
        { kind: 'message', label: 'Web no levantada', tooltip: 'Levanta la web desde Docker Web GUI para trabajar con ella' },
        { kind: 'message', label: 'Levanta la web y pulsa refrescar', tooltip: 'Usa el botón de refrescar en la barra del panel' },
      ];
    }

    return [
      this.buildHooksRoot(),
      this.buildSectionsRoot(),
      this.buildModulesRoot(),
    ];
  }

  // ── HOOKS ──

  private buildHooksRoot(): TreeNode {
    const children: TreeNode[] = [];

    if (fs.existsSync(this.hooksPath)) {
      const files = fs.readdirSync(this.hooksPath)
        .filter(f => f.startsWith('hooks.') && f.endsWith('.php'))
        .sort();

      for (const file of files) {
        const name = file.replace(/^hooks\./, '').replace(/\.php$/, '');
        children.push({
          kind: 'hook',
          label: name,
          filePath: path.join(this.hooksPath, file),
        });
      }
    }

    return {
      kind: 'root-hooks',
      label: `HOOKS (${children.length})`,
      children,
    };
  }

  // ── SECCIONES GENERALES ──

  private buildSectionsRoot(): TreeNode {
    const children: TreeNode[] = [];

    if (fs.existsSync(this.modulesPath)) {
      const dirs = fs.readdirSync(this.modulesPath)
        .filter(d => d.startsWith('custom-'))
        .sort();

      for (const dir of dirs) {
        const dirPath = path.join(this.modulesPath, dir);
        if (!fs.statSync(dirPath).isDirectory()) { continue; }

        const displayName = capitalize(dir.replace('custom-', ''));
        const fileChildren = getModuleFiles(dirPath);

        children.push({
          kind: 'section',
          label: displayName,
          dirPath,
          moduleId: dir,
          children: fileChildren.map(f => ({
            kind: 'section-file' as NodeKind,
            label: f.display,
            filePath: f.path,
          })),
        });
      }
    }

    return {
      kind: 'root-sections',
      label: `SECCIONES GENERALES (${children.length})`,
      children,
    };
  }

  // ── MÓDULOS ──

  private buildModulesRoot(): TreeNode {
    const categories = new Map<string, TreeNode[]>();
    let totalModules = 0;

    if (fs.existsSync(this.modulesPath)) {
      const dirs = fs.readdirSync(this.modulesPath)
        .filter(d => !d.startsWith('custom-'))
        .sort();

      for (const dir of dirs) {
        const dirPath = path.join(this.modulesPath, dir);
        if (!fs.statSync(dirPath).isDirectory()) { continue; }

        const { category, displayName } = getModuleInfo(dirPath, dir);
        const fileChildren = getModuleFiles(dirPath);

        if (fileChildren.length === 0) { continue; }

        totalModules++;
        const moduleNode: TreeNode = {
          kind: 'module',
          label: displayName,
          dirPath,
          moduleId: dir,
          children: fileChildren.map(f => ({
            kind: 'module-file' as NodeKind,
            label: f.display,
            filePath: f.path,
          })),
        };

        const existing = categories.get(category);
        if (existing) {
          existing.push(moduleNode);
        } else {
          categories.set(category, [moduleNode]);
        }
      }
    }

    // Sort categories: known first, OTROS last
    const order = ['GENERAL', 'INICIO', 'BASE', 'RESERVAS'];
    const sorted = [...categories.entries()].sort((a, b) => {
      const ai = order.indexOf(a[0]);
      const bi = order.indexOf(b[0]);
      if (ai !== -1 && bi !== -1) { return ai - bi; }
      if (ai !== -1) { return -1; }
      if (bi !== -1) { return 1; }
      if (a[0] === 'OTROS') { return 1; }
      if (b[0] === 'OTROS') { return -1; }
      return a[0].localeCompare(b[0]);
    });

    const categoryNodes: TreeNode[] = [
      { kind: 'create-module', label: '+ Crear Módulo' },
    ];

    for (const [cat, modules] of sorted) {
      categoryNodes.push({
        kind: 'category',
        label: `${cat} (${modules.length})`,
        children: modules,
      });
    }

    return {
      kind: 'root-modules',
      label: `MÓDULOS (${totalModules})`,
      children: categoryNodes,
    };
  }
}

// ── Helpers ──

interface ModuleFile {
  display: string;
  path: string;
}

function getModuleFiles(dirPath: string): ModuleFile[] {
  const files: ModuleFile[] = [];
  const map: [string, string][] = [
    ['index-base.tpl', 'index.html'],
    ['script.js', 'script.js'],
    ['style.css', 'style.css'],
    ['hook.php', 'hook.php'],
  ];

  for (const [real, display] of map) {
    const fullPath = path.join(dirPath, real);
    if (fs.existsSync(fullPath)) {
      files.push({ display, path: fullPath });
    }
  }

  return files;
}

function getModuleInfo(dirPath: string, dirName: string): { category: string; displayName: string } {
  const builderPath = path.join(dirPath, 'builder.json');

  if (fs.existsSync(builderPath)) {
    try {
      const data = JSON.parse(fs.readFileSync(builderPath, 'utf-8'));
      const label: string = data.label || '';

      if (label.includes('/')) {
        const parts = label.split('/');
        const category = parts[0].trim().toUpperCase();
        const displayName = parts.slice(1).join('/').trim();
        return { category, displayName };
      }
    } catch {
      // fall through
    }
  }

  return {
    category: 'OTROS',
    displayName: cleanDirName(dirName),
  };
}

function cleanDirName(name: string): string {
  const cleaned = name.replace(/_[a-z0-9]{4,8}$/, '');
  return capitalize(cleaned);
}

function capitalize(str: string): string {
  if (!str) { return str; }
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function getFileIcon(label: string): vscode.ThemeIcon {
  if (label.endsWith('.html')) { return new vscode.ThemeIcon('file-code'); }
  if (label.endsWith('.js')) { return new vscode.ThemeIcon('symbol-event'); }
  if (label.endsWith('.css')) { return new vscode.ThemeIcon('symbol-color'); }
  if (label.endsWith('.php')) { return new vscode.ThemeIcon('symbol-method'); }
  return new vscode.ThemeIcon('file');
}
