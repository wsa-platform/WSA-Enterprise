# AUTHORITATIVE PROBLEM REGISTRY

> Inventory snapshot at code SHA `7486178`. Problem IDs are preserved. Do not invent `#1–#31` titles.
> Mechanism rows marked **HISTORICAL / RESOLVED** below were closed in later phases; the original evidence text is kept.
> Phase 8 closeout: `docs/architecture/PHASE-8-CLOSEOUT.md`.

- **Baseline code SHA:** `74861783574b0fd407d964900af2ecc827fe542f`
- **Remote architecture tip:** `1eaced9cb9902d295c2c04c37d529580b4b345e6` (ADR-021 only delta)
- **Registry rule:** Do not invent `#1–#31` titles. Mark UNRECOVERED when source absent.
- **Status vocabulary:** PROVEN | PARTIALLY PROVEN | REQUIRES VERIFICATION | ARCHITECTURAL DEBT | SECURITY REVIEW REQUIRED | OPEN DECISION | STALE | CONTRADICTED | UNRECOVERED

---

## A. `#1` … `#31`

| ID | TITLE | STATUS | SOURCE | NOTES |
|----|-------|--------|--------|-------|
| #1 … #31 | *(definitions unknown)* | **UNRECOVERED — SOURCE NOT FOUND** | Searched: `docs/`, ADRs on HEAD + `FETCH_HEAD`, git log `--all`, `G:/tmp/wsa-project-engineering-audit`, transcripts (references only) | **Do not invent replacements.** Import verbatim when human provides source. |

---

## B. R32 / R33 (mission-named; inventory docs absent)

### R32 — Answer Accuracy

| Field | Value |
|-------|--------|
| ID | R32 |
| TITLE | Answer Accuracy |
| STATUS | PARTIALLY PROVEN (mechanisms) + REQUIRES VERIFICATION (post–RC-C residual / dirty WT). Language + Home confidence/limitations presentation rows below are **HISTORICAL / RESOLVED** (R2 / P2-C09 / P2-C10). |
| SOURCE | Mission + Phase-1 proxy + prior tmp accuracy/RC forensics |
| EVIDENCE | **HISTORICAL:** Platform `answer_language`; FE omits confidence/limitations. **CURRENT:** `answer_language = question_language` (R2); Home FE renders confidence/limitations (P2-C10). Comparison entity-B omission remains historical (RC-C on HEAD). |
| FIRST DIVERGENCE | Language: QUS `resolvePlatformAnswerLanguage` (**HISTORICAL**). Comparison (historical): QueryBuilder multi-entity path before RC-C |
| ROOT CAUSE | Split: (1) locale-bound answer language contract (**HISTORICAL / RESOLVED R2**); (2) historical search-plan entity coverage gap (RC-C targets); (3) presentation hiding sufficiency signals (**HISTORICAL / RESOLVED P2-C10** for Home FE) |
| AFFECTED COMPONENTS | QUS, QueryBuilder, Composer, React, Flutter, Evidence |
| FILES | `QueryUnderstandingService.php`, `ScientificSearchQueryBuilder.php`, `AnswerComposer.php`, `HomeScientificResearchSearch.tsx`, Flutter research screen |
| CONFIDENCE | HIGH mechanisms; MEDIUM current user-visible residual |
| SECURITY | none primary |
| PERFORMANCE | indirect (thin evidence → retries not proven) |
| DATA INTEGRITY | locale collapse interacts (see NEW-04) — **HISTORICAL / RESOLVED P2-C08** |
| USER IMPACT | Wrong-language or incomplete comparison answers; overconfident UX |
| RUNTIME VERIFICATION | YES — clean-HEAD matrix required |
| DEPENDENCIES | Semantic contract; Search; Evidence; Language policy OPEN DECISION |
| TARGET PHASE | ADR-021 Phase 2 + Phase 5 |
| BLOCKER | Dirty WIP; no live matrix this reconciliation; `#1–#31` unknown mapping |

### R33 — Answer Presentation & Navigation

