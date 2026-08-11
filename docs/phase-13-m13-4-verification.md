# Phase 13 — M13.4 Verification (Production Client Hardening)

**Milestone:** M13.4 Production Client Hardening  
**Date:** 2026-08-11

---

## Scope delivered

| Item | Status |
| --- | --- |
| React LoginPage — empty defaults when `VITE_SHOW_DEMO_LOGIN=false` | Done |
| Demo hint hidden in production builds | Done |
| `frontend/Dockerfile` + prod compose build args | Done |
| GHCR publish workflow prod build arg | Done |
| Flutter demo hint gated (`SHOW_DEMO_HINT`) | Done |
| `.env.production.example` — `FRONTEND_URL`, CORS | Done |
| `docs/production-secrets.md` — CORS guidance | Done |

---

## Key files

- `frontend/src/pages/LoginPage.tsx`
- `frontend/src/config/loginDemo.ts`
- `frontend/Dockerfile`
- `docker-compose.prod.yml`
- `.env.production.example`
- `mobile/lib/main.dart`
- `.github/workflows/publish-images.yml`

---

## Validation commands

```bash
cd frontend && VITE_SHOW_DEMO_LOGIN=false npm run build
cd frontend && npm run test
cd mobile && flutter analyze && flutter test
```

---

## Known limitation (unchanged)

Bearer token storage in `localStorage` remains a Phase 11 known limitation — **not** addressed in M13.

---

## Sign-off

- [ ] Production build has no embedded demo credentials
- [ ] Dev builds retain demo convenience (`VITE_SHOW_DEMO_LOGIN` unset → dev default)
- [ ] Operator review

**M13.4:** Pending Phase 3 closure approval
