# CURRENT SYSTEM STATE — Phase 1 Baseline

- **Original Phase 1 audit date:** 2026-09-20 (UTC+3)
- **WIP inventory refresh date:** 2026-09-21 (point-in-time snapshot — documentation only)
- **Audit mode:** READ-ONLY forensic (Phase 1) + documentation gap closure
- **Authority:** `ADR-021-system-wide-fastest-safe-remediation-plan.md` (recovered 2026-09-21 from Git object `1eaced9`; local HEAD still `7486178` — G0 FF not performed)
- **In-repo architecture ADRs present:** ADR-019, ADR-020, ADR-021 (working-tree recovery), ADR-internet-first, WSA-Enterprise-Architecture-v1

---

## 1. Git baseline (PROVEN — refreshed 2026-09-21)

| Field | Value |
|-------|--------|
| Branch | `phase-18-m18-ai-marketing-communications` |
| Local HEAD | `74861783574b0fd407d964900af2ecc827fe542f` |
| Remote tracking HEAD | `74861783574b0fd407d964900af2ecc827fe542f` |
| Ahead / behind | `0 / 0` |
| Index | **CLEAN** (staged file count = 0) |
| Worktree | **DIRTY** (intentional protected WIP + documentation/G1 untracked) |
| Tracked modifications (unstaged) | **25** |
| Untracked paths | **439** (includes directories’ file trees such as `backend/e2e-tmp/**`) |
| Deleted tracked files | **None** observed |

### Mission-supplied vs actual

| Claim | Actual | Status |
|-------|--------|--------|
| Expected remote tip historically `1eaced9c…` for ADR-021 | Code HEAD remains `7486178…`; ADR-021 object exists; file restored to working tree 2026-09-21 without FF | **GOVERNANCE OPEN — G0 not executed** |
| ADR-021 | Present as untracked working-tree recovery from `1eaced9` | **RECOVERED IN WORKTREE** |

### Recent architecture / remediation commits (HEAD history, newest first)

| SHA | Subject |
|-----|---------|
| `7486178` | fix(search): restore comparison coverage and add stage3 latency observability (**RC-L**) |
| `e96c34a` | fix(search): restore Home comparison entity coverage (**RC-C**) — on branch + remote |
| `7508c5c` | fix(scientific): add Home evidence lifecycle disposition layer |
| `708debb` | fix(scientific): integrate Phase 9 multilingual semantic contract |
| `c9f05cf` | fix(scientific): add Home scholarly provider concurrency |
| `6500109` | fix(scientific): gate legacy fallback on direct evidence |
| `78364b1` | merge: integrate Phase 1 Crossref observability with ADR-019 and ADR-020 |
| `1935514` | docs: add ADR-020 |
| `54fa1f3` | docs: protect crop page pipeline (ADR-019) |

---

## 2. WIP inventory (PROVEN — refreshed 2026-09-21)

**Point-in-time note:** Prior Phase 1 snapshot recorded **19** tracked modifications. Live measurement on 2026-09-21 is **25** tracked modifications + **439** untracked paths. This section supersedes the 19-file inventory for baseline freshness only; it does **not** authorize modifying WIP.

### Staged

**None** (`git diff --cached --name-only` empty).

### Tracked modified — unstaged (25)

