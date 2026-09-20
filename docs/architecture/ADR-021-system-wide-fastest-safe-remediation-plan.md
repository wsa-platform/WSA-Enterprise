# ADR-021 — WSA-Enterprise System-Wide Fastest Safe Remediation Plan

**Status:** ACCEPTED  
**Scope:** Entire WSA-Enterprise system  
**Execution model:** Fastest Safe Remediation  
**Time-boxing:** Explicitly rejected  
**Crop:** IN SCOPE  
**Decision type:** System-wide architectural remediation plan

## 1. Decision

Adopt the following 10-phase plan as the architectural and execution baseline for the system-wide remediation of WSA-Enterprise.

The objective is to complete the required repairs as quickly as practically possible without sacrificing architectural correctness, evidence quality, security, or regression safety.

There is **no fixed duration, schedule, week/month estimate, or time-box** for the work.

## 2. Scope

The scope covers the entire project, including:

- Backend
- API
- React
- Flutter
- Database
- Docker
- Search
- Scientific providers
- FAOSTAT
- Evidence and validation
- Answer composition and accuracy
- Language architecture
- Crop
- Home
- Legacy architecture
- Security and tenant isolation
- Observability and performance
- Documentation and engineering workflow

### Crop scope decision

Crop was historically protected by ADR-019. In the current remediation phase, the user explicitly directed that **Crop is now inside the scope of architectural inspection and repair**.

ADR-019 remains a historical record of the earlier protection boundary; this ADR defines the current remediation scope.

## 3. Ten Remediation Phases

### Phase 1 — Baseline + Forensic Audit

Establish the actual current state of the entire repository and runtime surface.

Cover Git/WIP, Backend, React, Flutter, API, Database, Docker, Providers, Search, Evidence, Crop, Home, Legacy, Security, Performance, and Documentation.

Required outputs:

- `CURRENT-SYSTEM-STATE.md`
- `SYSTEM-WIDE-FORENSIC-AUDIT.md`
- `SYSTEM-ARCHITECTURE-MAP.md`

This phase is read-only.

### Phase 2 — Core Contracts & Semantic Integrity

Repair and formalize the contracts around:

- Query Understanding
- Entity and Property resolution
- Intent
- Location
- Time
- Comparison
- Language metadata
- KnowledgeQueryPlan
- Semantic preservation
- Search-relevant entity coverage

No required semantic element may disappear silently between stages.

### Phase 3 — Search & Provider Architecture

Repair:

- Search Query Builder
- Search variants
- Expansion budget
- Provider selection
- Provider execution
- Concurrency
- Timeout
- Retry
- Deduplication
- Provider status
- FAOSTAT integration
- Search observability

Do not solve coverage problems by blindly increasing query volume.

### Phase 4 — Evidence + Validation + Ranking

Unify the Evidence lifecycle:

`DISCOVERED → NORMALIZED → VALIDATED → USABLE → RANKED → SELECTED → CITED → PERSISTED`

Explicitly distinguish rejected, filtered, duplicate, conflicting, no-results, not-queried, unavailable, timeout, validation-rejected, and insufficient-evidence states.

Separate generic scientific ranking from domain-specific agricultural relevance policies.

### Phase 5 — Answer Accuracy + Language + Composer

Repair answer accuracy and AnswerComposer responsibilities.

Required logical chain:

`Question → Claims → Evidence → Answer`

The system must not present a complete-looking answer when essential claims lack adequate supporting evidence.

Unify language handling for Arabic, English, Turkish, and French across understanding, planning, search, evidence, composition, persistence, API, React, and Flutter.

### Phase 6 — Crop Full Pipeline + Generic Architecture

Inspect and repair the complete Crop path:

`PlantProductionPage → Crop Selector → Farming Needs → API → Controller → Research → Planner → Search → Evidence → Composer → Persistence → UI`

