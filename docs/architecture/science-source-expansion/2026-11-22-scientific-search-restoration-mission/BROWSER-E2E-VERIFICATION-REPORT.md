# Browser E2E Verification Report

## Purpose
Prove Home UI renders ClaimAttributionLedger, citations, and failure/status metadata.

## Status: VERIFIED

### Command
```
cd frontend/e2e-rev65
WSA_E2E_BASE_URL=http://127.0.0.1:8079
npx playwright test tests/claim-attribution-ledger.spec.ts
```

### Result
**2 passed** (13.8s) after frontend image rebuild including ledger UI.

| Test | Result |
|------|--------|
| Ledger edges + citations + status metadata | PASSED |
| Insufficient failure class (not silent empty) | PASSED |

### Scope
- Real browser (Playwright / Edge channel)
- API mocked for deterministic contract (no paid APIs)
- Does **not** alone prove live multi-source scientific L7

### Prerequisite
Frontend Docker image rebuilt so `home-research-claim-attribution-ledger` testids ship.
