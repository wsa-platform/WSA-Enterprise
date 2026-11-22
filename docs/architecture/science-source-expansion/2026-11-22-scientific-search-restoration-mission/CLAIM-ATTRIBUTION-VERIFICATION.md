# Claim Attribution Verification

## Purpose
Verify Claim → Evidence → Source → Provider Record → Citation integrity.

## Status: PARTIAL → VERIFIED (unit + API fields); Browser E2E pending rebuild retest

### Contract
`claim_id → evidence_id → source_key → provider_record_id → citation_id`

### Implemented
- `ClaimAttributionLedger` with L7 flag
- Claim DTO `source_keys` / `attributions`
- Rebind after citations (`rebindClaimsWithCitations`)
- Nested provenance for `provider_record_id`
- Public API root `claim_attribution_ledger`
- Home UI renders ledger rows + status meta (`data-testid`)

### Tests
- `ClaimAttributionLedgerTest` OK
- Frontend `resolveClaimAttributionLedger` unit test added
- Playwright `claim-attribution-ledger.spec.ts` (requires rebuilt frontend image)

### Rules preserved
- `successful_sources` ≠ L7
- Ledger presence ≠ semantic support without matcher/validation path
