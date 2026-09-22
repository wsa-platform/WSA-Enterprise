# SYSTEM-WIDE FORENSIC AUDIT — Phase 1

- **Date:** 2026-09-20
- **HEAD audited:** `74861783574b0fd407d964900af2ecc827fe542f`
- **Branch:** `phase-18-m18-ai-marketing-communications`
- **Mode:** READ-ONLY discovery (no remediation)
- **FACT vs INFERENCE:** labeled per finding

---

## 1. Executive findings

1. **Mission authority ADR-021** was absent on local HEAD `7486178` (PROVEN historically). On 2026-09-21 the ADR text was **recovered into the working tree** from Git object `1eaced9` without performing G0 FF — HEAD commit tip unchanged. In-repo ADR-019/ADR-020 remain historical/8-phase Home records; ADR-021 is the master 10-phase plan.
2. **Mission expected HEAD `1eaced9…` ≠ actual code HEAD `7486178…`** (PROVEN). ADR-021 docs commit exists; G0 FF not executed.
3. **Home and Crop share `AgriculturalResearchAgent::conductResearch`** after an early QUS fork (PROVEN).
4. **Public research endpoints are unauthenticated** with client-selectable `organization_id` that can drive Library persistence (PROVEN **historically** at audit HEAD) — **SECURITY REVIEW REQUIRED** at time of audit. **CURRENT (Phase 8A-1 / HEAD `53a8734`+):** MODEL B write binding is implemented; client org is compatibility-only for public writes. Residual: P8-F1 browse / P8-F2 plant (frozen). See `PHASE-8A-1-SECURITY-CONTRACT-ALIGNMENT.md`.
5. **Evidence classification is re-evaluated in multiple layers** (Ranker → Matcher/EVL → Validation → Composer) with **duplicated Home disposition** between Composer and `HomeEvidenceLifecycleDisposition` (PROVEN) — architectural debt.
6. **Comparison entity-B omission** was PROVEN historically (RC-2); **RC-C committed** `ensureHomeMultiEntitySemanticCoverage` on HEAD (`e96c34a`) — live post-fix matrix **REQUIRES VERIFICATION**.
7. **Answer language follows platform locale**, not question language (PROVEN mechanism). Flutter omits `Accept-Language` (PROVEN) → English answers likely under default locale.
8. **Problem inventory `#1–#31` statement text not found** (PROVEN absence of definitions). Phase mapping recovered from ADR-021; scaffolding in `WSA-ENTERPRISE-PROBLEM-REGISTRY.md` marks definitions **NOT LOCALLY RECOVERABLE**. R32/R33 recovered in `AUTHORITATIVE-PROBLEM-REGISTRY.md`.
9. **Dirty WIP heavily overlaps** the scientific stack under audit (PROVEN; inventory refreshed 2026-09-21: 25 tracked mods / 439 untracked) — risk of contaminating any future live verification.
10. **No application code was modified** in this Phase 1 audit beyond creating the three forensic markdown artifacts listed in Section 33 of the completion response.

---

## 2. Architecture findings

| Finding | Status | Confidence | Evidence | Why it matters |
|---------|--------|------------|----------|----------------|
| Dual provider registries (Intelligence vs Stage-3 scientific) | PROVEN | HIGH | `AgriculturalProviderRegistry` vs `ScientificSourceAdapterRegistry` | Selection/capability can diverge from Stage-3 execution |
| Home/Crop share agent after QUS | PROVEN | HIGH | `conductCropProfileResearch` → `conductResearch` | Shared bugs / shared fixes affect both |
| Universal orchestrator not on public HTTP | PROVEN | HIGH | Enrichment-only path | Documented ADR-002 surface ≠ public API |
| Plan serialization drops `contextInput` | PROVEN | HIGH | `KnowledgeQueryPlan::toArray()` | Stage HTTP round-trip / observability loss |
| Legacy sequence remap | PROVEN | HIGH | `mapSequenceForLegacyEngine` | Legacy sees different sequence than Stage plan |
| ADR-019 vs mission ADR-021 Crop scope | ARCHITECTURAL DEBT / OPEN | HIGH | ADR-019 present; ADR-021 absent | Phase planning cannot proceed safely without human decision |

