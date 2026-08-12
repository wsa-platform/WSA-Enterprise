# Phase 15 M15 — Enterprise Platform Verification

**Branch:** `phase-15-m15-enterprise-platform`
**Baseline:** M14 on `main`

## Deliverables

| Capability | Backend | Frontend | Tests |
| --- | --- | --- | --- |
| `monitoring.view` permission | Yes | Monitoring nav/page | Yes |
| User invitations | Yes | Users page | Yes |
| Session management | Yes | Settings page | Yes |
| Analytics overview UI | Existing API | Yes | Regression |
| API clients admin UI | Existing API | Yes | Regression |

## Test suite

- `Phase15M15EnterprisePlatformTest` (@group security)
- M14/M13 regression suites unchanged

## Known limitations

- Invitation delivery is in-app token/link only (no email pipeline)
- Mobile admin parity deferred
- Mock AI/billing providers unchanged