| Path | Classification |
|------|----------------|
| `backend/.env.example` | A — Protected pre-existing WIP (env template churn; do not stage with remediation) |
| `backend/app/Services/Agriculture/FieldCropTaxonomyCatalog.php` | A — Protected scientific/Crop-adjacent WIP |
| `backend/app/Services/Agriculture/Research/AgriculturalEntityCatalog.php` | A — Protected entity/semantic WIP |
| `backend/app/Services/Agriculture/Research/AgriculturalResearchAgent.php` | A — Protected Agent disposition WIP |
| `backend/app/Services/Agriculture/Research/QueryUnderstandingService.php` | A — Protected QUS WIP |
| `backend/app/Services/Agriculture/Research/ResearchPlanner.php` | A — Protected Planner WIP |
| `backend/app/Services/Agriculture/Research/Search/ScientificEvidenceDirectnessAssessor.php` | A — Protected directness WIP |
| `backend/app/Services/Agriculture/Research/Search/ScientificEvidenceRelevanceGate.php` | A — Protected relevance WIP |
| `backend/app/Services/Agriculture/Research/Search/ScientificResultRanker.php` | A — Protected ranking WIP |
| `backend/app/Services/Agriculture/Research/Search/ScientificSearchQueryBuilder.php` | A — Protected post–RC-C QB WIP (broader than committed RC-C) |
| `backend/app/Services/Agriculture/Research/Synthesis/AnswerComposer.php` | A — Protected Composer WIP |
| `backend/app/Services/Agriculture/Research/Validation/ClaimEvidenceMatcher.php` | A — Protected matcher WIP |
| `backend/app/Services/Agriculture/Research/Validation/EvidenceVerificationLayer.php` | A — Protected EVL WIP |
| `backend/tests/Feature/AgriculturalResearchAgentStage3Test.php` | A — Protected scientific test WIP |
| `backend/tests/Feature/CompositionalAgriculturalSemanticsTest.php` | A — Protected scientific test WIP |
| `backend/tests/Feature/ScientificResearchAnswerAccuracyTest.php` | A — Protected scientific test WIP |
| `backend/tests/Feature/ScientificResearchAnswerRelevanceTest.php` | A — Protected scientific test WIP |
| `backend/tests/Feature/ScientificUpstreamRetrievalFixTest.php` | A — Protected scientific test WIP |
| `backend/tests/Feature/SweetPotatoEntityResolutionTest.php` | A — Protected scientific test WIP |
| `backend/app/Http/Controllers/Api/AgriculturalResearchAgentController.php` | MODEL B public-tenant write binding — **on HEAD** (Phase 8A-1 docs align) |
| `backend/app/Http/Controllers/Api/PublicFieldCropCultivationController.php` | MODEL B Crop public-tenant write binding — **on HEAD** |
| `backend/app/Services/Agriculture/Research/Persistence/ScientificKnowledgePersistenceService.php` | MODEL B persistence defense-in-depth — **on HEAD** |
| `backend/app/Services/Tenancy/TenantContext.php` | Public-bound flag — **on HEAD** |
| `backend/config/wsa.php` | MODEL B + Phase 8A-1 public rate-limit config |
| `backend/tests/Feature/PublicTenantBindingSecurityTest.php` | MODEL B security matrix — **on HEAD** |

### Untracked — classification summary (439 paths)

| Class | Examples | Notes |
|-------|----------|-------|
| **A — Protected pre-existing scientific WIP** | Untracked Feature tests: `ScientificResultRankerGeoScopeTest`, `SemanticTargetAndSupportedValueContractTest`, `ScientificResearchSearchFlowTest`, `HomeMultilingualSemanticContractTest`, `GenericAgriculturalEntityArchitectureTest`, `ProviderActivationAndLevel4FeatureTest` | Do not delete or normalize |
| **A — Protected G1 security WIP (uncommitted)** | `PublicTenantContext.php`, `PublicTenantResolver.php`, `PublicTenantResolutionException.php`, `PublicTenantBindingSecurityTest.php`, `G1-PUBLIC-TENANT-BINDING-MODEL-B.md` | Application+test+doc for MODEL B; not Phase 1 docs |
| **B — Documentation-only Phase 1 / remediation governance** | `CURRENT-SYSTEM-STATE.md`, `SYSTEM-WIDE-FORENSIC-AUDIT.md`, `SYSTEM-ARCHITECTURE-MAP.md`, `ADR-021-…md` (recovered), `WSA-ENTERPRISE-PROBLEM-REGISTRY.md`, `AUTHORITATIVE-PROBLEM-REGISTRY.md`, `BASELINE-RECONCILIATION.md`, `MASTER-REMEDIATION-EXECUTION-PLAN.md`, `REMEDIATION-DEPENDENCY-MAP.md`, `SECURITY-BOUNDARY-DISCOVERY.md` | Docs only |
| **C — Historical / generated / temp artifacts** | `backend/e2e-tmp/**`, `admin-mobile/e2e-tmp/**`, `frontend/e2e-tmp/**`, root `*-test-*.txt`, `frontend-*-*.txt`, `docker-ps-out.txt`, `agent-tools-docker-settings.html`, `mobile/**/*.iml`, `.kotlin/errors/*` | Do not treat as product source |
| **D — Unknown / human review** | `backend/composer.lock` | Lockfile untracked — do not auto-commit |