---

## 3. Home findings

**Pipeline (PROVEN):**
`HomePage` → `HomeScientificResearchSearch` → `POST /api/v1/public/research-agent/query` → `AgriculturalResearchAgentController::query` → `conductResearch` → QUS → Planner (`buildGenericKnowledgePlan`) → Search → Validate → Compose → Home disposition → Persist → optional legacy / Universal enrich → JSON.

| Finding | Status | Confidence | First divergence | Impact |
|---------|--------|------------|------------------|--------|
| FE sends only `{organization, query}` | PROVEN | HIGH | API client | No crop selectors on Home |
| Throttle 60/min, no auth | PROVEN | HIGH | `routes/api.php` | Abuse / cost / tenant risk |
| Home scholarly concurrency for `generic_research` | PROVEN | HIGH | Orchestrator | Latency/architecture differ from Crop |
| Disposition Home-only | PROVEN | HIGH | `HomeEvidenceLifecycleDisposition::appliesTo` | Crop unchanged by design |
| FE ignores confidence/limitations/evidence_references | PROVEN | HIGH | `HomeScientificResearchSearch.tsx` | User sees thinner contract than API |

---

## 4. Crop findings

**Pipeline (PROVEN):**
`PlantProductionPage` → `FieldCropSelector` → `FieldCropFarmingNeedsPanel` → `GET …/field-crops/farming-needs-profile` → `PublicFieldCropCultivationController` → `conductCropProfileResearch` ≡ `conductResearch` → QUS crop context → `buildCropProfileKnowledgePlan` → shared search/validate/compose → **always** legacy profile shaping → Crop UI sections.

| Finding | Status | Confidence | Impact |
|---------|--------|------------|--------|
| Earliest Home/Crop split at QUS when `selected_crop_id`+`name` present | PROVEN | HIGH | Intent / planning / concurrency / disposition |
| Crop skips Home multi-entity coverage | PROVEN | HIGH | Comparison semantics Home-only |
| Crop forces sequential scholarly | PROVEN | HIGH | Latency profile differs |
| Crop always runs legacy post-processing | PROVEN | HIGH | Response shape ≠ Home |
| Crop runtime quality this audit | NOT RUNTIME VERIFIED | — | Mission now scopes Crop for future repair; no live Crop matrix here |
| Dirty `FieldCropTaxonomyCatalog` WIP | PROVEN | HIGH | Contaminates future Crop commits if staged blindly |

---

## 5. QUS findings

| Finding | Status | Confidence | Evidence |
|---------|--------|------------|----------|
| Crop profile short-circuit before free-text NLP | PROVEN | HIGH | `QueryUnderstandingService` |
| `answer_language` ← platform locale | PROVEN | HIGH | `resolvePlatformAnswerLanguage` |
| Question language detection separate | PROVEN | HIGH | `detectLanguage` / AKQ.language |
| Dirty WT land/causal/year WIP | PROVEN | HIGH | Large QUS diff vs HEAD | Live semantics may depend on WIP |
| Historical matrix QUS PASS on dirty tree | PROVEN (prior audit) | MEDIUM | External accuracy forensic @ older HEAD |

---

## 6. Planning findings

| Finding | Status | Confidence |
|---------|--------|------------|
| Generic vs crop profile plan builders | PROVEN | HIGH |
| `toAgriculturalResearchPlan` remaps domain/sequence; crop intent from context | PROVEN | HIGH |
| `toArray()` omits `contextInput` | PROVEN | HIGH |
| Planner land-classification WIP uncommitted | PROVEN | HIGH |

---

## 7. Search findings (Query Builder + Orchestrator)

