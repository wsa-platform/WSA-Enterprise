# WSA-Enterprise Master Remediation Execution Plan

> **HISTORICAL PLANNING BLUEPRINT** (created 2026-09-20 @ code baseline `7486178`).
> Do not treat G0 / Open Decision #3 / “U8.4 deferred” rows below as live gates.
> **CURRENT:** MODEL B public **writes** are closed (R1 / Phase 8A-1). Do not reopen MODEL B vs C.
> Phase 8 closeout: `docs/architecture/PHASE-8-CLOSEOUT.md`.
> Phase 9 units in this file are **not** rewritten here. Official Phase 9 name: **LEGACY + WIP + ENGINEERING CLEANUP** (Wave 0 / Wave 1). Do not invent “Phase 9-1”.

- **Document type:** Authoritative remediation blueprint (planning only)
- **Created:** 2026-09-20
- **Code baseline:** `74861783574b0fd407d964900af2ecc827fe542f`
- **Architecture authority:** ADR-021 on remote tip `1eaced9cb9902d295c2c04c37d529580b4b345e6`
- **Branch:** `phase-18-m18-ai-marketing-communications`
- **This document does NOT authorize implementation, commit, or push**

---

## 1. Authoritative Baseline

| Item | Value | Evidence |
|------|--------|----------|
| Local HEAD (checked out) | `7486178` | `git rev-parse HEAD` |
| Remote tip | `1eaced9` | `git ls-remote origin refs/heads/phase-18-…` |
| Relationship | `7486178` → `1eaced9` (linear; **docs-only** ADR-021) | `merge-base --is-ancestor`; `diff --name-status` |
| RC-C | On `e96c34a` / contained in `7486178` ancestry | `ensureHomeMultiEntitySemanticCoverage` on HEAD QB |
| RC-L | On `7486178` | Outcome `duration_ms`; orchestrator `stage3_elapsed_ms` |
| ADR-019 | Historical Crop protection | Do **not** rewrite |
| ADR-020 | Historical 8-phase Home plan | Historical unless evidence confirms surviving decision |
| ADR-021 | Current remediation authority | Crop IN SCOPE; 10 phases; no time-boxing |
| `#1–#31` | **UNRECOVERED** | Do not invent |

**Working rule:** Reason about **committed** behavior via `HEAD` / `git show HEAD:…`. Dirty worktree is **not** the clean baseline.

---

## 2. Current Repository State

| Field | State |
|-------|--------|
| Index | CLEAN |
| Tracked modified | 19 (QUS, Catalog, QB, Ranker, Directness, Relevance, Composer, EVL, Matcher, Planner, Agent, Taxonomy, tests, `.env.example`) |
| Untracked | ~429 (e2e-tmp, logs, untracked Feature tests, forensic docs) |
| Local vs live remote | Behind by ADR-021 only; tracking ref often **stale** |
| Phase 1–reconciliation docs | Present as untracked under `docs/architecture/` |

---

## 3. Protected WIP Boundaries

**MUST NOT:** `reset` / `restore` / `clean` / `stash` / `checkout` / `switch` / `rebase` / `cherry-pick` / `revert` / `commit` / `push` / `add .` / `add -A` / delete untracked / overwrite modified.

**Preferred staging forever:** path-scoped `git add -- <file>` and/or **index-only surgical patches**; never whole dirty scientific mega-files without hunk isolation.

**WIP contamination:** Dirty QUS/Composer/QB/Ranker/EVL/Matcher **BLOCK** treating live dirty-tree results as clean-HEAD proof (NEW-12).

---

## 4. Architecture Summary (as implemented)

```
HOME:  React HomeScientificResearchSearch
    → POST /api/v1/public/research-agent/query
    → AgriculturalResearchAgentController::query
    → AgriculturalResearchAgent::conductResearch
    → QUS::understand → ResearchPlanner::planKnowledgeQuery (generic)
    → Search (MultiSourceScientificSearchOrchestrator)
    → Validation → AnswerComposer::compose
    → [Universal path] HomeEvidenceLifecycleDisposition::applyToSynthesis
    → ScientificKnowledgePersistenceService::persist
    → optional legacy KnowledgeEngine (gated)

CROP:  PlantProductionPage → FieldCrop* → GET …/farming-needs-profile
    → PublicFieldCropCultivationController::farmingNeedsProfile
    → conductCropProfileResearch ≡ conductResearch
    → QUS::understandCropProfileContext → buildCropProfileKnowledgePlan
    → shared Search/Validate/Compose
    → always legacy profile shaping (CropKnowledgeEngine via AgriculturalScientificKnowledgeEngine)
```

