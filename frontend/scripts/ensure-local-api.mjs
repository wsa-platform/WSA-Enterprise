/**
 * Ensures the Vite-proxied Laravel API gateway is reachable on :8079.
 * Home scientific search POSTs to /api/v1/public/research-agent/query via this proxy.
 */
import { spawn } from 'node:child_process'
import { existsSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..')
const HEALTH_URL = 'http://127.0.0.1:8079/api/v1/health'
const COMPOSE_SERVICES = ['postgres', 'redis', 'backend', 'frontend', 'nginx']

function log(message) {
  console.log(`[ensure-local-api] ${message}`)
}

function run(command, args, { ignoreStdio = false } = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd: repoRoot,
      shell: process.platform === 'win32',
      stdio: ignoreStdio ? 'ignore' : 'inherit',
    })
    child.on('error', reject)
    child.on('exit', (code) => {
      if (code === 0) {
        resolve()
        return
      }
      reject(new Error(`${command} ${args.join(' ')} exited ${code}`))
    })
  })
}

async function isApiHealthy() {
  try {
    const response = await fetch(HEALTH_URL, { signal: AbortSignal.timeout(4000) })
    if (!response.ok) {
      return false
    }
    const payload = await response.json().catch(() => null)
    return payload?.status === 'ok' || response.ok
  } catch {
    return false
  }
}

async function isDockerReady() {
  try {
    await run('docker', ['info'], { ignoreStdio: true })
    return true
  } catch {
    return false
  }
}

function dockerDesktopCandidates() {
  const localAppData = process.env.LOCALAPPDATA ?? ''
  return [
    path.join(localAppData, 'Programs', 'DockerDesktop', 'Docker Desktop.exe'),
    path.join(process.env['ProgramFiles'] ?? 'C:\\Program Files', 'Docker', 'Docker', 'Docker Desktop.exe'),
  ].filter((candidate) => existsSync(candidate))
}

async function startDockerDesktop() {
  const exe = dockerDesktopCandidates()[0]
  if (!exe) {
    throw new Error(
      'Docker Desktop is not running and Docker Desktop.exe was not found. Start Docker Desktop, then retry npm run dev.',
    )
  }
  log(`Starting Docker Desktop: ${exe}`)
  const child = spawn(exe, [], {
    detached: true,
    stdio: 'ignore',
    windowsHide: true,
  })
  child.unref()
}

async function waitFor(label, check, attempts, delayMs) {
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    if (await check()) {
      return
    }
    log(`Waiting for ${label} (${attempt}/${attempts})...`)
    await new Promise((resolve) => setTimeout(resolve, delayMs))
  }
  throw new Error(`Timed out waiting for ${label}.`)
}

if (await isApiHealthy()) {
  log('API gateway already healthy on http://127.0.0.1:8079')
  process.exit(0)
}

log('API gateway on :8079 is not reachable. Bringing up the Docker Compose stack.')

if (!(await isDockerReady())) {
  await startDockerDesktop()
  await waitFor('Docker engine', isDockerReady, 60, 2000)
}

await run('docker', ['compose', 'up', '-d', ...COMPOSE_SERVICES])
await waitFor('http://127.0.0.1:8079/api/v1/health', isApiHealthy, 60, 2000)
log('API gateway is healthy on http://127.0.0.1:8079')