| Finding | Status | Confidence | Notes |
|---------|--------|------------|-------|
| `MAX_VARIANTS=5` builder / `MAX_VARIANTS_PER_PROVIDER=2` orchestrator | PROVEN | HIGH | Expansion vs execution mismatch |
| `ADEQUATE_RESULT_COUNT=8` early stop | PROVEN | HIGH | |
| RC-C Home multi-entity helper on HEAD | PROVEN | HIGH | Live fix verification pending |
| Post–RC-C QB WIP (year omit, cultivation, semantic-target, etc.) | PROVEN | HIGH | Mixed-hunk risk |
| Historical RC-2 entity-B omission | PROVEN @ pre-RC-C | HIGH | May be remediated — **REQUIRES VERIFICATION** |

---

## 8. Provider findings

| Provider | Default | Stage-3 default select | Notes |
|----------|---------|------------------------|-------|
| OpenAlex | enabled | yes | |
| Crossref | enabled | yes | |
| Semantic Scholar | enabled | yes | |
| FAOSTAT Portal | **disabled** | only if enabled + policy | `faostatservices.fao.org`; Fenix retired in runtime flags |
| Consensus | key optional | **not** in default selector | legacy/optional |
| Open-Meteo | enabled | Intelligence registry, not Stage-3 list | |
| Free Search MCP | disabled | WebSearch binding when on | |

| Finding | Status |
|---------|--------|
| Dual registries | PROVEN |
| Home overlap OA/CR/S2 after FAOSTAT | PROVEN |
| `.env.example` still documents Fenix `FAO_*` alongside Portal | PROVEN (config drift) |

---

## 9. FAOSTAT findings

| Finding | Status | Confidence |
|---------|--------|------------|
| Canonical base `https://faostatservices.fao.org/api/v1` | PROVEN | HIGH |
| Host allowlist + HTTPS | PROVEN | HIGH |
| `fenix_retained = false` | PROVEN | HIGH |
| Stage-3 ignores retired `FAO_ENABLED` | PROVEN (selector comments/policy) | HIGH |
| Operator confusion from Fenix comments in `.env.example` | PROVEN | MEDIUM |
| Live Portal behavior this audit | NOT RUNTIME VERIFIED | — |

---

## 10. Evidence findings

Lifecycle (conceptual): Discover → Normalize → Gate/Directness → Rank/filter → Validate/Match/EVL → Compose eligibility → Disposition (Home) → Cite → Persist.

| Finding | Status | Confidence |
|---------|--------|------------|
| Gate+Directness invoked in Ranker, Matcher/EVL, Validation, Composer | PROVEN | HIGH |
| Dual vocabularies (DIRECT/SUPPORTING vs SUPPORTED/RELATED aliases) | PROVEN | MEDIUM |
| Composer disposition metadata duplicated vs HomeDisposition service | PROVEN | HIGH |
| Historical funnel raw→usable thinning | PROVEN (prior deep probes) | MEDIUM (older HEAD) |
| Confusion timeout vs empty vs unavailable | PARTIALLY PROVEN | MEDIUM | Outcome statuses exist; UX mapping thin on FE |

### RC-3 — Under-retrieval / evidence lifecycle (historical)

| Field | Value |
|-------|--------|
| Status | **PROVEN** (historical forensic) |
| Mechanism | Comparison second-entity omission at search-query construction (RC-2) cascades so Entity B evidence cannot be validated/cited |
| Historical observation | Forensic Audit V2 recorded a retrieval-funnel case in which **maize was never retrieved** during the relevant comparison search path (EN6 / multi-entity probes) |

**Evidence sources (authoritative, external-to-git project forensics):**

- `G:/tmp/wsa-project-engineering-audit/PROJECT-ENGINEERING-MASTER-FORENSIC-REPORT.md` — RC-3 **PROVEN** “(funnel re-measured; maize never retrieved)”; EN6 “**maize never retrieved**”
- `G:/tmp/wsa-project-engineering-audit/EVIDENCE-LIFECYCLE-REPORT.md` — “Composer cannot cite maize evidence that was never retrieved”
- `G:/tmp/wsa-project-engineering-audit/CROSS-ROOT-CAUSE-ANALYSIS.md` — “maize never retrieved”

Historical forensic evidence included a retrieval-funnel case in which maize was never retrieved during the relevant search path. This is documented as evidence for the RC-3 under-retrieval/evidence-lifecycle finding; it does not by itself constitute a separate root cause from the RC-2 search-variant omission that prevented Entity B from being queried.

