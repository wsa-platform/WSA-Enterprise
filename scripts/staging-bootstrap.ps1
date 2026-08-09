# WSA-Enterprise — Docker staging bootstrap (Windows PowerShell)
# Prerequisites: Docker Desktop with WSL2 backend enabled.
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Error "Docker is not installed or not on PATH. Install Docker Desktop and enable the WSL2 backend."
}

$envFile = Join-Path $repoRoot "backend\.env"
$envExample = Join-Path $repoRoot "backend\.env.example"

if (-not (Test-Path $envFile)) {
    Copy-Item $envExample $envFile
    Write-Host "Created backend/.env from .env.example"
}

Write-Host "Building images..."
docker compose build

Write-Host "Starting postgres and redis..."
docker compose up -d postgres redis

Write-Host "Waiting for postgres to become healthy..."
$deadline = (Get-Date).AddMinutes(3)
do {
    $health = docker inspect --format "{{.State.Health.Status}}" (docker compose ps -q postgres 2>$null) 2>$null
    if ($health -eq "healthy") { break }
    if ((Get-Date) -gt $deadline) {
        Write-Error "Postgres did not become healthy within 3 minutes."
    }
    Start-Sleep -Seconds 3
} while ($true)

Write-Host "Generating APP_KEY if missing..."
docker compose run --rm --no-deps backend php artisan key:generate --force

Write-Host "Starting full stack..."
docker compose up -d --build

Write-Host "Running migrations and demo seeders..."
docker compose exec backend php artisan migrate --seed --force

Write-Host ""
Write-Host "Staging stack is ready at http://localhost:8081"
Write-Host "Health:  curl http://localhost:8081/api/v1/health"
Write-Host "Login:   admin@wsa.test / password"
