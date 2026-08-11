# Phase 12 M12.5 — Rollback & Production Verification

**Date:** 2026-08-11
**Branch:** `phase-12-m12-5-rollback-production-verification` (merged via PR #19)
**Merge commit:** `e4eac69`
**Feature commit:** `c42b981`
**Scope:** Production backup, rollback, and verification scripts with safety tests

---

## Deliverables

| Item | Location |
| --- | --- |
| Backup script | `scripts/backup-production.sh` |
| Rollback script | `scripts/rollback-production.sh` |
| Verification script | `scripts/verify-production.sh` |
| Backup & restore guide | `docs/phase-12-m12-5-backup-and-rollback.md` |
| Rollback runbook | `docs/phase-12-m12-5-rollback-runbook.md` |
| Automated tests | `backend/tests/Feature/Phase12M125ProductionOpsTest.php` |

---

## Script behavior

### `backup-production.sh`

- Uses `pg_dump` via the running `postgres` Compose service.
- Supports `DRY_RUN=1` — validates configuration without creating a dump.
- Output naming: `{BACKUP_DIR}/wsa_enterprise_{POSTGRES_DB}_{UTC_TIMESTAMP}.sql.gz`
- **Never** prints `POSTGRES_PASSWORD` or other secrets.
- Exits non-zero on configuration or backup failure.

### `rollback-production.sh`

- Requires explicit `IMAGE_TAG` environment variable.
- **Rejects** mutable `main` tag and non-immutable targets.
- Redeploys previous GHCR images via `deploy-production.sh` pattern.
- **Does not** auto-restore database or run destructive operations.

### `verify-production.sh`

Eleven non-destructive checks:

1. HTTPS legacy health (`/api/v1/health`)
2. HTTPS liveness (`/api/v1/health/live`)
3. HTTPS readiness (`/api/v1/health/ready`)
4. Laravel `/up`
5. Container health: `backend`, `frontend`, `nginx`, `queue`, `scheduler`
6. PostgreSQL `pg_isready`
7. Redis `PING`

Requires `DOMAIN` in `.env`. Does not print secrets.

---

## Safety boundaries

| Rule | Enforcement |
| --- | --- |
| No secret disclosure in script output | Tested in `Phase12M125ProductionOpsTest` |
| No automatic DB restore | Documented; no restore script in M12.5 |
| Immutable rollback tags only | Rollback script rejects `main` and unsafe tags |
| No production data deletion | Scripts are read/backup/deploy only |
| Backup before rollback (operator) | Documented in runbook — not automated |

---

## Automated tests (`Phase12M125ProductionOpsTest`)

| Test | Coverage |
| --- | --- |
| `test_backup_script_dry_run_validates_required_configuration` | `DRY_RUN=1` success path |
| `test_backup_script_fails_when_required_configuration_is_missing` | Missing password fails |
| `test_backup_script_does_not_print_secrets` | Secret non-disclosure |
| `test_rollback_script_requires_explicit_image_tag` | Missing tag rejected |
| `test_rollback_script_refuses_mutable_main_tag` | `main` tag rejected |
| `test_rollback_script_refuses_unsafe_non_immutable_target` | Unsafe tag rejected |
| `test_rollback_script_prints_explicit_target_before_deploy_actions` | Target visibility |
| `test_verify_production_script_requires_domain` | Missing `DOMAIN` fails |
| `test_production_scripts_exist_and_are_non_empty` | Script presence |

**Test count:** 9 tests (included in full backend CI suite).

---

## Verification results

| Check | Result | Evidence |
| --- | --- | --- |
| `Phase12M125ProductionOpsTest` | **PASS** | CI backend job on PR #19 |
| Full backend suite | **PASS** | 171 tests, 704 assertions (CI run `31488373975`) |
| Security group | **PASS** | 26 tests, 78 assertions |
| OpenAPI validate | **PASS** | CI openapi job |
| Docker prod + GHCR compose config | **PASS** | CI docker-validate job |
| Frontend lint + build | **PASS** | CI frontend job |
| Mobile analyze + test | **PASS** | CI mobile job |
| Local Docker test re-run (closure) | **SKIPPED** | Docker daemon unavailable locally |

### CI evidence (PR #19 — merge run `31488373975`)

All 6 CI jobs: **SUCCESS** (backend, frontend, mobile, openapi, security, docker-validate).

---

## Backup / restore expectations

- **Backup:** Operator runs `backup-production.sh` before deploy or rollback (documented checklists).
- **Restore:** Manual operator action only — see [phase-12-m12-5-backup-and-rollback.md](phase-12-m12-5-backup-and-rollback.md).
- **Pre-deploy:** `DRY_RUN=1` optional validation, then full backup.
- **Post-deploy:** `verify-production.sh` + `smoke-production.sh`.

---

## Production host verification

**N/A — no production/staging host verification performed.**

Repository and CI validation passed. Operator must run `verify-production.sh`, `smoke-production.sh`, and `DRY_RUN=1 ./scripts/backup-production.sh` on the production host before first live deploy.

---

## Known limitations

1. No automated database restore script — manual `psql` restore only.
2. Rollback redeploys application images only — schema/data rollback is operator-managed.
3. `verify-production.sh` requires HTTPS endpoint reachable (production host with valid TLS).
4. Script tests run via `bash` in Docker test profile with repo mount (`/var/www/repo`).
5. Certbot post-renewal nginx reload is manual — see [tls-production.md](tls-production.md).

---

## Acceptance status

**M12.5: COMPLETE** — merged to `main` via PR #19. Scripts, runbooks, and automated tests verified in CI.