RC-3 status remains **PROVEN**. Live post–RC-C residual verification is a separate concern (`REQUIRES VERIFICATION`).

---

## 11. Ranking findings

| Finding | Status | Confidence |
|---------|--------|------------|
| Ranker embeds geo-scope, germination, inventory/evidence adjusts (esp. WIP) | PROVEN | HIGH |
| Domain policy mixed into ranking layer | ARCHITECTURAL DEBT | HIGH |
| Untracked `ScientificResultRankerGeoScopeTest` | PROVEN | HIGH |

---

## 12. Composer findings

| Finding | Status | Confidence |
|---------|--------|------------|
| Owns synthesis text, claims, citations, sufficiency framing | PROVEN | HIGH |
| Reuses gate/assessor/EVL | PROVEN | HIGH |
| Home lifecycle metadata helpers | PROVEN | HIGH |
| Large uncommitted Composer WIP | PROVEN | HIGH |
| Overlap with disposition service | PROVEN | HIGH |

---

## 13. Answer Accuracy findings (R32)

**R32 inventory doc:** NOT FOUND in repo. Treated as **named requirement from mission**, evidence from prior forensics + code.

| Failure mode | Status | First divergence | Impact |
|--------------|--------|------------------|--------|
| Comparison answer missing entity B | HISTORICALLY PROVEN; post-RC-C **REQUIRES VERIFICATION** | QueryBuilder variants | Incomplete scientific answer |
| Answer language ≠ question language when locale=en | PROVEN mechanism | QUS `answer_language` | Perceived inaccuracy for AR/TR/FR |
| Partial support presented with scientific_generated | OBSERVED historically | Composer sufficiency | Overconfidence risk |
| Germination numeric spread across sources | OBSERVED | Evidence plurality | “Wrong number” perception |
| FE omits limitations/confidence | PROVEN | React Home UI | Accuracy signals hidden |

---

## 14. Language findings

| Finding | Status | Confidence |
|---------|--------|------------|
| Platform locale drives answer language | PROVEN | HIGH |
| Persistence: non-`ar` → `locale='en'` | PROVEN | HIGH | `ScientificKnowledgePersistenceService` |
| React sends Accept-Language | PROVEN | HIGH |
| Flutter Arabic UI, no Accept-Language | PROVEN | HIGH |
| TR/FR locale collapse in LibraryItem | PROVEN | HIGH |

---

## 15. API findings

| Endpoint | Auth | Org | Notes |
|----------|------|-----|-------|
| POST `/public/research-agent/query` | none | required | Persist path |
| POST `…/plan|search|validate` | none | not required | Stage tools |
| POST `…/synthesize` | none | required | Persist risk |
| GET `/public/field-crops/farming-needs-profile` | none | resolved public org | Crop |

Contract skew: backend synthesis fields ≫ React types ≫ Flutter displayed fields (PROVEN).

---

## 16. React findings

| Finding | Status |
|---------|--------|
| Single-answer + citation `<a href target=_blank>` | PROVEN |
| No multi-answer UI; no Google intermediary | PROVEN (absence) |
| Unused typed fields: confidence, limitations | PROVEN |
| Crop panel separate client + section UI | PROVEN |

---

## 17. Flutter findings

| Finding | Status |
|---------|--------|
| `ResearchAgentScreen` richer field display than React | PROVEN |
| Citation URLs not launched | PROVEN (absence of url_launcher usage) |
| Locale header missing | PROVEN |
| Plant production taxonomy ≠ research answers | PROVEN |

---

## 18. Navigation / link findings (R33)

**R33 inventory doc:** NOT FOUND. Mission-named concerns audited from code:

| Concern | Current behavior | Status |
|---------|------------------|--------|
| Multiple answers | Not implemented | PROVEN absence |
| Visual separation | Single block | PROVEN |
| Citation links | Direct external href (React) | PROVEN |
| Google intermediary | Not present | PROVEN absence |
| Flutter open URL | Not present | PROVEN absence |
| URL sanitization beyond browser/`rel=noopener` | Limited | PARTIALLY PROVEN |
| Internal WSA topic links | Not found in Home research UI | PROVEN absence |