**First Home/Crop fork:** `QueryUnderstandingService::understand` when `selected_crop_id` + `selected_crop_name` present.

**Shared risk:** One agent, shared Stage 3–5 classes → Home patches can regress Crop unless gated.

---

## 5. Root-Cause Model

### RC-A — Semantic coverage / plan→search loss
- **First divergence:** `ScientificSearchQueryBuilder::buildVariantsFromPlan` / entity resolution (historical RC-2); RC-C adds `ensureHomeMultiEntitySemanticCoverage` (Home-only).
- **Downstream:** Missing provider queries (RC-3), incomplete comparison answers (R32).
- **Status:** Partially remediated on HEAD; **REQUIRES VERIFICATION** live residual.

### RC-B — Answer language bound to platform locale
- **First divergence:** `QueryUnderstandingService::resolvePlatformAnswerLanguage` → `constraints.answer_language`.
- **Downstream:** EN answers under EN locale / missing Accept-Language (RC-4, R32 language); Flutter header gap (NEW-05).
- **Status:** PROVEN mechanism; product **OPEN DECISION**.

### RC-C — Persistence locale collapse
- **First divergence:** `ScientificKnowledgePersistenceService::persist` — `locale = ($language === 'ar') ? 'ar' : 'en'`.
- **Downstream:** TR/FR LibraryItem locale wrong (NEW-04).
- **Status:** PROVEN on committed HEAD.

### RC-D — Public tenant write surface
- **First divergence:** `AgriculturalResearchAgentController::resolveOrganization` on unauthenticated public routes + `persist`.
- **Downstream:** NEW-02, SEC-1…4.
- **Status:** SECURITY REVIEW REQUIRED.

### RC-E — Fragmented evidence ownership
- **First divergence:** Same evidence reclassified in Ranker / Matcher / EVL / Validation / Composer; disposition in Composer helpers **and** `HomeEvidenceLifecycleDisposition` (Universal); Agent wiring only in WIP.
- **Downstream:** NEW-03, STRUCT-04, R32 opacity.
- **Status:** ARCHITECTURAL DEBT / PROVEN duplication.

### RC-F — Stage-3 multi-provider latency architecture
- **First divergence:** `MultiSourceScientificSearchOrchestrator::execute` (FAOSTAT then scholarly; Home overlap).
- **Downstream:** Historical ~9–15s totals (RC-1) — **STALE as current numbers**; RC-L enables remeasure.
- **Status:** REQUIRES VERIFICATION on clean HEAD before provider strategy changes.

### RC-G — Client contract thinning / presentation
- **First divergence:** React/Flutter consume subset of synthesis fields; Flutter no Accept-Language; citations presentation (R33, NEW-06).
- **Status:** PROVEN code facts; product OPEN DECISION for multi-answer / Google intermediary.

### RC-H — Governance / tip lag
- **First divergence:** Local HEAD behind `1eaced9`; stale tracking (NEW-01/11).
- **Status:** PROVEN; process fix.

### RC-I — Dual registries / optional Consensus / env drift
- **STRUCT-01/05, NEW-10** — clarify selection; no blind removals.

### Not independent “fix everywhere”
Do **not** patch OpenAlex, Crossref, Ranker, Composer, and FE separately for comparison incompleteness if RC-A residual is the cause — verify RC-C first, then one Search-plan fix.

---

## 6. Authoritative Problem Registry Reference

Canonical table: `docs/architecture/AUTHORITATIVE-PROBLEM-REGISTRY.md`.

| Set | Handling |
|-----|----------|
| `#1–#31` | UNRECOVERED — import before claiming complete taxonomy |
| R32 / R33 | Mission-named; remediate via Phases 2/5/7 |
| NEW-01…12 | Working IDs; NEW-01 = tip lag (ADR-021 on remote) |
| RC-1…4 | Historical root causes; map to RC-A…F above |
| SEC-* / STRUCT-* | Security + architecture debt |
| ADR-020 P* | Historical completed/partial lineage — do not re-open blindly |

---

## 7. Problem Deduplication

