/**
 * Bridge para comunicar el frontend con la extensión VSCode cuando corre en WebView.
 * En modo navegador/dev no hace nada.
 */
let vscodeApi = null

if (typeof acquireVsCodeApi === 'function') {
  vscodeApi = acquireVsCodeApi()
}

export function notifyExtension(command, data) {
  if (vscodeApi) {
    vscodeApi.postMessage({ command, ...data })
  }
}
