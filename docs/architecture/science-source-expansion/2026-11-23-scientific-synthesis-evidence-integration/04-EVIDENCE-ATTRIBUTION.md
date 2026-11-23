# 04 — Evidence Attribution

`ClaimAttributionLedger` unchanged. L7 Claim→Evidence→Source→Citation preserved.

Support relationships continue to use `ClaimEvidenceRelationship`:
- supported ≈ DIRECT_SUPPORT (with directness axis)
- partially_supported ≈ PARTIAL_SUPPORT
- conflicting
- insufficient_evidence

No duplicate status vocabulary introduced on the claim-support axis. Conflict *assessment* uses a separate outcome enum.