| ROOT CAUSE ID | PROBLEMS COVERED | SINGLE FIX? | WHY | REGRESSION RISKS |
|---------------|------------------|-------------|-----|------------------|
| RC-A | RC-2, RC-3, R32 comparison | Prefer single Search-plan/QUS contract after verify | Earliest divergence before providers | Crop if shared QB change ungated |
| RC-B + RC-C + NEW-05 | RC-4, R32 language, NEW-04, NEW-05 | One **language contract** across layers | Same language ownership chain | Composer/persist/Flutter together |
| RC-D | NEW-02, SEC-1…4 | One public org/persist policy | Same entrypoint | Demo UX / public research |
| RC-E | NEW-03, STRUCT-04, part R32 | One disposition/directness owner | Stops conflicting labels | Home vs Crop disposition gates |
| RC-F | RC-1, NEW-07 (partial) | Measure first (RC-L), then one orchestration policy | Avoid symptom provider cuts | Home concurrency vs Crop sequential |
| RC-G | NEW-06, R33, Flutter URL | API+UI contract after backend stable | Presentation not root of accuracy | Product OPEN DECISIONS |
| RC-H | NEW-01, NEW-11 | FF/fetch governance | Docs-only | None if FF |
| NEW-08 | Stage API incompleteness | Serialization contract | Separate from search in-memory | Stage route consumers |
| NEW-09 | CI gap | CI job | Independent | Pipeline time |
| NEW-10 | Fenix comments | Env hygiene | Independent | Operator confusion |
| NEW-12 | Contaminated verification | Process isolation | Blocks false greens | All phases |
| STRUCT-02/03 | Home/Crop share + Crop legacy shape | Phase 6 shared/Crop gates | After contracts stable | Crop UX |

**STALE / ALREADY RESOLVED (do not re-fix unless verification fails):**
- RC-C commit itself (helper on HEAD) — *verify residual*, don’t re-implement blindly.
- RC-L instrumentation on HEAD — *use it*, don’t re-add mapping.
- Absolute claim “ADR-021 does not exist anywhere” — **STALE**; exists on `1eaced9`.

---

## 8. Phase 2 — Core Contracts & Semantic Integrity

### Canonical semantic contract (target)

| Field | SOURCE | OWNER | CONSUMERS | LOSS RISK |
|-------|--------|-------|-----------|-----------|
| entity / entities[] | QUS + Catalog | `QueryUnderstandingService` / `AgriculturalEntityCatalog` | Planner, QB, Ranker, Matcher, Composer | Multi-entity drop in QB (historical) |
| property / requested_information | QUS | QUS | QB, Matcher, Composer | Sense collapse |
| intent / scientific_sense | QUS | QUS | Planner, QB, Directness | Land/cultivation WIP contamination |
| comparison + comparison_entities | QUS constraints | QUS | QB `ensureHomeMultiEntity…` | Crop skips helper |
| location / year | QUS constraints | QUS | QB, FAOSTAT options, Ranker geo | Year omit-from-text intentional; structured year must survive |
| question language | QUS detect | QUS | Persist metadata | ≠ answer_language |
| answer_language | Platform locale | QUS `resolvePlatformAnswerLanguage` | Composer | RC-B |
| crop profile context | Input selectors | QUS crop path + `contextInput` | Planner crop plan, intent flag | `toArray()` omits `contextInput` (NEW-08) |
| specificity / residual | QUS constraints | QUS | QB | Dirty WIP |

### Exact remediation units (design)

| UNIT | OBJECTIVE | FILES (likely) | NOT TO CHANGE | TESTS |
|------|-----------|----------------|---------------|-------|
| U2.1 | Document + contract-test field survival QUS→Plan→QB | `QueryUnderstandingService.php`, `KnowledgeQueryPlan.php`, `ResearchPlanner.php`, `ScientificSearchQueryBuilder.php` (+ tests) | AnswerComposer wholesale; providers; Crop FE | Extend HomeSearchPlanSemanticCoverage; Phase9 multilingual; comparison AR/EN/TR/FR |
| U2.2 | Serialize `contextInput` (or explicit crop subset) in plan arrays | `KnowledgeQueryPlan::toArray`, possibly `AgriculturalResearchPlan::toArray` | Search behavior | Stage API round-trip |
| U2.3 | Post–RC-C residual comparison verification only | Prefer **no code** until clean-HEAD fail | Dirty QB mega-WIP | Live/isolated matrix |

**HUMAN GO** before any semantic contract behavior change.

---

## 9. Phase 3 — Search & Provider Architecture

| Topic | Current fact (HEAD) | Remediation rule |
|-------|---------------------|------------------|
| MAX_VARIANTS | 5 in QB | Do not raise blindly |
| MAX_VARIANTS_PER_PROVIDER | 2 in Orchestrator | Align/document with QB (NEW-07) after measure |
| ADEQUATE_RESULT_COUNT | 8 | Preserve unless evidence |
| Home concurrency | Scholarly overlap for `generic_research` | Keep Crop sequential via `isCropProfileIntent()` |
| Default Stage-3 sources | OA, CR, S2; ±FAOSTAT if enabled | Consensus not default-selected |
| FAOSTAT | Portal `faostatservices.fao.org`; default disabled | No Fenix revive; clean `.env.example` comments (NEW-10) carefully |
| RC-L | `duration_ms`, `stage3_elapsed_ms` | **Measure clean HEAD before strategy change** |

