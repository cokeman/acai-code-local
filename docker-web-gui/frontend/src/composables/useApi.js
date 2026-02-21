/**
 * API wrapper — replaces the global api() function from vanilla JS.
 */
export async function api(path, opts = {}) {
  const res = await fetch(path, {
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
