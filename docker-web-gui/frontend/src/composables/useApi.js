/**
 * API wrapper — replaces the global api() function from vanilla JS.
 * In WebView mode, window.__DOCKER_GUI_URL__ provides the base URL (e.g. http://localhost:9090).
 * In dev mode (Vite proxy), it stays empty so paths remain relative.
 */
const BASE = window.__DOCKER_GUI_URL__ || ''

export async function api(path, opts = {}) {
  const res = await fetch(BASE + path, {
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  })
  return res.json()
}

export function apiPost(path, data) {
  return api(path, {
    method: 'POST',
    body: JSON.stringify(data),
  })
}