| UNIT | OBJECTIVE | FILES | DEPENDENCIES |
|------|-----------|-------|--------------|
| U3.0 | Clean-HEAD latency ownership matrix using RC-L fields | Probes only (no prod code) or read-only harness | G0; prefer G1 before public persist probes |
| U3.1 | Budget/selection policy only if U3.0 proves bottleneck | `MultiSourceScientificSearchOrchestrator.php`, `ScientificSourceSelector.php`, config | U3.0 |
| U3.2 | Registry clarification (Intelligence vs Stage-3) docs + selection tests | Registry/selector; tests | — |

**HUMAN GO** before orchestration/concurrency/timeout/retry changes.

---

## 10. Phase 4 — Evidence / Validation / Ranking

### Canonical ownership (target)

| Concern | Canonical owner (proposed) | Must not redefine independently |
|---------|----------------------------|----------------------------------|
| Relevance gate | `ScientificEvidenceRelevanceGate` | Composer ad-hoc re-gate without contract |
| Directness label | `ScientificEvidenceDirectnessAssessor` (+ EVL refine as **documented** post-step) | Ranker inventing alternate taxonomy |
| Match / claim support | `ClaimEvidenceMatcher` | — |
| Usability / disposition (Home) | **Single** `HomeEvidenceLifecycleDisposition` | Composer duplicate metadata arms |
| Ranking score | `ScientificResultRanker` | Domain policy extraction over time |
| Citation selection | `AnswerComposer` (consumes upstream states) | Re-deriving directness from scratch without recording |

| UNIT | OBJECTIVE | FILES | NOT TO CHANGE |
|------|-----------|-------|---------------|
| U4.1 | Disposition single-writer ADR + remove/delegate Composer duplicate | `HomeEvidenceLifecycleDisposition.php`, `AnswerComposer.php` (narrow), `UniversalAnswerOrchestrator.php`; Agent only if bringing WIP with isolation | Crop disposition no-op |
| U4.2 | Evidence state enum/contract doc + tests | Validation/EVL/Matcher as needed | Provider adapters |
| U4.3 | Ranker domain-policy inventory (geo/germination) — extract only if proven harmful mix | `ScientificResultRanker.php` + GeoScopeTest | Blind threshold edits |

---

## 11. Phase 5 — Answer Accuracy / Language / Composer

### R32 close-out order
1. Verify RC-A residual (comparison) on clean HEAD.  
2. Land language policy (OPEN DECISION) → QUS + Composer + Persist + clients.  
3. Composer sufficiency framing only after evidence ownership (Phase 4).  
4. Do not whole-file commit dirty `AnswerComposer.php` WIP.

| UNIT | OBJECTIVE | FILES | CHECKS |
|------|-----------|-------|--------|
| U5.1 | Language policy implementation | `QueryUnderstandingService::resolvePlatformAnswerLanguage`, Composer phrases, `ScientificKnowledgePersistenceService::persist`, Flutter `_headers`, React already sends Accept-Language | AR/EN/TR/FR matrix |
| U5.2 | Composer accuracy gates (unsupported claims / limitations) | `AnswerComposer::compose` (+ related) | Accuracy Feature tests |
| U5.3 | Align Agent disposition with Universal if product requires Agent path parity | `AgriculturalResearchAgent.php` (from WIP surgically) | Disposition contract |

**HUMAN GO** for language policy and Composer behavior.

---

## 12. Phase 6 — Crop / Generic Architecture

ADR-021: Crop **in scope**. ADR-019: historical record only.

| Shared (keep shared, gate behavior) | Crop-specific (preserve unless defect) |
|-------------------------------------|----------------------------------------|
| Agent `conductResearch`, Search, Validate, Compose classes | `understandCropProfileContext`, `buildCropProfileKnowledgePlan`, legacy profile response, FE sections UI |
| Intent via `isCropProfileIntent()` | Always-on legacy post-process for Crop |

| UNIT | OBJECTIVE | FILES | VERIFICATION |
|------|-----------|-------|--------------|
| U6.1 | Crop contract test suite (profile shape, sequential search, no Home disposition) | Crop controller, agent gates, FE client types, new/extended tests | Crop E2E / Feature |
| U6.2 | Shared-change Crop gate checklist enforced in PRs | Process + tests | Any shared file change |
| U6.3 | Only evidence-backed Crop defects | TBD after U6.1 fails | HUMAN GO |

