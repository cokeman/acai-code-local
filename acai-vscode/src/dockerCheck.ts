import * as http from 'http';

const SERVER_URL = 'http://localhost:9090/api/projects';

interface ProjectInfo {
  name: string;
  web_url: string;
  acai_domain?: string;
}

function fetchProjects(): Promise<ProjectInfo[]> {
  return new Promise((resolve) => {
    const req = http.get(SERVER_URL, { timeout: 3000 }, (res) => {
      let data = '';
      res.on('data', (chunk: Buffer) => { data += chunk.toString(); });
      res.on('end', () => {
        try {
          const json = JSON.parse(data);
          resolve(json.projects || []);
        } catch {
          resolve([]);
        }
      });
    });
    req.on('error', () => resolve([]));
    req.on('timeout', () => { req.destroy(); resolve([]); });
  });
}

/**
 * Comprueba si el Docker del proyecto está levantado consultando docker-web-gui.
 * Busca un proyecto cuyo acai_domain coincida con el domain del .acai.
 * Retorna la web_url si está running, o null si no.
 */
export async function getProjectStatus(domain: string): Promise<{ running: boolean; webUrl: string | null }> {
  const projects = await fetchProjects();

  for (const proj of projects) {
    if (proj.acai_domain === domain && proj.web_url) {
      return { running: true, webUrl: proj.web_url };
    }
  }

  return { running: false, webUrl: null };
}