---

## 19. Security findings

| ID | Finding | Status | Confidence |
|----|---------|--------|------------|
| SEC-1 | Public research + crop unauthenticated | PROVEN | HIGH |
| SEC-2 | Client `organization_id` → org resolve → persist | PROVEN | HIGH |
| SEC-3 | Fallback to first Organization by id | PROVEN | HIGH |
| SEC-4 | Expensive multi-provider search behind public throttle only | PROVEN | HIGH |
| SEC-5 | Citation SSRF via Composer fetch | NOT FOUND (no fetch) | MEDIUM |
| SEC-6 | FAOSTAT host allowlist | PROVEN mitigation | HIGH |

**SECURITY RUNTIME VERIFICATION REQUIRED** for tenant write abuse and cost amplification (not exploited here).

---

## 20. Persistence / database findings

| Finding | Status |
|---------|--------|
| LibraryItem written from public query/synthesize | PROVEN |
| locale collapse tr/fr→en | PROVEN |
| Explicit field set (not request fill) on research persist path | PROVEN |
| Broad `$fillable` on LibraryItem | PROVEN | Risk if other controllers mass-assign |

---

## 21. Legacy findings

| Finding | Status |
|---------|--------|
| Legacy engine gated for Home when DIRECT sufficiency passes | PROVEN |
| Crop always uses legacy profile shaping | PROVEN |
| Sequence remap to legacy RESEARCH_SEQUENCE | PROVEN |
| Dual modern/legacy responsibilities | ARCHITECTURAL DEBT | HIGH |

---

## 22. Testing findings

| Gap | Status |
|-----|--------|
| Strong backend research Feature/Unit coverage on HEAD | PARTIALLY PROVEN |
| Untracked companion tests not in CI until committed | PROVEN |
| React research E2E thin vs unit | REQUIRES VERIFICATION |
| Flutter research locale/contract tests | GAP LIKELY |
| admin-mobile not in root CI | PROVEN |
| This audit did not run suites | N/A |

---

## 23. CI findings

Root `.github/workflows/ci.yml`: backend, frontend, mobile, openapi, security group, stage10, docker-validate.
**Gaps:** admin-mobile absent; nested `backend/.github` likely inactive; research-specific jobs not isolated.

---

## 24. Documentation drift

| Doc | Drift |
|-----|-------|
| ADR-021 | Recovered to worktree from `1eaced9` (2026-09-21); still absent from local HEAD tip `7486178` (G0 FF not performed) |
| ADR-019 | Historical Crop protection vs ADR-021 Crop-in-scope |
| ADR-020 | 8-phase Home plan; **not** master (ADR-021 is master) |
| `#1–#31` definitions | Still NOT LOCALLY RECOVERABLE; phase map + scaffolding in `WSA-ENTERPRISE-PROBLEM-REGISTRY.md` |
| R32 / R33 | Present in `AUTHORITATIVE-PROBLEM-REGISTRY.md` |
| External tmp forensics | Not in git; older HEAD relative to current |

---

## 24A. Docker forensic depth (documentation gap closure — 2026-09-21)

**Source of truth:** root `docker-compose.yml`, `backend/Dockerfile`, `frontend/Dockerfile`, `nginx/Dockerfile`, `nginx/Dockerfile.prod`, `nginx/default.conf`, root `Dockerfile`.
**Status vocabulary:** OBSERVED | POTENTIAL | NOT VERIFIED | NOT APPLICABLE
**No Docker configuration was changed for this section.**

### 1. Docker topology (OBSERVED)

Compose network `wsa` (bridge) connects: `backend`, `frontend`, `nginx`, `postgres`, `redis`, `queue`, `scheduler`, and optional profile service `backend-test`.

Public edge: host port **8079 → nginx:80**. Postgres **5432** and Redis **6379** published to host.

### 2. Service inventory (OBSERVED)