**HUMAN GO** before any Crop architecture behavior change.

---

## 13. Phase 7 — API / React / Flutter / Answer UI

### Canonical response fields (minimum for clients)

`status`, `answer` / `concise_summary`, `citations[]` (incl. `url`), `confidence`, `limitations`, `evidence_references` (if emitted), language metadata, errors.

| UNIT | OBJECTIVE | FILES |
|------|-----------|-------|
| U7.1 | Backend serialization completeness (NEW-08 + field guarantees) | Plan/report `toArray`, controller responses |
| U7.2 | React: surface confidence/limitations; keep direct citation URLs; no Google intermediary unless product decision | `HomeScientificResearchSearch.tsx`, `researchAgent.ts` |
| U7.3 | Flutter: `Accept-Language`; citation open via platform launcher if product requires | `http_client.dart`, `research_agent_screen.dart` |
| U7.4 | R33 OPEN DECISIONS documented (multi-answer, internal WSA links) | Product + UI only after decision |

**OPEN DECISION:** Google intermediary, multi-answer carousel, internal deep-links — **do not invent**.

**HUMAN GO** before API contract breaks.

---

## 14. Phase 8 — Security / Tenant Isolation

> **CURRENT AUTHORITATIVE (Phase 8A-1):** Public **write** tenant selection is **MODEL B** (server `PublicTenantResolver` + `TenantContext` public-bound + persistence mismatch guard). Client `organization` / `organization_id` are compatibility-only for writes. See `PHASE-8A-1-SECURITY-CONTRACT-ALIGNMENT.md` and R1.  
> **P8-F1 / P8-F2 remain FROZEN** (browse / plant-diagnosis org resolution — human decision required).  
> **HISTORICAL SNAPSHOT below** described the pre-MODEL-B client-authoritative write path and is preserved as evidence only — do not treat it as current write behavior.

### Critical path (executable design) — HISTORICAL (pre-MODEL B)

```
Public caller
  → POST /public/research-agent/query|synthesize (throttle:60,1, no auth.principal)
  → organization | organization_id (client-supplied)
  → AgriculturalResearchAgentController::resolveOrganization
       → Organization::findOrFail(id) | slug | WSA_PUBLIC_ORG_SLUG | first org
  → conductResearch($organizationId, …)
  → ScientificKnowledgePersistenceService::persist → LibraryItem.organization_id
```

| Question | Historical answer (pre-MODEL B) | Current answer (Phase 8A-1) |
|----------|----------------------------------|----------------------------|
| Who provides org for writes? | Client body | Server config (`WSA_PUBLIC_ORG_SLUG`) |
| Who authorizes public writes? | **Nobody** on public routes | Server bind + persist guard (no membership) |
| Who can write? | Anyone with valid org id/slug | Anyone hitting public URL → **public org only** |
| Invalid client org? | 404 via findOrFail | Ignored (compat); bind still uses public org |
| Absent client org? | Validation fails if neither org field | Allowed; server binds public org |

### Critical path (CURRENT — MODEL B writes)

```
Public caller
  → POST /public/research-agent/query|synthesize|feedback
     OR GET /public/field-crops/farming-needs-profile
  → PublicTenantResolver.bindPublicTenant()
  → TenantContext.isPublicBound()
  → conduct* / record(feedback) with bound public organizationId
  → ScientificKnowledgePersistenceService refuses public_tenant_mismatch
```

### Security units

| UNIT | OBJECTIVE | FILES | TESTS |
|------|-----------|-------|-------|
| U8.1 | Docs/registry align to MODEL B writes; freeze P8-F1/F2 | Phase 8A-1 alignment doc + architecture map/audit notes | Doc + contract reuse |
| U8.2 | Enforce binding (MODEL B) | `PublicTenantResolver`, controllers, persist guard | `PublicTenantBindingSecurityTest` |
| U8.3 | Rate/cost controls for expensive public compute | Named limiters + route split | `PublicExpensiveComputeProtectionTest` |
| U8.4 | Audit logging of public persist | **HISTORICAL deferral.** **CURRENT:** completed in Phase 8A-2 (`337c417`). See `PHASE-8-CLOSEOUT.md`. | `Phase8A2PublicSecurityAuditTest` |

**Feature flag:** Only if temporary dual-mode (open demo vs locked) required in production.

**HUMAN GO** mandatory before behavior change.  
**SECURITY RUNTIME VERIFICATION REQUIRED.**

**Ordering note:** ADR-021 lists Security as Phase 8, but **U8.1/U8.2 are blocking prerequisites for any public live matrix that persists**. Treat as **early Phase-8 gate**, not a new top-level phase. U8.2 write binding is **done on HEAD**; U8.1/U8.3 are Phase 8A-1.

