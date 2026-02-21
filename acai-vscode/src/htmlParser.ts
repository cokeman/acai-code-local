import { JSDOM } from 'jsdom';
import * as vm from 'vm';
import * as https from 'https';

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
 * Replica exactamente el flujo de acai-code: remoteParser.js → appParser.generateBuilderVars(code, 2)
 */
export async function parseHtml(html: string, previousSchema?: any): Promise<ParseResult> {
  const { appParser, window } = await loadRemoteParser();

  // Setear globals que el parser necesita (módulos y tablas conocidos)
  // En local no tenemos la lista, pero el parser funciona sin ellos
  window.allModules = window.allModules || [];
  window.tables = window.tables || [];

  const safePreviousSchema = previousSchema || {};
  const result = appParser.generateBuilderVars(html, 2, safePreviousSchema);

  return {
    htmlParsed: result.codeParsed,
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
