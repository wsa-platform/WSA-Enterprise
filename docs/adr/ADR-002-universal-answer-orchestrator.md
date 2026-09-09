# ADR-002: Universal Answer Orchestrator

**Status:** Accepted / Implemented
**Date:** 2026-09-09

## Context

Scientific synthesis alone can mark answers insufficient even when general web evidence could still help users. Eligibility must be split so web-sufficient answers remain displayable.

## Decision

### Pipeline

```
QUS → plan → select providers → retrieve → normalize → dedupe
  → conflict detection → web consensus → scientific fusion
  → validation (existing ASVS/Matcher) → eligibility → compose
```

Implemented as `UniversalAnswerOrchestrator`, wired through `AgriculturalIntelligenceServiceProvider`, and optionally enriching `AgriculturalResearchAgent` synthesis when `UNIVERSAL_ANSWER_ORCHESTRATOR_ENABLED` and `UNIVERSAL_ANSWER_ENRICH_LEGACY` are true. Full multi-source entrypoint: `AgriculturalResearchAgent::answerUniversal()` (service-level; HTTP remains legacy synthesize/query enrichment).

### Eligibility (NON-NEGOTIABLE)

| Flag | Meaning |
|------|---------|
| `web_answer_eligible` | Usable general-web evidence / consensus |
| `scientific_answer_eligible` | Strict scientific synthesis sufficiency |
| `overall_answer_eligible` | `scientific_answer_eligible OR web_answer_eligible` |

**Statuses**

- `SCIENTIFIC_VERIFIED` — scientific eligible
- `WEB_SUPPORTED_SCIENTIFIC_LIMITED` — web + partial scientific support
- `GENERAL_WEB` — scientific insufficient, web sufficient → **answer still displayed**
- `INSUFFICIENT` — neither eligible

`scientific_answer_eligible=false` MUST NOT force `overall_answer_eligible=false` when `web_answer_eligible=true`.

### Evidence fusion

`EvidenceFusionService` separates:

- architectural confidence
- provider/model confidence
- scientific confidence

`WebConsensusService` computes ranges, agreement, quality-weighted representative value (not first-hit), and conflicts.

### Final answer contract (backward compatible)

Extended fields (additive):

- `answer_status`, `web_answer_eligible`, `scientific_answer_eligible`, `overall_answer_eligible`
- `web_citations` / `scientific_citations` / `citations`
- `limitations`, `providers_used`, `evidence_summary`
- `universal_orchestrator` observability block

Legacy Stage 5 fields remain.

## Consequences

- Existing research-agent consumers keep working; enrichment is feature-flagged and failure-isolated.
- Full multi-source path available via `AgriculturalResearchAgent::answerUniversal()`.
- Web is never treated as Semantic Scholar / FAO.

## Related

- ADR-001 provider/adapter architecture
- Existing Internet-First research ADR under `docs/architecture/`