| Service | Image/build | Responsibility | App component |
|---------|-------------|----------------|---------------|
| `backend` | `./backend` (PHP 8.4-FPM) | Laravel API / research agent | Backend |
| `frontend` | `./frontend` (Node build → nginx alpine) | Static React SPA | React |
| `nginx` | `./nginx` | Reverse proxy `/api` → PHP-FPM, `/` → frontend | API + UI edge |
| `postgres` | `postgres:16-alpine` | Primary DB `wsa_enterprise` | Database |
| `redis` | `redis:7-alpine` | Cache/queue backend | Queue/cache |
| `queue` | same backend image | `php artisan queue:work redis` | Async jobs |
| `scheduler` | same backend image | `php artisan schedule:work` | Scheduled tasks |
| `backend-test` | profile `test` | `php artisan test` against `wsa_enterprise_test` | CI/test |

Flutter / `admin-mobile` / `mobile`: **NOT APPLICABLE** as Compose services (not containerized in this compose file).

### 3. Network topology (OBSERVED)

- Single user-defined bridge network: `wsa`.
- Service DNS names used for dependencies (`postgres`, `redis`, `backend:9000`, `frontend:80`).
- Nginx `fastcgi_pass backend:9000`; UI `proxy_pass http://frontend:80`.

### 4. Persistence / volumes (OBSERVED)

| Volume | Purpose |
|--------|---------|
| `postgres_data` | PostgreSQL data |
| `redis_data` | Redis data |
| `backend_vendor` | Composer vendor bind-isolated from host bind-mount |
| Bind `./backend:/var/www/html` | Live backend code (backend, queue, scheduler, backend-test) |
| Bind `./docs` | Read-only docs into backend/test containers |
| Bind `./nginx/default.conf` | Nginx config |

### 5. Health / readiness (OBSERVED)

| Service | Healthcheck |
|---------|-------------|
| postgres | `pg_isready` |
| redis | `redis-cli ping` |
| backend | `php artisan about` grep Application |
| frontend | `wget` localhost |
| nginx | `curl` `/api/v1/health` |
| queue / scheduler | process grep for artisan workers |

`depends_on` uses `condition: service_healthy` for postgres/redis/backend/frontend where declared.

### 6. Environment dependencies (OBSERVED)

- `backend` / `queue` / `scheduler`: `env_file: ./backend/.env` plus `REDIS_HOST`/`DB_HOST` overrides.
- `backend-test`: inline testing env; `FORBIDDEN_TEST_DATABASES=wsa_enterprise`; separate DB name `wsa_enterprise_test`.
- Frontend build args: `VITE_API_URL`, `VITE_SHOW_DEMO_LOGIN`.
- Backend image installs Free Search MCP tooling (`uv` / `free-search-mcp`) — OBSERVED in Dockerfile.

### 7. Runtime / test topology (OBSERVED)

- Default `docker compose up` path: nginx:8079 → API + React.
- Tests: Compose profile `test` (`backend-test`) or `docker compose exec backend php artisan test`.
- Nginx PHP timeouts set to **90s** (`fastcgi_read_timeout` et al.), documented as aligned with agricultural search time budget — OBSERVED in `nginx/default.conf`.

### 8. Evidence-backed observations

| Observation | Status |
|-------------|--------|
| Healthy dependency ordering via healthchecks | OBSERVED |
| Host exposure of Postgres/Redis ports | OBSERVED (dev convenience; production hardening NOT VERIFIED) |
| Vendor volume prevents host vendor overwrite | OBSERVED |
| Search/provider latency is application-layer, not proven as Docker misconfiguration | OBSERVED |
| Prior sessions reported stack healthy | OBSERVED historically; live health NOT VERIFIED this documentation pass |
| `backend-test` shares postgres instance with different DB name | OBSERVED |
| Root `Dockerfile` / `nginx/Dockerfile.prod` exist alongside compose builds | OBSERVED; prod path NOT VERIFIED |

### 9. Unknowns

- Production Compose vs this file (NOT VERIFIED).
- Redis persistence / AOF intent (NOT VERIFIED beyond default volume).
- Live container health at documentation time (NOT VERIFIED).
- Provider egress firewall differences by environment (NOT VERIFIED).