---

## 15. Phase 9 — Legacy / WIP / Engineering

| Caller | Classification |
|--------|----------------|
| `AgriculturalResearchAgent` → `knowledgeEngine->execute` | **Conditional** (Home skip when DIRECT sufficient); **Always** for Crop profile shaping |
| `AgriculturalScientificKnowledgeEngine` → `CropKnowledgeEngine` | **Active** Crop path |
| Universal enrichLegacySynthesis | **Conditional** config |

**Removal criteria (future):** Crop no longer needs legacy profile shape **and** Home never needs legacy discovery **and** contract tests green — until then, do not delete.

| UNIT | OBJECTIVE |
|------|-----------|
| U9.1 | WIP isolation playbook (index-only); never destroy WIP |
| U9.2 | admin-mobile CI job (NEW-09) |
| U9.3 | Dead Consensus path documentation / activation policy |
| U9.4 | `.env.example` Fenix comment cleanup (NEW-10) — careful of dirty file |

---

## 16. Phase 10 — Full Verification / Release Gate

### Matrix (each row = PURPOSE / INPUT / EXPECTED / FAILURE / LAYER)

| ID | PURPOSE | INPUT | EXPECTED | FAILURE | LAYER |
|----|---------|-------|----------|---------|-------|
| V-SEM-1 | Multi-entity coverage | Wheat vs maize irrigation (AR/EN/TR/FR) | Both entities in variants | Missing entity B | QB/QUS |
| V-SEM-2 | Property survival | Germination temperature | Property in plan+variants | Generic drift | QUS/QB |
| V-LANG-1 | Answer language | AR UI Accept-Language=ar | Arabic prose | EN prose | QUS/Composer |
| V-LANG-2 | Flutter header | Flutter research query | Accept-Language sent; matching prose | Missing header / EN default | Flutter/API |
| V-LANG-3 | Persist locale | TR/FR question | locale≠forced-en if policy says so | locale=en always | Persist |
| V-SRCH-1 | Provider outcomes | Home query | duration_ms present when adapter latency known | Always null | RC-L |
| V-SRCH-2 | Crop sequential | Crop profile | No scholarly overlap | Overlap | Orchestrator |
| V-EVD-1 | Disposition single-writer | Home synthesis | One disposition authority | Conflicting fields | Disposition/Composer |
| V-ACC-1 | Limitations visible | Insufficient evidence | limitations/confidence on FE | Hidden | React |
| V-CROP-1 | Profile shape | farming-needs-profile | sections/load_state stable | Home-shaped JSON | Crop |
| V-SEC-1 | Cross-tenant write | Public query other org_id | Denied/bound | LibraryItem written | Security |
| V-PERF-1 | Latency ownership | Clean HEAD n≥10 | stage3 vs provider ms recorded | Guessing | Perf |
| V-CI-1 | Pipelines | PR | backend/FE/mobile/(admin-mobile)/security/OpenAPI | Silent skip | CI |

No numeric quality score. Release only when mandatory gates for changed phases pass.

---

## 17. Cross-Phase Dependencies

```
Phase1 (done) → G0 governance
     ↓
Phase8 early gate (U8.1/U8.2) ──blocks──→ public live matrices
     ↓
Phase2 semantic → Phase3 search → Phase4 evidence
     ↓                ↓
Phase5 language/composer (after 2+4; language decision can draft early)
     ↓
Phase6 Crop
     ↓
Phase7 API/UI
     ↓
Phase8 full + Phase9 cleanup
     ↓
Phase10 release
```

---

## 18. Safe Parallel Workstreams

| Parallel OK | Condition |
|-------------|-----------|
| U9.2 admin-mobile CI | No file overlap with semantic |
| Language **policy drafting** | No code until GO |
| U3.0 measurement design | Read-only / isolated harness |
| Documentation FF to ADR-021 (G0) | Docs-only; WIP-safe FF |

---

## 19. Mandatory Sequential Workstreams

1. G0 tip/ADR-021 alignment before claiming architecture baseline synced.  
2. Security public-persist gate before public persist verification.  
3. Semantic residual verify before provider-cutting for “accuracy”.  
4. Evidence ownership before Composer disposition edits.  
5. Language decision before claiming R32 language closed.  
6. Backend field stability before React/Flutter contract expansion.  
7. Crop contract suite before shared QB/Composer behavior changes.

---

## 20. Security Gates