### Overlap with audited subsystems

**YES — HIGH:** Dirty WIP still overlaps QUS, Planner, QB, Ranker, Directness, Relevance, Composer, EVL, Matcher, Catalog, Agent, plus G1 public-tenant files. Live semantics on dirty tree **may differ** from clean HEAD.

---

## 3. Repository structure (architectural)

| Root | Role |
|------|------|
| `backend/` | Laravel API, research agent, intelligence providers, migrations, PHPUnit |
| `frontend/` | React + Vite public site (Home, Plant Production / Crop) |
| `mobile/` | Flutter public app (includes Research Agent screen) |
| `admin-mobile/` | Flutter admin (CI gap) |
| `docs/` | ADRs, OpenAPI, phase notes |
| `docker/`, `nginx/`, root `docker-compose.yml` | Runtime topology |
| `.github/workflows/` | Root CI |
| `scripts/` | Ops / helpers |

---

## 4. Major runtime components (PROVEN)

| Component | Entry | Notes |
|-----------|-------|-------|
| Home research | `HomeScientificResearchSearch` → `POST /api/v1/public/research-agent/query` | Unauthenticated + `public-global` + `public-expensive-compute` (MODEL B write bind) |
| Crop profile | `FieldCropFarmingNeedsPanel` → `GET /api/v1/public/field-crops/farming-needs-profile` | Unauthenticated + `public-global` + `public-expensive-compute` (MODEL B write bind) |
| Agent | `AgriculturalResearchAgent::conductResearch` | Shared by Home + Crop |
| Universal orchestrator | In-process enrichment only | No dedicated public route |
| Stage 3 | `MultiSourceScientificSearchOrchestrator` | OA/CR/S2 (+ optional FAOSTAT) |
| Persistence | `ScientificKnowledgePersistenceService` | Writes `LibraryItem` on query/synthesize |

---

## 5. Known healthy areas (PROVEN or PARTIALLY PROVEN)

| Area | Status | Evidence |
|------|--------|----------|
| RC-C Home multi-entity coverage helper on HEAD | Committed | `e96c34a` + `HomeSearchPlanSemanticCoverageContractTest` |
| RC-L Stage-3 latency field mapping on HEAD | Committed | `7486178` + `Stage3LatencyObservabilityContractTest` |
| FAOSTAT Portal host allowlist (when enabled) | Code path | `FaoStatDeveloperPortalClient`; `fenix_retained=false` |
| Phase 9 multilingual contract test on HEAD | Committed historically | `708debb` / `Phase9HomeMultilingualSemanticContractTest` |
| Crop/Home QUS fork exists and is explicit | PROVEN | `QueryUnderstandingService` crop fields gate |
| React Home sends `Accept-Language` | PROVEN | `frontend/src/api/client.ts` |

---

## 6. Known broken / unproven areas

| Area | Status |
|------|--------|
| ADR-021 (mission authority) | **RECOVERED in worktree from `1eaced9`** (not yet on local HEAD commit; G0 FF pending) |
| Problem inventory `#1–#31` definitions | **NOT LOCALLY RECOVERABLE** — registry scaffolding in `WSA-ENTERPRISE-PROBLEM-REGISTRY.md` (phase map only) |
| R32 / R33 | Documented in `AUTHORITATIVE-PROBLEM-REGISTRY.md` |
| Post–RC-C live comparison matrix | **REQUIRES VERIFICATION** (not re-run this audit; persist mutates) |
| Per-provider / Stage-3-only ms on HEAD `7486178` | **NOT RUNTIME VERIFIED** (instrumentation now present; live measure not run) |
| Public `organization_id` → Library write | **HISTORICAL risk**; **WRITE MITIGATED (MODEL B)**. Browse/plant = P8-F1/P8-F2 frozen |
| Flutter research locale (`Accept-Language` absent) | **PROVEN mismatch risk** |
| Clean-HEAD semantics vs dirty WIP | **REQUIRES VERIFICATION** |
| Crop runtime answer quality | **NOT RUNTIME VERIFIED** this audit |

