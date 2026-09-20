# WSA-Enterprise Problem Registry (`#1`–`#31`)

- **Snapshot date:** 2026-09-21
- **Final recovery pass:** 2026-09-21 (Phase 1 final closure — GAP-B)
- **Final GAP-B validation pass:** 2026-09-21 (L1–L5 reconfirmed; no new authoritative definition source found)
- **Code HEAD:** `74861783574b0fd407d964900af2ecc827fe542f`
- **Branch:** `phase-18-m18-ai-marketing-communications`
- **ADR-021 source:** recovered from Git object `1eaced9cb9902d295c2c04c37d529580b4b345e6`
- **Rule:** Do **not** invent problem statements. Phase mapping is from ADR-021 §4 (authoritative). Original definitions for `#1`–`#31` remain **NOT LOCALLY RECOVERABLE** after exhaustive local/project source search.
- **GAP-B forensic result:** Explicit `NOT_LOCALLY_RECOVERABLE` for every `#1`–`#31` is a **valid closure result** (absence proven under permitted search), not an open investigation gap.
- **Related recovered inventories (not substitutes for `#1`–`#31`):** `AUTHORITATIVE-PROBLEM-REGISTRY.md` (NEW-01…, R32/R33)

## Recovery provenance (GAP-B — 2026-09-21)

| Level | Source searched | Result for `#1`–`#31` definitions |
|-------|-----------------|-----------------------------------|
| 1 | ADR-021 @ `1eaced9…` / local recovered file | Phase map + R32/R33 titles only; **no** `#n` statement text |
| 2 | `G:/tmp/wsa-project-engineering-audit/` (Master Forensic, Evidence Lifecycle, Cross-RC, RC-1…RC-4, etc.) | RC-/R- repair IDs; **no** `#1`–`#31` inventory |
| 3 | Git history (`git log` / pickaxe / `1eaced9` tree docs) | ADR-021 maps IDs; **no** definition blobs |
| 4 | `docs/architecture/*`, ADRs, remediation plans | Explicit UNRECOVERED / NOT LOCALLY RECOVERABLE |
| 5 | Prior Phase 1 artifacts + prior read-only subagent search | Same absence reconfirmed |
| 6 | Conversation/Library search (`SearchConversations`) | No embedded original `#1`–`#31` title list |

**Rejected false positive (must not be used as `#1`–`#31`):** ADR-001 section headings `# 1.`…`# 31.` in `docs/adr/ADR-001-provider-adapter-architecture.md` (and corrupted encoding copies under `backend/e2e-tmp/_adr001-*.md`) are **ADR section numbers**, not the remediation problem inventory referenced by ADR-021.

## Phase mapping (ADR-021 — preserved exactly)

| Phase | Covered problem IDs |
|-------|---------------------|
| 1 | All — baseline / forensic confirmation |
| 2 | #2, #3, #4, #12, #13, #16, #31 |
| 3 | #4, #5, #6, #7, #8, #29, #30 |
| 4 | #9, #10, #17 |
| 5 | #11, #12, #13, R32 |
| 6 | #14, #15, #24 |
| 7 | #20, #21, #22, R33 |
| 8 | #18, #19 |
| 9 | #1, #25, #26, #27, #31 |
| 10 | #23, #28 + final verification of all |

## Cross-cutting forensic findings (must not be contradicted)

| Finding | Status | Notes |
|---------|--------|-------|
| RC-1 Latency (~9–15s Home total historically) | PARTIALLY_PROVEN | Stage-3-only ms not fully instrumented historically; RC-L fields on HEAD `7486178`; live remeasure NOT_RUNTIME_VERIFIED |
| RC-2 Comparison entity-B omission | PROVEN (historical) | RC-C commit `e96c34a` on branch/remote; live post-fix residual REQUIRES VERIFICATION |
| RC-3 Evidence / under-retrieval (incl. maize never retrieved historically) | PROVEN (historical) | Cascade from missing entity-B queries; do not erase |
| RC-4 Language / Accept-Language → English answers | PROVEN (historical) | Platform locale `answer_language`; Flutter omits Accept-Language |
| Phase 9 regression | NOT_PROVEN | |
| Crop behavior equivalence | NOT_FULLY_PROVEN | |