- No public cross-tenant Library write.  
- Org resolution policy explicit and tested.  
- Expensive search not anonymous-unlimited beyond intended throttle.  
- Secrets never in client payloads.  
- HUMAN GO before any Phase 8 behavior commit.

---

## 21. Performance Gates

- No provider/variant/concurrency change without clean-HEAD RC-L measurements.  
- Historical 9–15s is **not** current truth.  
- Crop sequential behavior preserved unless measured Crop defect requires change + GO.

---

## 22. Regression Gates

- HomeSearchPlanSemanticCoverage + Phase9 multilingual.  
- Home disposition / accuracy / Stage3 contracts.  
- Crop profile Feature/E2E after any shared change.  
- Security negative tests after U8.x.  
- FE/Flutter contract tests after Phase 7.

---

## 23. Atomic Commit Boundaries

| Pattern | Rule |
|---------|------|
| One root-cause group per commit when possible | Avoid mega-commits |
| Docs-only commits allowed | e.g. ADR-021 already on remote |
| Never mix security + Composer + QB in one commit | — |
| Index-only from dirty files | Surgical patches only |
| Message style | Conventional `fix|feat|docs|security(scope): …` matching repo |

---

## 24. Rollback Strategy

- **Only** `git revert <sha>` of the atomic commit (new forward commit).  
- **Never** reset/restore/force-push to undo.  
- Feature flags only for temporary dual runtime modes (esp. security cutover).

---

## 25. Open Decisions (human)

1. WIP-safe fast-forward local branch to `1eaced9`?  
2. Import `#1–#31` from external source?  
3. Public research: bind to demo org only vs require auth?  
4. `answer_language`: follow UI locale (current) vs question language vs explicit request field?  
5. R33: multi-answer UI? Internal WSA link routing? Google intermediary (default **no**)?  
6. Flutter: must open citation URLs?  
7. Persist locales for `tr`/`fr` as first-class?

---

## 26. Blockers

| Blocker | Blocks |
|---------|--------|
| Dirty WIP (NEW-12) | Clean runtime claims |
| `#1–#31` UNRECOVERED | Complete numeric taxonomy |
| Security product intent | Safe public matrices |
| Language OPEN DECISION | R32 language close |
| Stale origin tracking | False “synced” status |
| No clean-HEAD latency matrix | Phase 3 optimization |
| HUMAN GO gates | All behavior phases |

---

## 27. Final Execution Order

### G0 — Governance / baseline sync
- **Root cause:** RC-H  
- **Problems:** NEW-01, NEW-11; enables ADR-021 locally  
- **Files:** none locally until authorized FF (remote already has ADR-021); optional tracking fetch  
- **Deps:** HUMAN GO (WIP-safe FF)  
- **Verification:** local tip == `1eaced9`; ADR-021 on tree  
- **Commit boundary:** already exists on remote; local FF only  
- **Next gate:** G1 policy  

### G1 — Security public org/persist (Phase 8 early gate)
- **Root cause:** RC-D  
- **Problems:** NEW-02, SEC-1…4  
- **Files:** `AgriculturalResearchAgentController.php` (`query`, `synthesize`, `resolveOrganization`); possibly middleware; `ScientificKnowledgePersistenceService.php` guard  
- **Deps:** Open Decision #3; G0 preferred  
- **Verification:** V-SEC-1; SECURITY RUNTIME VERIFICATION  
- **Commit boundary:** `security(research): …` atomic  
- **Next gate:** G2  

### G2 — Semantic residual verify + contracts (Phase 2)
- **Root cause:** RC-A (+ NEW-08)  
- **Problems:** R32 comparison residual, RC-2/3 residual, NEW-08  
- **Files:** QB/QUS/Plan/Planner **only if verify fails**; tests always  
- **Deps:** G1 before public persist probes  
- **Verification:** V-SEM-* on clean HEAD  
- **Commit boundary:** `fix(search)|test(semantic): …`  
- **Next gate:** G3  

### G3 — Search measure then policy (Phase 3)
- **Root cause:** RC-F, NEW-07  
- **Problems:** RC-1, NEW-07, STRUCT-01/05 as docs/selection  
- **Files:** Orchestrator/Selector/config **only after U3.0**  
- **Deps:** G2  
- **Verification:** V-SRCH-*, V-PERF-1  
- **Commit boundary:** separate measure docs vs behavior  
- **Next gate:** G4  

### G4 — Evidence ownership (Phase 4)
- **Root cause:** RC-E  
- **Problems:** NEW-03, STRUCT-04  
- **Files:** `HomeEvidenceLifecycleDisposition.php`, narrow `AnswerComposer.php`, Universal orchestrator; optional Agent surgical  
- **Deps:** G2–G3  
- **Verification:** V-EVD-1  
- **Commit boundary:** `fix(evidence): …`  
- **Next gate:** G5  

