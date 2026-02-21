import { JSDOM } from 'jsdom';
import * as vm from 'vm';
import * as https from 'https';
import { log } from './extension';

// Cache del parser y scripts
let appParserCache: any = null;
let windowCache: any = null;

const REMOTE_SCRIPTS = [
  'https://cms.cocosolution.com/lib/plugins/builder_saas/js/lexer.js',
  'https://cms.cocosolution.com/lib/plugins/builder_saas/js/mixins/vuecomponents.js',
  'https://cms.cocosolution.com/lib/plugins/builder_saas/js/mixins/builderdata.js',
  'https://cms.cocosolution.com/lib/plugins/builder_saas/js/mixins/filters.js',
  'https://cms.cocosolution.com/lib/plugins/builder_saas/js/parseDocument.js',
];

function fetchScript(url: string): Promise<string> {
  return new Promise((resolve, reject) => {
    https.get(url, { timeout: 10000 }, (res) => {
      let data = '';
      res.on('data', (chunk: Buffer) => { data += chunk.toString(); });
      res.on('end', () => {
        if (res.statusCode && res.statusCode >= 200 && res.statusCode < 300) {
          resolve(data);
        } else {
          reject(new Error(`HTTP ${res.statusCode} fetching ${url}`));
        }
      });
    }).on('error', reject);
  });
}

async function loadRemoteParser(): Promise<{ appParser: any; window: any }> {
  if (appParserCache) {
    return { appParser: appParserCache, window: windowCache };
  }

  const dom = new JSDOM('<!DOCTYPE html><html><body></body></html>', {
    runScripts: 'dangerously',
    resources: 'usable',
  });

  const window = dom.window as any;

  if (!window.btoa) {
    window.btoa = (str: string) => Buffer.from(str).toString('base64');
  }
  if (!window.atob) {
    window.atob = (str: string) => Buffer.from(str, 'base64').toString();
  }

  if (!window.bus) {
    window.bus = { $emit: () => {}, $on: () => {}, $off: () => {} };
  }

  const context = vm.createContext({
    window,
    document: window.document,
    DOMParser: window.DOMParser,
    console,
    Buffer,
    setTimeout,
    setInterval,
    clearTimeout,
    clearInterval,
  });

  for (const scriptUrl of REMOTE_SCRIPTS) {
    const scriptContent = await fetchScript(scriptUrl);
    vm.runInContext(scriptContent, context);
  }

  if (!window.appParser) {
    throw new Error('appParser no se cargó correctamente desde los scripts remotos');
  }

  appParserCache = window.appParser;
  windowCache = window;

  return { appParser: appParserCache, window: windowCache };
}

export interface ParseResult {
  htmlParsed: string;
  vars: any;
}

/**
 * Parsea HTML base (index-base.tpl) y genera htmlParsed (Twig) + vars editables.
 * Replica el flujo de acai-code save.js:
 *   1. generateBuilderVars(html, 2, previousSchema) → vars
 *   2. parseComponents(html, prefixVar, 2) → htmlParsed (con módulos, c-if, c-for, etc.)
 */
export async function parseHtml(html: string, previousSchema?: any, moduleIds?: string[], listTables?: string[]): Promise<ParseResult> {
  const { appParser, window } = await loadRemoteParser();

  // Setear globals — parseDocument.js usa `for (const module in window.allModules)`
  // por lo que necesita un OBJETO con IDs como keys, no un array
  const modulesObj: Record<string, boolean> = {};
  for (const id of (moduleIds || [])) { modulesObj[id] = true; }
  window.allModules = modulesObj;
  window.tables = listTables || [];

  log.info(`Parser globals: allModules=${window.allModules.length} items, tables=${window.tables.length} items`);
  if (window.allModules.length > 0) {
    log.info(`Primeros módulos: ${window.allModules.slice(0, 10).join(', ')}`);
  }

  // Paso 1: Generar builder vars
  const safePreviousSchema = previousSchema || {};
  const result = appParser.generateBuilderVars(html, 2, safePreviousSchema);

  // Paso 2: Parsear componentes (módulos embebidos, c-if, c-for, etc.) → Twig
  let htmlParsed = result.codeParsed;
  try {
    log.info('Llamando a appParser.parseComponents...');
    htmlParsed = appParser.parseComponents(html, '', 2);
    log.info(`parseComponents OK — ${htmlParsed.length}c`);
  } catch (err: any) {
    log.error(`parseComponents FALLÓ: ${err.message}\n${err.stack}`);
    log.info('Usando codeParsed de generateBuilderVars como fallback');
  }

  return {
    htmlParsed,
    vars: result.codeVars,
  };
}

/**
 * Limpia la cache del parser (fuerza recarga de scripts remotos)
 */
export function clearParserCache(): void {
  appParserCache = null;
  windowCache = null;
}
