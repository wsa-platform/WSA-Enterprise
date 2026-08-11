# Phase 12 M12.5 — Production Rollback Runbook

**Status:** Implemented (pending verification)  
**Architecture:** Single Docker host, GHCR images, SSH deploy (M12.2)  
**Script:** `scripts/rollback-production.sh`

---

## Rollback triggers

Initiate rollback when one or more apply:

- Post-deploy smoke or `./scripts/verify-production.sh` fails persistently
- Critical application errors introduced by the latest deploy
- Security incident tied to the current release
- Data integrity concerns after a failed migration (assess before rollback)
- On-call / operator decision with documented approval

**Do not rollback** without identifying the target immutable image and recording the incident.

---

## Identify the previous immutable Git SHA / image tag

### From GitHub Actions (publish workflow)

1. Open **Actions → Publish Images** (or equivalent GHCR publish workflow).
2. Find the last **successful** run **before** the faulty deploy.
3. Note the **full commit SHA** published as an image tag.

### From GHCR

Images: `ghcr.io/wsa-platform/wsa-enterprise-{backend,frontend,nginx}`

| Tag type | Example | Rollback use |
|----------|---------|--------------|
| Full git SHA | `a1b2c3d4e5f6...` | **Preferred** — immutable |
| CI tag | `sha-12345` | **Allowed** — immutable run reference |
| `main` | `main` | **Not for rollback** — mutable pointer |

### From production host deploy log

Check the last known good `IMAGE_TAG` recorded during deploy:

```bash
grep IMAGE_TAG /var/log/wsa-deploy.log   # if your ops log exists
```

---

## Redeploy a previous GHCR image

On the production host at the repository clone:

```bash
cd /opt/wsa-enterprise   # or PROD_DEPLOY_PATH

# Required: immutable tag only
IMAGE_TAG=<previous-full-git-sha> ./scripts/rollback-production.sh
```

What the script does (aligned with `scripts/deploy-production.sh`):

1. Validates `DOMAIN`, `IMAGE_TAG` (required, immutable, not `main`)
2. Optional GHCR login via `GHCR_TOKEN`
3. `docker compose pull` with `docker-compose.yml` + `docker-compose.prod.yml` + `docker-compose.ghcr.yml`
4. `docker compose up -d --no-build --wait`
5. **Skips** automatic database migrations
6. Runs `./scripts/verify-production.sh`

**Does NOT:**

- Restore database backups
- Run `migrate:rollback` or destructive SQL
- Default to `main`

---

## Database migration rollback considerations

Rolling back **containers** does not automatically roll back **schema**.

| Scenario | Guidance |
|----------|----------|
| New migration ran, rollback to older code | Older code may not support new schema — assess compatibility before rollback |
| Migration failed mid-deploy | Do not run rollback script until DB state is understood; consider restore from pre-deploy backup |
| Backward-compatible migration | Container rollback may be sufficient |
| Destructive migration | **Do not** rely on image rollback alone — restore from backup (manual) |

**Manual steps (operator):**

1. Take backup: `./scripts/backup-production.sh`
2. Review `php artisan migrate:status` inside backend container
3. Consult Laravel migration docs for safe rollback — **never automated in M12.5**
4. Document decisions in incident record

---

## Application health verification after rollback

```bash
./scripts/verify-production.sh
./scripts/smoke-production.sh
```

Endpoints verified:

| Check | URL / target |
|-------|----------------|
| Legacy health | `GET https://${DOMAIN}/api/v1/health` |
| Liveness | `GET https://${DOMAIN}/api/v1/health/live` |
| Readiness | `GET https://${DOMAIN}/api/v1/health/ready` |
| Laravel up | `GET https://${DOMAIN}/up` |

---

## Frontend verification

- `verify-production.sh` confirms **frontend container health**
- Manually load `https://${DOMAIN}/` in browser
- Confirm static assets and API calls succeed (login smoke if applicable)

---

## Queue verification

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml -f docker-compose.ghcr.yml \
  ps queue
```

- Container status **healthy**
- Logs show `queue:work` running without repeated crash loop
- Optional: dispatch a test job in maintenance window

---

## Scheduler verification

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml -f docker-compose.ghcr.yml \
  ps scheduler
```

- Container status **healthy**
- Logs show `schedule:work` active

---

## nginx / TLS verification

- `verify-production.sh` checks nginx container health
- HTTPS endpoints respond (curl via script)
- Certificate valid: `curl -vI https://${DOMAIN}/ 2>&1 | grep expire`
- Certbot renewal container running (production compose)

For certificate issues, see M12.1 TLS docs and `./scripts/init-letsencrypt.sh` (not part of automated rollback).

---

## Incident recording

After rollback:

1. Create/update monitoring incident (M12.4 `monitoring_events` or ops ticket)
2. Record:
   - Faulty `IMAGE_TAG`
   - Rollback target `IMAGE_TAG`
   - Operator and timestamp
   - Backup file used (if any DB restore)
   - Verification results
3. Audit log / change management ticket updated

---

## Final rollback sign-off

Rollback is complete when:

- [ ] `rollback-production.sh` finished exit 0
- [ ] `verify-production.sh` passed
- [ ] `smoke-production.sh` passed
- [ ] Frontend manually spot-checked
- [ ] Queue and scheduler healthy
- [ ] TLS/HTTPS confirmed
- [ ] Migration/DB state documented
- [ ] Incident recorded
- [ ] On-call / stakeholder notified

**Operator sign-off:** _______________  **Date (UTC):** _______________

---

## Related documentation

- [deploy-production.md](deploy-production.md) — deploy architecture
- [phase-12-m12-5-backup-and-rollback.md](phase-12-m12-5-backup-and-rollback.md) — backup/restore
- [production-secrets.md](production-secrets.md) — secrets handling (M12.3)