### G5 — Language + Composer accuracy (Phase 5)
- **Root cause:** RC-B, RC-C, R32 remainder  
- **Problems:** RC-4, NEW-04, NEW-05, R32  
- **Files:** QUS language, Persist locale, Composer, Flutter headers  
- **Deps:** Open Decision #4; G4  
- **Verification:** V-LANG-*, V-ACC-1  
- **Commit boundary:** language commit ≠ Composer mega-WIP  
- **Next gate:** G6  

### G6 — Crop contracts + shared gates (Phase 6)
- **Root cause:** STRUCT-02/03  
- **Problems:** Crop gaps; shared-change risk  
- **Files:** Crop controller/agent gates/tests; shared only with Crop proof  
- **Deps:** G2–G5 for shared layers  
- **Verification:** V-CROP-1 + Home regression  
- **Commit boundary:** Crop tests first, behavior second  
- **Next gate:** G7  

### G7 — API / React / Flutter / R33 (Phase 7)
- **Root cause:** RC-G  
- **Problems:** NEW-06, R33, client gaps  
- **Files:** FE/Flutter clients + UI; backend serialization  
- **Deps:** G5 field stability; Open Decisions #5–6  
- **Verification:** contract tests  
- **Commit boundary:** backend contract then clients  
- **Next gate:** G8  

### G8 — Full security + observability hardening (Phase 8 remainder)
- Completes U8.3–U8.4 after early gate.  

### G9 — Legacy map / WIP process / CI / env hygiene (Phase 9)
- NEW-09, NEW-10, legacy classification docs.  

### G10 — Full verification / release (Phase 10)
- Entire matrix; no release if V-SEC-1 or V-SEM-1 fail.

---

### Exact first implementation unit (DO NOT IMPLEMENT NOW)

**UNIT ID:** `U0 / G0`  
**PHASE:** Phase 1 closure / Phase 2 prelude (governance)  
**TITLE:** Align local baseline with remote ADR-021 tip  

**OBJECTIVE:** Make local branch tip equal `1eaced9` so ADR-021 is on the working tree as architecture authority, without destroying WIP.

**ROOT CAUSE:** RC-H (tip lag / stale tracking).

**FILES TO CHANGE:** None in application code. Resulting tree gains:
`docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md` via fast-forward of the already-pushed docs commit.

**FILES NOT TO CHANGE:** All application/test/config WIP; no ADR-019/020 rewrites.

**DEPENDENCIES:** Explicit **HUMAN GO** for WIP-safe fast-forward / fetch of `origin/phase-18-m18-ai-marketing-communications` (no reset/restore/clean/stash/checkout of WIP).

**ARCHITECTURAL CONTRACT:** ADR-021 becomes locally present; Crop IN SCOPE for remediation planning; ADR-019 remains historical text.

**TESTS:** N/A (docs-only). Verify: `git rev-parse HEAD` == `1eaced9`; file exists; WIP still dirty as before.

**REGRESSION / SECURITY / PERF:** None.

**ROLLBACK:** `git revert 1eaced9` (forward revert of docs) if ADR-021 must be withdrawn — only under GO.

**COMMIT BOUNDARY:** Commit already on remote; local operation is FF only.

**NEXT GATE (HISTORICAL as of this 2026-09-20 unit):** Open Decision #3 + **G1 / U8.1–U8.2** security public-org policy (first *application* behavior unit after G0).
**CURRENT:** G0/G1/MODEL B writes are closed. Do not reopen Decision #3 as if MODEL B were undecided. P8-F1 / P8-F2 remain frozen.

---

## Human GO gates (mandatory)

| Before | Gate |
|--------|------|
| G0 FF | WIP-safe sync authorization |
| G1 / any Phase 8 behavior | Security + product org policy |
| Phase 2 behavior | Semantic contract GO |
| Phase 3 behavior | Perf evidence GO |
| Phase 5 language/Composer | Language policy GO |
| Phase 6 Crop behavior | Crop architecture GO |
| Phase 7 API break | API contract GO |
| Any destructive Git | Explicit separate authorization (default: forbidden) |

---

## Document control

- **Supersedes for planning purposes:** informal phase chat plans that conflict with ADR-021.  
- **Does not modify:** ADR-019, ADR-020, ADR-021 source texts.  
- **Companion evidence:** Phase-1 forensic trio + BASELINE-RECONCILIATION + AUTHORITATIVE-PROBLEM-REGISTRY + REMEDIATION-DEPENDENCY-MAP.