### 10. Relationship to RC-1 … RC-4

| Finding | Docker relationship |
|---------|---------------------|
| RC-1 Latency (~9–15s Home) | Unrelated as proven cause. Nginx 90s timeout is enabling budget, not the measured latency driver. |
| RC-2 Comparison entity-B | Unrelated (QueryBuilder / search-plan). |
| RC-3 Evidence / maize under-retrieval | Unrelated (evidence pipeline). |
| RC-4 Language / Accept-Language | Unrelated (client/QUS locale). |

### 11. Causal-status conclusion

**NO DOCKER CAUSALITY PROVEN.**

---

## 25. WIP contamination risks

- Any live matrix on dirty WT may reflect Catalog/QUS/Composer WIP, not HEAD alone.
- Staging whole dirty files would ship unfinished land mega / geo / Composer WIP.
- Agent disposition wiring uncommitted while Universal path already has disposition on HEAD.

---

## 26. Problems `#1–#31`

**STATUS:** Statement text for `#1`…`#31` remains **NOT LOCALLY RECOVERABLE**.
**PHASE MAP:** Recovered from ADR-021 §4 into `docs/architecture/WSA-ENTERPRISE-PROBLEM-REGISTRY.md` (2026-09-21).
**ACTION:** Import original problem definitions before treating numeric IDs as titled defects.
**RELATED:** ADR-020 phases use a *different* numbering scheme — do not conflate.

Do not invent titles. Do not renumber NEW-/RC- findings into `#1`–`#31` without human import.

---

## 27. R32 — Answer Accuracy

| Field | Value |
|-------|-------|
| STATUS | ARCHITECTURAL DEBT + PARTIALLY REMEDIATED (RC-C) + OPEN verification |
| SEVERITY | HIGH (user trust) |
| CONFIDENCE | HIGH on mechanisms; MEDIUM on post-RC-C residual |
| FIRST DIVERGENCE | Historical: QueryBuilder multi-entity; Language: QUS platform locale |
| AFFECTED | QB, Composer, FE presentation, providers (cascade) |
| RUNTIME VERIFICATION REQUIRED | YES |

---

## 28. R33 — Answer Presentation & Navigation

| Field | Value |
|-------|-------|
| STATUS | PROVEN thin client presentation; missing product features relative to mission checklist |
| SEVERITY | MEDIUM (UX / discoverability) |
| CONFIDENCE | HIGH |
| FIRST DIVERGENCE | Frontend render (`HomeScientificResearchSearch`); Flutter non-interactive citations |
| AFFECTED | React, Flutter |
| RUNTIME VERIFICATION REQUIRED | Optional UI walkthrough |

---

## 29. Newly discovered / independently confirmed problems

| NEW-ID | Description | Root cause | Evidence | First divergence | Impact | Confidence | Phase hint |
|--------|-------------|------------|----------|------------------|--------|------------|------------|
| NEW-01 | ADR-021 missing; ADR conflict | Docs not landed | `docs/architecture` listing | Docs | Phase planning blocked | HIGH | Phase 1/2 governance |
| NEW-02 | Public org_id → Library write | Unauthenticated resolveOrganization (historical) | Controller | API entry | Tenant/data integrity | HIGH | Security — **WRITE MITIGATED (MODEL B)**; browse/plant residual = P8-F1/P8-F2 frozen |
| NEW-03 | Disposition ownership duplication | Composer + HomeDisposition | Both classes | Post-compose | Inconsistent metadata | HIGH | Evidence/Composer |
| NEW-04 | Locale persistence collapses TR/FR | `locale = ar?ar:en` | Persistence service | Persist | Wrong library language | HIGH | Language/persistence |
| NEW-05 | Flutter no Accept-Language | Http client headers | `http_client.dart` | Mobile API | EN answers + AR chrome | HIGH | Mobile/language |
| NEW-06 | FE drops accuracy signals | UI ignores confidence/limitations | HomeScientificResearchSearch | React | Misleading UX | HIGH | R33 |
| NEW-07 | Variant build 5 vs run 2 | Separate constants | QB + Orchestrator | Search | Wasted planning / confusion | MEDIUM | Search perf |
| NEW-08 | Plan JSON omits contextInput | toArray design | KnowledgeQueryPlan | Serialization | Stage API incomplete | HIGH | API/contracts |
| NEW-09 | admin-mobile CI gap | ci.yml scope | `.github/workflows/ci.yml` | CI | False confidence | HIGH | CI |
| NEW-10 | Fenix comments remain in .env.example | Doc drift | `.env.example` | Config | Operator misconfig | MEDIUM | FAOSTAT/ops |
| NEW-11 | Mission HEAD SHA stale | Process | git rev-parse | Baseline | Audit targeting error | HIGH | Process |
| NEW-12 | Dirty WIP overlaps audit surface | Ongoing land/geo/Composer work | git status | WT | Contaminated verification | HIGH | Process |