Also inspect Generic/Crop coupling, Crop Knowledge Engine, AgriculturalScientificKnowledgeEngine, legacy interaction, semantic integrity, evidence, language, and performance.

### Phase 7 — API + React + Flutter + Answer UI

Establish a canonical API contract shared by Backend, React, and Flutter.

Repair response/error/evidence/language/link/loading/empty-state handling.

Answer presentation requirements:

- Each answer is visually separated from the next answer.
- Topic/source links are genuinely clickable.
- Internal WSA content opens through a WSA route/page.
- External sources open their original external destination.
- Google must not be used as an intermediary for reaching the source.

### Phase 8 — Security + Tenant Isolation

Perform an actual security verification covering:

- Authentication
- Authorization
- RBAC
- Tenant isolation
- Organization ownership
- ID enumeration
- Public endpoints
- Public write operations
- Rate limiting
- Input validation
- SQL injection surfaces
- SSRF
- Secrets exposure
- File access
- Audit logging

Pay special attention to `/public/research-agent/*` and persistence paths involving `organization_id` and `LibraryItem`.

Do not classify an issue as a confirmed vulnerability until evidence establishes it.

### Phase 9 — Legacy + WIP + Engineering Cleanup

For Legacy:

`Consumer Inventory → Compatibility Layer → Migration → Deprecation → Removal`

Do not delete Legacy blindly.

For WIP:

- Keep unrelated existing work protected.
- Stage explicit files/hunks only.
- Never use `git add .` or `git add -A`.
- Keep remediation commits atomic and traceable.

Update architecture documentation and the decision register.

### Phase 10 — Full Verification + Release Gate

Run and verify:

- Unit tests
- Integration tests
- API tests
- React tests
- Flutter tests
- Crop E2E
- Home E2E
- Comparison scenarios
- Arabic/English/Turkish/French
- Evidence scenarios
- Security verification
- Performance verification
- Regression suite

Then review the final diff, changed files, contracts, documentation, configuration, and runtime impact.

Only after Human GO:

`Atomic Commit → Push`

## 4. Problem Traceability

The ten phases collectively cover all 31 previously identified system problems plus two additional requirements:

- **R32 — Answer Accuracy**
- **R33 — Answer Presentation & Navigation**

The problem-to-phase mapping is maintained as follows:

| Phase | Covered problems |
|---|---|
| 1 | All problems — baseline, evidence, and root-cause confirmation |
| 2 | #2, #3, #4, #12, #13, #16, #31 |
| 3 | #4, #5, #6, #7, #8, #29, #30 |
| 4 | #9, #10, #17 |
| 5 | #11, #12, #13, R32 |
| 6 | #14, #15, #24 |
| 7 | #20, #21, #22, R33 |
| 8 | #18, #19 |
| 9 | #1, #25, #26, #27, #31 |
| 10 | #23, #28, plus final verification of all problems |

## 5. Execution Rules

### Fastest Safe Remediation

- No time-boxing.
- No fixed project duration.
- No week/month estimates.
- Group compatible repairs when they share a root cause.
- Parallelize only when dependencies and regression risk permit.
- Do not sacrifice verification for speed.

### Git safety

Do not use:

- `git reset`
- `git restore`
- `git clean`
- `git stash`
- `git rebase`
- force push

Do not use broad staging:

- `git add .`
- `git add -A`

### Change control

`Evidence → Root Cause → Repair → Test → Regression → Verification → Commit`

Feature flags are optional and should only be used where they are technically appropriate.

If a committed repair must be rolled back, use a controlled revert after the regression is identified and verified.

## 6. Acceptance Principle

No problem is considered closed merely because code was changed.

A remediation item is closed only when its root cause is understood, the repair is implemented, the required tests pass, regression impact is checked, and the result is verified.

## 7. Current Status

This ADR records the approved remediation architecture and scope. It does not itself authorize unrelated implementation changes.

**Next execution step:** Phase 1 — Read-Only Full Forensic Audit.