| Field | Value |
|-------|--------|
| ID | R33 |
| TITLE | Answer Presentation & Navigation |
| STATUS | PROVEN (current thin UI) + OPEN DECISION (product requirements for Google intermediary / multi-answer) |
| SOURCE | Mission + code inspection |
| EVIDENCE | React: single answer + direct `href`; Flutter: no URL launch; no Google intermediary in research code |
| FIRST DIVERGENCE | Client render layer (`HomeScientificResearchSearch`; Flutter `ResearchAgentScreen`) |
| ROOT CAUSE | Client contracts consume subset of API; product features not implemented |
| AFFECTED COMPONENTS | React, Flutter, API field exposure |
| FILES | `HomeScientificResearchSearch.tsx`, `researchAgent.ts`, `research_agent_screen.dart`, `http_client.dart` |
| CONFIDENCE | HIGH for code facts |
| SECURITY | External URL open (React) — browser navigation; SSRF not via Composer fetch |
| PERFORMANCE | none primary |
| DATA INTEGRITY | none primary |
| USER IMPACT | Limited evidence UX; non-clickable Flutter citations |
| RUNTIME VERIFICATION | Optional UI walkthrough |
| DEPENDENCIES | API contract alignment; product decision on intermediary |
| TARGET PHASE | ADR-021 late UI / Phase 5–adjacent |
| BLOCKER | Product requirements not documented in-repo |

---

## C. NEW-01 … NEW-12 (from Phase-1; revalidated)

### NEW-01 — ADR-021 governance gap (local)

| Field | Value |
|-------|--------|
| STATUS | **PARTIALLY CONTRADICTED / STALE as absolute** — missing on `7486178`, **present on remote `1eaced9`** |
| SOURCE | Phase-1 + this reconciliation |
| EVIDENCE | `ls-tree HEAD` absent; `FETCH_HEAD` has ADR-021 |
| FIRST DIVERGENCE | Docs commit `1eaced9` not in local HEAD |
| ROOT CAUSE | Local tip lag / stale tracking |
| TARGET PHASE | Governance / FF to `1eaced9` |
| BLOCKER | WIP-safe fast-forward authorization |

### NEW-02 — Public organization_id → Library write

| Field | Value |
|-------|--------|
| STATUS | **SECURITY REVIEW REQUIRED** / PROVEN mechanism |
| SOURCE | Phase-1 + HEAD controller |
| EVIDENCE | `AgriculturalResearchAgentController::resolveOrganization` + persist on query |
| FIRST DIVERGENCE | Public API entry (unauthenticated org resolve) |
| ROOT CAUSE | Public demo routes without membership check |
| AFFECTED | API, Persistence, Tenant |
| FILES | `AgriculturalResearchAgentController.php`, `ScientificKnowledgePersistenceService.php` |
| CONFIDENCE | HIGH |
| SECURITY | HIGH — tenant write / enumeration |
| RUNTIME VERIFICATION | YES (non-destructive design review + controlled proof) |
| TARGET PHASE | Security before broad public hardening |
| BLOCKER | Product intent for public demo org |

### NEW-03 — Disposition ownership duplication

| Field | Value |
|-------|--------|
| STATUS | ARCHITECTURAL DEBT / PROVEN |
| EVIDENCE | Universal applies `HomeEvidenceLifecycleDisposition`; Composer also has Home lifecycle metadata helpers; Agent wiring only in WIP |
| FIRST DIVERGENCE | Post-compose ownership split |
| ROOT CAUSE | Dual implementation of disposition concerns |
| TARGET PHASE | ADR-021 Phase 4–5 |
| FILES | `AnswerComposer.php`, `HomeEvidenceLifecycleDisposition.php`, `UniversalAnswerOrchestrator.php`, WIP `AgriculturalResearchAgent.php` |

### NEW-04 — Locale persistence TR/FR → en

