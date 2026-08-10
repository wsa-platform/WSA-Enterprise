# Run backend tests against an isolated test database (never staging wsa_enterprise).
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Error "Docker is required. Install Docker Desktop and start the daemon."
}

Write-Host "Ensuring isolated test database exists..."
docker compose exec -T postgres psql -U wsa -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = 'wsa_enterprise_test'" | Select-String -Pattern "1" -Quiet | Out-Null
if (-not $?) {
    docker compose exec -T postgres psql -U wsa -d postgres -c "CREATE DATABASE wsa_enterprise_test;"
}

Write-Host "Running PHPUnit in isolated test container..."
docker compose --profile test run --rm backend-test
