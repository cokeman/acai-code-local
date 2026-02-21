import * as fs from 'fs';
import * as path from 'path';

export interface AcaiConfig {
  domain: string;
  ssl: boolean;
  token: string;
}

export interface DockerEnv {
  dbUsername: string;
  dbPassword: string;
  dbDatabase: string;
}

export interface TunnelConfig {
  tunnelHost: string;
  tunnelPort: string;
  boreDbHost: string;
  boreDbPort: string;
}

export interface ProjectConfig {
  acai: AcaiConfig;
  docker: DockerEnv;
  tunnel: TunnelConfig;
  workspaceRoot: string;
  modulesPath: string;
  hooksPath: string;
  schemasPath: string;
}

function readJsonFile(filePath: string): any {
  const content = fs.readFileSync(filePath, 'utf-8');
  return JSON.parse(content);
}

function parseHostPort(value: string): { host: string; port: string } {
  const trimmed = value.trim();
  const lastColon = trimmed.lastIndexOf(':');
  if (lastColon === -1) {
    return { host: trimmed, port: '' };
  }
  return {
    host: trimmed.substring(0, lastColon),
    port: trimmed.substring(lastColon + 1),
  };
}

function parseEnvFile(filePath: string): Record<string, string> {
  const content = fs.readFileSync(filePath, 'utf-8');
  const vars: Record<string, string> = {};
  for (const line of content.split('\n')) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) { continue; }
    const eqIndex = trimmed.indexOf('=');
    if (eqIndex === -1) { continue; }
    const key = trimmed.substring(0, eqIndex).trim();
    const value = trimmed.substring(eqIndex + 1).trim();
    vars[key] = value;
  }
  return vars;
}

export function readProjectConfig(workspaceRoot: string): ProjectConfig {
  const acaiPath = path.join(workspaceRoot, '.acai');
  const acaiData = readJsonFile(acaiPath);
  const acai: AcaiConfig = {
    domain: acaiData.domain,
    ssl: acaiData.ssl ?? true,
    token: acaiData.token,
  };

  const envPath = path.join(workspaceRoot, '.docker', '.env');
  const envVars = parseEnvFile(envPath);
  const docker: DockerEnv = {
    dbUsername: envVars['DB_USERNAME'] || '',
    dbPassword: envVars['DB_PASSWORD'] || '',
    dbDatabase: envVars['DB_DATABASE'] || '',
  };

  const tunnelPath = path.join(workspaceRoot, '.docker', 'tunnel-url.txt');
  const tunnelRaw = fs.readFileSync(tunnelPath, 'utf-8');
  const tunnelParsed = parseHostPort(tunnelRaw);

  const borePath = path.join(workspaceRoot, '.docker', 'bore-db-url.txt');
  const boreRaw = fs.readFileSync(borePath, 'utf-8');
  const boreParsed = parseHostPort(boreRaw);

  const tunnel: TunnelConfig = {
    tunnelHost: tunnelParsed.host,
    tunnelPort: tunnelParsed.port,
    boreDbHost: boreParsed.host,
    boreDbPort: boreParsed.port,
  };

  return {
    acai,
    docker,
    tunnel,
    workspaceRoot,
    modulesPath: path.join(workspaceRoot, 'template', 'estandar', 'modulos'),
    hooksPath: path.join(workspaceRoot, 'hooks'),
    schemasPath: path.join(workspaceRoot, 'cms', 'data', 'schema'),
  };
}