| Field | Value |
|-------|--------|
| STATUS | **HISTORICAL / RESOLVED (P2-C08).** Original Phase 1 evidence: **PROVEN on committed HEAD** `7486178` (not WIP-only). |
| EVIDENCE | **HISTORICAL:** `$item->locale = $language === 'ar' ? 'ar' : 'en'`. **CURRENT:** persist supported `ar\|en\|tr\|fr` without TR/FR → `en` collapse. |
| FIRST DIVERGENCE | `ScientificKnowledgePersistenceService` persist |
| ROOT CAUSE | Binary locale model (**historical**) |
| USER IMPACT | Wrong library language for TR/FR (**historical**) |
| TARGET PHASE | Language (Phase 5) — closed for this mechanism |
| RUNTIME VERIFICATION | Optional read of persisted rows |

### NEW-05 — Flutter missing Accept-Language

| Field | Value |
|-------|--------|
| STATUS | **HISTORICAL / RESOLVED (P2-C04).** Original Phase 1 evidence: PROVEN. |
| EVIDENCE | **HISTORICAL:** `_headers()` has Accept/Authorization/X-Organization-Id only. **CURRENT:** Flutter/admin-mobile send UI `Accept-Language`. R2: UI locale must not override answer language. |
| FIRST DIVERGENCE | Flutter HTTP client |
| ROOT CAUSE | Header omission + Arabic-only UI locale (**historical**) |
| TARGET PHASE | Mobile + Language — closed for this header omission |
| DEPENDENCIES | Language contract decision — R2 closed |

### NEW-06 — React drops confidence / limitations

| Field | Value |
|-------|--------|
| STATUS | **HISTORICAL / RESOLVED (P2-C10)** for Home FE confidence/limitations. Original Phase 1 evidence: PROVEN. |
| EVIDENCE | **HISTORICAL:** Home UI renders answer/citations/status; not confidence/limitations. **CURRENT:** Home FE renders both (P2-C10). Broader R33 presentation decisions remain in `WSA-ENTERPRISE-R33-PHASE7-OPEN-DECISIONS.md`. |
| FIRST DIVERGENCE | React presentation |
| TARGET PHASE | R33 / Phase 5 UI |
| USER IMPACT | Accuracy signals hidden (**historical for this Home FE gap**)

### NEW-07 — Variant budget 5 vs execution 2

| Field | Value |
|-------|--------|
| STATUS | PROVEN (design asymmetry) |
| EVIDENCE | QB `MAX_VARIANTS=5`; Orchestrator `MAX_VARIANTS_PER_PROVIDER=2` |
| FIRST DIVERGENCE | Orchestrator execution cap |
| ROOT CAUSE | Separate constants / layers |
| PERFORMANCE | Possible wasted planning; not proven wall-clock sole cause |
| TARGET PHASE | Search / Providers (Phase 3) |
| RUNTIME VERIFICATION | With RC-L timings |

### NEW-08 — KnowledgeQueryPlan.toArray omits contextInput

| Field | Value |
|-------|--------|
| STATUS | PROVEN |
| EVIDENCE | `toArray()` keys lack `contextInput`; in-memory object retains it |
| FIRST DIVERGENCE | Serialization boundary |
| IMPACT | Stage API / observability incomplete; search still OK in-process |
| TARGET PHASE | API contracts / Phase 2 |

### NEW-09 — admin-mobile CI gap

| Field | Value |
|-------|--------|
| STATUS | PROVEN |
| EVIDENCE | Root `ci.yml` runs `mobile/` not `admin-mobile/` |
| TARGET PHASE | CI / quality gates |

### NEW-10 — Fenix comments in `.env.example`

| Field | Value |
|-------|--------|
| STATUS | PROVEN drift (committed + dirty WIP may add more) |
| EVIDENCE | `.env.example` FAO_* Fenix comments alongside FAOSTAT_* |
| TARGET PHASE | Ops/docs hygiene |
| NOTE | Dirty `.env.example` is WIP — do not stage blindly |

### NEW-11 — Mission / tracking HEAD confusion

