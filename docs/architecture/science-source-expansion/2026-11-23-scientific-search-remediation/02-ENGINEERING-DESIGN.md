# 02-ENGINEERING-DESIGN

## Design principles

1. ADR-022: CSQ surfaces stay source-language; English is retrieval compilation only.
2. ADR-023: Membership ≠ capability ≠ eligibility ≠ activation; language capability is a new orthogonal axis (ISS-SRC-002).
3. Do not weaken relevance/validation; do not global OR FTS.

## Integrated solution

| Issue | Design |
|---|---|
| ISS-AR-003 | Prefer irrigation intent when `asksExplicitIrrigationOrWaterRequirement`; sense also checks that helper (not only water factor). |
| ISS-AR-004 | Add `organic`/`soil` topic factors + English labels; intent maps to soil_management. |
| ISS-AR-001/006/FTS | `compileTerm` never returns Arabic; `joinTerms` strips Arabic; CSQ `specExecutable*` use compileTerm + English fallbacks; AGRIS/OE specializers prefer scientificName/cropId and strip Arabic tokens. |
| ISS-AR-002 | Multilingual organic cues in `GovernedScientificSourceEligibility`. |
| ISS-AR-005 | Label Latin excerpts with `source_language_excerpt` phrase in answer_language. |
| ISS-EVD-002 | `EvidencePipelineStatus` canonical contract + alias canonicalize. |
| ISS-SRC-002 | `ScientificSourceLanguageCapability` + Catalog for 10 production keys. |
| ISS-TEST-001 | Rewrite `ScientificResearchLanguageContractTest` to R2. |

## Alternatives rejected

- Translate-all Arabic questions to English as semantic authority — violates ADR-022.
- Global FTS OR — loses precision.
- Plant-specific hardcodes — forbidden.