---

## 7. Test baseline

| Suite class | Notes |
|-------------|-------|
| Root CI | `php artisan test` (backend), frontend lint/test/build, Flutter `mobile/`, OpenAPI, `--group=security`, `--group=stage10` |
| Research contracts | Many Feature/Unit tests under `backend/tests` (Home coverage, Stage3, language, accuracy, disposition, etc.) |
| Untracked tests | Exist but **not** on HEAD — WIP |
| This Phase 1 audit | **Did not execute** test suites (read-only; avoid env mutation) |

---

## 8. Runtime baseline

| Item | Status |
|------|--------|
| Docker stack (prior sessions) | Historically healthy nginx/backend/postgres/redis/frontend |
| Live `POST /query` this audit | **NOT EXECUTED** (persistence side effect) |
| Prior latency matrix (~8.8–14.6s) | External forensic under `G:/tmp/wsa-project-engineering-audit` @ older HEAD `708debb` — **NOT** revalidated on `7486178` |
| Vite `localhost:5173` | May be running from earlier ops; not used as evidence for research accuracy |

---

## 9. Security baseline (static)

| Control | Finding |
|---------|---------|
| Public research + crop routes | No `auth.principal`; **HISTORICAL** shared `throttle:60,1`. **CURRENT (Phase 8A-1):** `public-global` (aggregate 60/min) + specialized `public-expensive-compute` / `public-browse` sub-buckets (not additive). |
| Org resolution | **HISTORICAL:** client-supplied `organization_id` / slug → `findOrFail`. **CURRENT writes:** MODEL B `PublicTenantResolver` (client org compatibility-only). Browse/plant = P8-F1/P8-F2 frozen. |
| Persistence | Query path can write LibraryItem for **bound public** org when eligible; public-bound mismatch rejected |
| CSRF | `research-agent/query` excepted; other stage POSTs may differ |
| Citation URL fetch | No Synthesis HTTP fetch of citation URLs found |

---

## 10. Performance baseline

| Claim | Status |
|-------|--------|
| Home total ~9–15s historically | PROVEN on prior matrix; **NOT RUNTIME VERIFIED on current HEAD** |
| Stage-3 multi-provider/variant dominant | LIKELY (architecture); provider ms historically null — RC-L adds mapping |
| Crop sequential scholarly | PROVEN in code (`isCropProfileIntent`) |

---

## 11. Documentation baseline

| Doc | Status vs code |
|-----|----------------|
| ADR-019 Crop protection | Present; **conflicts with mission ADR-021 Crop-in-scope** |
| ADR-020 8-phase Home repair | Present; **differs from mission “10 phases”** |
| ADR-021 | **Recovered to worktree** from `1eaced9` (2026-09-21); HEAD tip still `7486178` |
| Numbered `#1–#31` definitions | **Not recoverable** — IDs + phase map only in `WSA-ENTERPRISE-PROBLEM-REGISTRY.md` |
| R32 / R33 | Present in `AUTHORITATIVE-PROBLEM-REGISTRY.md` |
| External forensics (`G:/tmp/wsa-project-engineering-audit`) | Rich but **not** versioned in git |

---

## 12. Phase 1 document set

Phase 1 forensic artifacts:

1. `CURRENT-SYSTEM-STATE.md` (this file)
2. `SYSTEM-WIDE-FORENSIC-AUDIT.md`
3. `SYSTEM-ARCHITECTURE-MAP.md`

Documentation gap-closure companions (2026-09-21):

4. `ADR-021-system-wide-fastest-safe-remediation-plan.md` (recovered from `1eaced9`)
5. `WSA-ENTERPRISE-PROBLEM-REGISTRY.md` (`#1`–`#31` scaffolding; definitions NOT LOCALLY RECOVERABLE)