---

## 30. Root-cause graph (verified links)

```
Semantic multi-entity loss (historical RC-2 @ QueryBuilder)
  → missing provider queries for entity B
  → under-retrieval / thin comparison evidence (RC-3 cascade)
  → incomplete comparison answers (R32)
  [RC-C on HEAD may cut this edge — REQUIRES VERIFICATION]

Platform locale answer_language (QUS)
  → Composer English prose under EN locale
  → R32 language mismatch
  → Flutter missing Accept-Language amplifies

Stage-3 multi-provider/variant architecture
  → high wall-clock latency (historical RC-1)
  → RC-L observability enables ownership measurement (not yet re-measured)

Public unauthenticated org resolve
  → LibraryItem writes to attacker-chosen org (SEC-2)
  → integrity / abuse

Evidence multi-layer reclassification + disposition duplication
  → inconsistent usable/cited/disposition counts
  → R32 confidence/limitation opacity when FE hides fields
```

---

## 31. Open Questions

| QUESTION | WHY | EVIDENCE | UNKNOWN | OWNER | PHASE |
|----------|-----|----------|---------|-------|-------|
| Where is ADR-021? | Mission authority | File missing | Intentional delay vs omission | Architecture owner | Immediate |
| Where is `#1–#31` registry? | Taxonomy required | Not in docs | External only? | Product/Eng | Immediate |
| Does RC-C fully fix live comparison answers? | R32 residual | Code on HEAD; no live matrix | Residual gaps in WIP QB | Research eng | Phase 2 |
| Should Crop remain protected (ADR-019) or enter repair (mission)? | Scope | ADR conflict | Policy | Architecture owner | Immediate |
| Is public Library persistence intentional for demo org only? | Security | Code allows any org id | Product intent | Security + Product | Security |
| Should answer_language follow question language? | R32/RC-4 | Platform-locale design | Product decision | Product | Language |
| Re-measure latency with RC-L fields on HEAD? | Perf | Instrumentation shipped | Numbers unknown | Perf eng | Perf |

---

## 32. Recommended Phase 2 scope (recommendation only — DO NOT START)

1. **Governance:** Land or reject ADR-021; reconcile ADR-019; import `#1–#31` registry into git.
2. **Security baseline design:** Public research org binding / auth / rate & cost controls (design only until GO).
3. **Verification matrix (isolated):** Post–RC-C comparison + language + Crop smoke on clean HEAD (persist-safe harness).
4. **Evidence ownership ADR:** Single disposition owner; reduce duplicate classification.
5. **Client contract:** Align React/Flutter with synthesis fields needed for R32/R33.
6. **Do not** stage dirty WIP land/Composer mega without surgical isolation.

---

## Completion checklist (Phase 1)

Git baseline, WIP inventory, architecture map, Home/Crop/shared traces, API/security/perf/semantic/entity/QB/provider/FAOSTAT/evidence/ranking/composer/accuracy/language/React/Flutter/navigation/DB/legacy/tests/CI/docs, `#1–#31` attempted (absent), R32/R33, new problems, root-cause graph, open questions, three docs created; **no application/test/config code changes; no commit/push.**
