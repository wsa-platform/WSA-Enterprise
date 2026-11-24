# 06 — G2-15 vs G4-02 Comparison

| Criterion | G2-15 Andalasian Livestock | G4-02 KAIKObase |
|---|---|---|
| Scientific Scope | Livestock / animal science journal | Silkworm (*Bombyx mori*) genome DB |
| Livestock Coverage | Verified (journal scope) | **Not livestock production** — insect genomics |
| Access | Verified OAI-PMH (2026-11-24) | Web/download site; no OAI livestock API |
| OAI-PMH/API | Verified | N/A for livestock articles |
| Metadata Quality | oai_dc records with DOI/title/abstract | Genome annotations |
| Full Text | OA PDFs via journal | Sequence downloads |
| License | CC BY-NC-SA 4.0 (verified) | NARO materials; commercial reuse unclear |
| LocalIndex Compatibility | Compatible *if* license cleared | Different data model (genome) |
| Operational Cost | Free OA endpoint | Free downloads |

## Decision

- **G2-15:** Scientifically suitable for livestock; access OK; **license blocks commercial activation** → `BLOCKED_LICENSE`.
- **G4-02:** **Not selected** as livestock alternative — wrong scientific scope (silkworm genome ≠ livestock husbandry coverage target).

`ALTERNATIVE_SELECTED` is **not** used (no alternative chosen for onboarding).