## Registry entries `#1`–`#31`

### #1

| Field | Value |
|---|---|
| Problem ID | #1 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 9 row (`#1, #25, #26, #27, #31`) |
| Phase mapping | 9 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | Do not invent from Phase 9 theme (Legacy + WIP + engineering cleanup) |

### #2

| Field | Value |
|---|---|
| Problem ID | #2 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 2 row |
| Phase mapping | 2 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | Do not invent from Phase 2 theme (core contracts / semantic integrity) |

### #3

| Field | Value |
|---|---|
| Problem ID | #3 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 2 row |
| Phase mapping | 2 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #4

| Field | Value |
|---|---|
| Problem ID | #4 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phases 2 and 3 rows |
| Phase mapping | 2, 3 |
| Evidence | ID appears in both Phase 2 and Phase 3 maps; no definition text |
| Notes | Dual-phase mapping is authoritative; definition still absent |

### #5

| Field | Value |
|---|---|
| Problem ID | #5 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 3 row |
| Phase mapping | 3 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #6

| Field | Value |
|---|---|
| Problem ID | #6 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 3 row |
| Phase mapping | 3 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #7

| Field | Value |
|---|---|
| Problem ID | #7 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 3 row |
| Phase mapping | 3 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #8

| Field | Value |
|---|---|
| Problem ID | #8 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 3 row |
| Phase mapping | 3 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #9

| Field | Value |
|---|---|
| Problem ID | #9 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 4 row |
| Phase mapping | 4 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #10

| Field | Value |
|---|---|
| Problem ID | #10 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 4 row |
| Phase mapping | 4 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #11

| Field | Value |
|---|---|
| Problem ID | #11 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 5 row |
| Phase mapping | 5 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #12

| Field | Value |
|---|---|
| Problem ID | #12 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phases 2 and 5 rows |
| Phase mapping | 2, 5 |
| Evidence | Dual-phase map only; no definition text |
| Notes | |

### #13

| Field | Value |
|---|---|
| Problem ID | #13 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phases 2 and 5 rows |
| Phase mapping | 2, 5 |
| Evidence | Dual-phase map only; no definition text |
| Notes | |

### #14

| Field | Value |
|---|---|
| Problem ID | #14 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 6 row |
| Phase mapping | 6 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #15

| Field | Value |
|---|---|
| Problem ID | #15 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 6 row |
| Phase mapping | 6 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #16

| Field | Value |
|---|---|
| Problem ID | #16 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 2 row |
| Phase mapping | 2 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #17

| Field | Value |
|---|---|
| Problem ID | #17 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 4 row |
| Phase mapping | 4 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #18

| Field | Value |
|---|---|
| Problem ID | #18 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 8 row |
| Phase mapping | 8 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #19

| Field | Value |
|---|---|
| Problem ID | #19 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 8 row |
| Phase mapping | 8 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #20

| Field | Value |
|---|---|
| Problem ID | #20 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 7 row |
| Phase mapping | 7 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #21

| Field | Value |
|---|---|
| Problem ID | #21 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 7 row |
| Phase mapping | 7 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #22

| Field | Value |
|---|---|
| Problem ID | #22 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 7 row |
| Phase mapping | 7 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #23

| Field | Value |
|---|---|
| Problem ID | #23 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 10 row |
| Phase mapping | 10 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #24

| Field | Value |
|---|---|
| Problem ID | #24 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 6 row |
| Phase mapping | 6 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #25

| Field | Value |
|---|---|
| Problem ID | #25 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 9 row |
| Phase mapping | 9 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #26

| Field | Value |
|---|---|
| Problem ID | #26 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 9 row |
| Phase mapping | 9 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #27

| Field | Value |
|---|---|
| Problem ID | #27 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 9 row |
| Phase mapping | 9 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #28

| Field | Value |
|---|---|
| Problem ID | #28 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 10 row |
| Phase mapping | 10 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #29

| Field | Value |
|---|---|
| Problem ID | #29 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 3 row |
| Phase mapping | 3 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #30

| Field | Value |
|---|---|
| Problem ID | #30 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phase 3 row |
| Phase mapping | 3 |
| Evidence | ID appears in ADR-021 mapping; no authoritative local definition source |
| Notes | |

