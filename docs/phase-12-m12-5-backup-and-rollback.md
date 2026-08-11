# Phase 12 M12.5 — Production Backup & Restore

**Status:** Implemented (pending verification)  
**Branch:** `phase-12-m12-5-rollback-production-verification`  
**Baseline:** M12.4 merged on `main`

---

## Overview

Production runs on a **single Docker host** with PostgreSQL in the `postgres` Compose service (see `docker-compose.yml` + `docker-compose.prod.yml`). Backups use `pg_dump` via the running container — no direct exposure of credentials in logs.

**Script:** `scripts/backup-production.sh`

---

## Required environment variables

Set in repository root `.env` (never commit real values):

| Variable | Purpose |
|----------|---------|
| `POSTGRES_DB` | Database name (default `wsa_enterprise`) |
| `POSTGRES_USER` | PostgreSQL role (default `wsa`) |
| `POSTGRES_PASSWORD` | Database password (**never printed by scripts**) |
| `PROD_BACKUP_DIR` or `BACKUP_DIR` | Optional backup output directory |

Optional:

| Variable | Purpose |
|----------|---------|
| `DRY_RUN=1` | Validate configuration without creating a dump |

---

## Backup naming convention

```
{BACKUP_DIR}/wsa_enterprise_{POSTGRES_DB}_{UTC_TIMESTAMP}.sql.gz
```

Example:

```
./backups/production/wsa_enterprise_wsa_enterprise_20260811T120000Z.sql.gz
```

- UTC timestamp: `YYYYMMDDTHHMMSSZ`
- Gzip-compressed plain SQL dump
- One file per backup run

---

## Production database backup procedure

1. SSH to the production host and `cd` to the repository clone (e.g. `/opt/wsa-enterprise`).
2. Confirm the stack is healthy: `./scripts/verify-production.sh`
3. Run a dry run (optional):

   ```bash
   DRY_RUN=1 ./scripts/backup-production.sh
   ```

4. Create the backup:

   ```bash
   BACKUP_DIR=/var/backups/wsa ./scripts/backup-production.sh
   ```

5. Confirm output reports a non-zero byte size.

---

## Backup verification procedure

After each backup:

1. **File exists:** `test -f /var/backups/wsa/wsa_enterprise_*.sql.gz`
2. **Non-empty:** `test -s <backup-file>`
3. **Integrity (optional, off-host):**

   ```bash
   gzip -t <backup-file>
   ```

4. **Restore drill (staging only — never on production without approval):**

   ```bash
   gunzip -c <backup-file> | head -n 20   # inspect header only
   ```

5. Record backup path, size, and timestamp in the deployment/rollback log.

---

## Restore procedure (manual — not automated)

**WARNING:** Restore is **manual operator action only**. No M12.5 script performs automatic restore.

1. **Stop write traffic** (maintenance window / scale queue workers down).
2. **Take a fresh backup** of current state before restore.
3. On a **staging** or recovery host first:
   - Create empty database or drop/recreate in isolated environment only.
   - `gunzip -c backup.sql.gz | psql -U ... -d ...`
4. Verify application connectivity and schema version.
5. Production restore requires explicit sign-off and incident record.

Use Compose to exec into postgres for manual restore:

```bash
gunzip -c /var/backups/wsa/<file>.sql.gz | \
  docker compose -f docker-compose.yml -f docker-compose.prod.yml \
  exec -T postgres psql -U "${POSTGRES_USER}" -d "${POSTGRES_DB}"
```

Review migration state after restore — application code tag must match schema expectations.

---

## Retention recommendations

| Tier | Retention | Notes |
|------|-----------|-------|
| Pre-deploy | Keep last 7 daily | Minimum before each production deploy |
| Weekly | 4 weeks | Off-host copy recommended |
| Monthly | 6 months | Compliance / audit requirements |
| Pre-rollback | Mandatory snapshot | Before any rollback operation |

Store backups **off-host** (object storage, separate volume) when possible. Encrypt at rest.

---

## Pre-deployment backup checklist

- [ ] `./scripts/verify-production.sh` passes
- [ ] `DRY_RUN=1 ./scripts/backup-production.sh` succeeds
- [ ] Full backup created and size verified
- [ ] Backup copied off-host (if policy requires)
- [ ] Deploy/rollback log updated with backup filename
- [ ] On-call notified if maintenance window

---

## Post-deployment verification checklist

- [ ] `./scripts/verify-production.sh` passes
- [ ] `./scripts/smoke-production.sh` passes
- [ ] `/api/v1/health/ready` returns 200
- [ ] Queue and scheduler containers healthy
- [ ] No unexpected migration errors in backend logs
- [ ] Monitoring incidents clear or documented

---

## Safety boundaries

- Scripts **never** print `POSTGRES_PASSWORD` or other secrets.
- Scripts **never** delete production data.
- Scripts **never** restore automatically.
- Backup failures exit non-zero.