| Field | Value |
|-------|--------|
| STATUS | PROVEN (this reconciliation resolves mechanism) |
| EVIDENCE | Stale `origin/…` vs live `1eaced9` tip |
| TARGET PHASE | Process / fetch discipline |

### NEW-12 — Dirty WIP overlaps scientific stack

| Field | Value |
|-------|--------|
| STATUS | PROVEN |
| EVIDENCE | 19 modified files including QUS/Composer/QB/Ranker/EVL |
| IMPACT | Contaminates runtime verification |
| TARGET PHASE | All phases — isolation required |
| BLOCKER | Yes for clean live proof |

---

## D. Alternate recovered inventories (not `#1–#31`)

### D1. RC-1 … RC-4 (external tmp forensics @ older HEAD `708debb`)

| ID | TITLE | STATUS vs current baseline | FIRST DIVERGENCE | NOTES |
|----|-------|----------------------------|------------------|-------|
| RC-1 | Home latency ~9–15s Stage-3 dominant | STALE timings; architecture PARTIALLY PROVEN; RC-L enables remeasure | Orchestrator execute | REQUIRES VERIFICATION on `7486178`/`1eaced9` |
| RC-2 | Comparison variants omit entity B | PARTIALLY REMEDIATED by RC-C on HEAD | QueryBuilder (historical) | Live residual REQUIRES VERIFICATION |
| RC-3 | Evidence funnel / under-retrieval | PARTIALLY PROVEN historically; cascade from RC-2 | Providers never queried for B | Re-verify post–RC-C |
| RC-4 | AR/TR/FR → English answers | PROVEN mechanism (platform locale) | QUS answer_language | Product OPEN DECISION |

### D2. ADR-020 phase labels (historical plan)

| ID | TITLE | STATUS |
|----|-------|--------|
| P4a | Crossref Observability | Implemented lineage (historical) |
| P2 | Statistical Measure Alignment | Implemented lineage |
| P4b | FAOSTAT Duplicate Control | Implemented lineage |
| P3 | Evidence State + Composer | Partial / ongoing WIP |
| P1 | Entity Contract + Specificity | Partial / RC-C related |
| P4c | Legacy Short-Circuit | Implemented lineage |
| P4d | Provider Concurrency | Implemented lineage |

### D3. SEC-* (Phase-1 security slice)

| ID | TITLE | STATUS |
|----|-------|--------|
| SEC-1 | Public unauthenticated research/crop | PROVEN |
| SEC-2 | Client org_id resolve | SECURITY REVIEW REQUIRED |
| SEC-3 | Fallback first Organization | PROVEN |
| SEC-4 | Expensive public search | PROVEN |
| SEC-5 | Citation SSRF via Composer | NOT FOUND / low |
| SEC-6 | FAOSTAT host allowlist | PROVEN mitigation |

---

## E. Additional confirmed structural issues (no new # numbers invented)

| ID | TITLE | STATUS | FIRST DIVERGENCE | ROOT CAUSE |
|----|-------|--------|------------------|------------|
| STRUCT-01 | Dual provider registries | ARCHITECTURAL DEBT | Registry split | Parallel Intelligence vs Stage-3 |
| STRUCT-02 | Home/Crop share agent after QUS | PROVEN design | QUS crop gate | Shared foundation |
| STRUCT-03 | Crop always legacy profile shaping | PROVEN | Agent crop branch | Dual response contracts |
| STRUCT-04 | Evidence reclassified in many layers | ARCHITECTURAL DEBT | Ranker→…→Composer | Ownership fragmentation |
| STRUCT-05 | Consensus registered but not default-selected | PROVEN | Selector defaults | Dead/optional path |

---

## F. Registry completeness statement

- **Authoritative `#1–#31`:** UNRECOVERED.
- **Usable working registry for remediation:** R32, R33, NEW-01…NEW-12 (with NEW-01 updated), RC-1…RC-4, SEC-*, STRUCT-*, ADR-020 labels as historical.
- **Do not renumber** external lists into `#1–#31` without human import.
