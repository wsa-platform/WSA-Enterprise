# 07 — Remediation Record

## Changes

### `AnswerComposer.php`
- `questionRequiresUnavailableDomainEvidence` → early insufficient for exotic domains (e.g. Martian).
- `preferredFindingSentence` instead of blind first sentence.
- `isLowQualityFindingFragment` filters chrome/meta/methods leads.
- `findingAddressesAskedAct` rejects wastewater-only findings for deficit questions.
- Pass-4 recovery from full `evidenceText` when claimText is meta-only.
- Causal/effects heading override (avoid water-requirements heading leak).
- `factItemsFromFindings` keeps scientific prose intact; rejects chrome.
- Updated attributed-excerpt prefix detection to live phrase variants.

### `AnswerComposerPhrases.php`
- Minor phrase alignment for excerpt prefixes (as needed by composer).

### Tests added
- `AnswerComposerLiveQualityGateTest` (3 cases).
- Relies also on existing `AnswerComposerExplanatoryFragmentContractTest` (untracked prior WIP aligned with same gates — included in mission commit).

## Verification after fix

```text
docker compose exec -T backend php artisan test --filter="AnswerComposerLiveQualityGateTest"
→ 3 passed

docker compose exec -T backend php artisan test --filter="ScientificSynthesisEvidenceIntegrationContractTest|ComposerConflictExpressionContractTest|IrrigationGeneralitySemanticMatrixTest|ClaimAttributionLedgerTest|AnswerComposerLiveQualityGateTest|AnswerComposerExplanatoryFragmentContractTest"
→ 55 passed (250 assertions)
```

Live probe v2: EN deficit empirical findings; Martian insufficient; AR improved but not chrome-free.

## Non-goals / not diluted

- Evidence validation thresholds not relaxed.
- No crop-specific hardcoding beyond act/domain linguistic gates.
- No paid APIs.