### #31

| Field | Value |
|---|---|
| Problem ID | #31 |
| Original problem definition | NOT LOCALLY RECOVERABLE |
| Recovery status | NOT_LOCALLY_RECOVERABLE |
| Source | ADR-021 §4 phase map only (no statement text) |
| Source location | ADR-021 §4 / Phases 2 and 9 rows |
| Phase mapping | 2, 9 |
| Evidence | Dual-phase map only; no definition text |
| Notes | Appears in Phase 2 and Phase 9 maps |

## Completeness matrix

| ID | Status |
|----|--------|
| #1 | NOT RECOVERABLE |
| #2 | NOT RECOVERABLE |
| #3 | NOT RECOVERABLE |
| #4 | NOT RECOVERABLE |
| #5 | NOT RECOVERABLE |
| #6 | NOT RECOVERABLE |
| #7 | NOT RECOVERABLE |
| #8 | NOT RECOVERABLE |
| #9 | NOT RECOVERABLE |
| #10 | NOT RECOVERABLE |
| #11 | NOT RECOVERABLE |
| #12 | NOT RECOVERABLE |
| #13 | NOT RECOVERABLE |
| #14 | NOT RECOVERABLE |
| #15 | NOT RECOVERABLE |
| #16 | NOT RECOVERABLE |
| #17 | NOT RECOVERABLE |
| #18 | NOT RECOVERABLE |
| #19 | NOT RECOVERABLE |
| #20 | NOT RECOVERABLE |
| #21 | NOT RECOVERABLE |
| #22 | NOT RECOVERABLE |
| #23 | NOT RECOVERABLE |
| #24 | NOT RECOVERABLE |
| #25 | NOT RECOVERABLE |
| #26 | NOT RECOVERABLE |
| #27 | NOT RECOVERABLE |
| #28 | NOT RECOVERABLE |
| #29 | NOT RECOVERABLE |
| #30 | NOT RECOVERABLE |
| #31 | NOT RECOVERABLE |

## Summary counts

| Category | Count |
|----------|-------|
| RECOVERED_EXACT | 0 |
| RECOVERED_SOURCE_GROUNDED | 0 |
| PARTIALLY_RECOVERED | 0 |
| NOT_LOCALLY_RECOVERABLE | 31 |
| Phase mappings preserved from ADR-021 | YES |
| Invented definitions | 0 (NO) |
| Duplicate IDs | 0 |
| Missing IDs in `#1`–`#31` | 0 |

## Related Requirements / R32 / R33

R32 and R33 are **not** `#32`/`#33`. They are separate ADR-021 requirements.

### R32 — Answer Accuracy

| Field | Value |
|---|---|
| ID | R32 |
| Original definition | Answer Accuracy (title from ADR-021 §4) |
| Recovery status | RECOVERED_EXACT (title only from ADR-021); detailed mechanism notes in `AUTHORITATIVE-PROBLEM-REGISTRY.md` |
| Source | ADR-021 §4 Problem Traceability |
| Source location | `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md` — “R32 — Answer Accuracy”; Git object `1eaced9…` |
| Phase mapping | 5 (with `#11`, `#12`, `#13`) |
| Evidence | ADR-021 naming; Phase 5 map |
| Notes | Do not renumber to `#32` |

### R33 — Answer Presentation & Navigation

| Field | Value |
|---|---|
| ID | R33 |
| Original definition | Answer Presentation & Navigation (title from ADR-021 §4) |
| Recovery status | RECOVERED_EXACT (title only from ADR-021); detailed mechanism notes in `AUTHORITATIVE-PROBLEM-REGISTRY.md` |
| Source | ADR-021 §4 Problem Traceability |
| Source location | `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md` — “R33 — Answer Presentation & Navigation”; Git object `1eaced9…` |
| Phase mapping | 7 (with `#20`, `#21`, `#22`) |
| Evidence | ADR-021 naming; Phase 7 map |
| Notes | Do not renumber to `#33` |

## Human import required

Provide the original `#1`–`#31` problem statement source (document or paste). Until then, Phase 2+ planning may use ADR-021 themes and NEW-/RC- inventories, but must not claim numeric `#n` titles as known.
