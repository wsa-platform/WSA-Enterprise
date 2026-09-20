# SYSTEM ARCHITECTURE MAP — Phase 1

- **HEAD:** `74861783574b0fd407d964900af2ecc827fe542f`
- **Mode:** As-implemented map from code (not aspirational)
- **Legend:** SOURCE → TARGET | CONTRACT | DATA | RISK

---

## HOME PIPELINE

```
HomePage (React)
  → HomeScientificResearchSearch
    CONTRACT: user free-text query
    DATA: query string, i18n chrome, Accept-Language via api client
    RISK: thin rendering of API fields

  → queryPublicResearchAgent (POST /api/v1/public/research-agent/query)
    CONTRACT: { organization|organization_id, query }
    DATA: no crop selectors
    RISK: public, unauthenticated

  → AgriculturalResearchAgentController::query
    CONTRACT: validate org + query; throttle:60,1
    DATA: Organization model
    RISK: client-chosen organization_id (SEC)

  → AgriculturalResearchAgent::conductResearch
    CONTRACT: full Stage 2–5 + persist
    DATA: KnowledgeQueryPlan → search/validation/synthesis reports
    RISK: expensive; writes LibraryItem

  → QueryUnderstandingService::understand (Home free-text path)
    CONTRACT: AKQ (language, entities, intent, constraints, answer_language)
    DATA: semantic target / comparison flags / location / year
    RISK: dirty WIP; answer_language = platform locale

  → ResearchPlanner::buildGenericKnowledgePlan
    CONTRACT: KnowledgeQueryPlan (internet-first, topics, evidenceRequirements)
    DATA: contextInput from input array
    RISK: toArray() later drops contextInput

  → AgriculturalScientificSearchService / MultiSourceScientificSearchOrchestrator
    CONTRACT: ScientificSearchExecutionReport
    DATA: variants (≤5 built, ≤2/provider run), source_outcomes, timings (RC-L)
    RISK: latency; provider failure modes

  → AgriculturalScientificValidationService
    CONTRACT: EvidenceValidationExecutionReport
    DATA: matched/usable evidence, directness
    RISK: multi-layer reclassification

  → AnswerComposer::compose
    CONTRACT: AnswerSynthesisExecutionReport
    DATA: answer, citations, claims, confidence, limitations
    RISK: ownership overlap with disposition; dirty WIP

  → HomeEvidenceLifecycleDisposition::applyToSynthesis
    CONTRACT: Home-only disposition metadata
    DATA: disposition enums / counts
    RISK: duplicate logic vs Composer helpers

  → ScientificKnowledgePersistenceService::persist
    CONTRACT: LibraryItem write
    DATA: locale ar|en collapse; metadata
    RISK: public write; language collapse

  → [optional] Legacy KnowledgeEngine / Universal enrich
    CONTRACT: merge or short-circuit
    DATA: legacy discovery vs DIRECT gate
    RISK: dual answer sources

  → JSON → React render (answer + citations href)
```

---

## CROP PIPELINE

```
PlantProductionPage
  → FieldCropSelector (category field-crops)
    → FieldCropFarmingNeedsPanel
      CONTRACT: selected crop + knowledge_option
      DATA: selected_crop_id/name, scientificName
      RISK: Crop UX complexity

      → GET /api/v1/public/field-crops/farming-needs-profile
        CONTRACT: query params; throttle; no auth
        DATA: crop selectors
        RISK: public expensive research

      → PublicFieldCropCultivationController::farmingNeedsProfile
        → AgriculturalResearchAgent::conductCropProfileResearch
          ≡ conductResearch (shared)

      → QueryUnderstandingService::understandCropProfileContext
        FIRST DIVERGENCE from Home (PROVEN)
        CONTRACT: crop-bound AKQ
        DATA: selected crop identity

      → ResearchPlanner::buildCropProfileKnowledgePlan
        CONTRACT: sections from CropKnowledgeSectionCatalog
        DATA: crop_profile intent via contextInput
        RISK: shared planner file with Home WIP

      → Search / Validate / Compose (shared classes)
        CONTRACT: same Stage 3–5 types
        DATA: isCropProfileIntent gates concurrency, multi-entity, disposition
        RISK: shared regressions

      → Legacy CropKnowledgeEngine profile shaping (always for Crop)
        CONTRACT: sections / load_state / references
        DATA: Crop UI profile shape ≠ Home answer shape
        RISK: dual response contracts

      → FieldCropFarmingNeedsPanel render
```

---

## SHARED SERVICES

| SOURCE | TARGET | CONTRACT | DATA | RISK |
|--------|--------|----------|------|------|
| Home + Crop | AgriculturalResearchAgent | conductResearch | Full reports | Cross-pipeline coupling |
| Agent | QUS / Planner / Search / Validation / Composer / Persist | Stage interfaces | Plans & reports | Shared WIP contamination |
| Composer / Ranker / Matcher | RelevanceGate + DirectnessAssessor | Classification labels | Haystacks / plan | Duplicate evaluation |
| Agent (Home) | HomeEvidenceLifecycleDisposition | applyToSynthesis | Disposition | Overlap with Composer |
| Agent | UniversalAnswerOrchestrator | optional enrich | Universal fields | Unused by FE |
| Agent | AgriculturalScientificKnowledgeEngine | legacy execute | Legacy plan | Sequence remap loss |

---

## SEARCH

| SOURCE | TARGET | CONTRACT | DATA | RISK |
|--------|--------|----------|------|------|
| KnowledgeQueryPlan | ScientificSearchQueryBuilder | variants list | Entity/property/location strings | Semantic dilution; WIP |
| QueryBuilder | Orchestrator | ≤5 variants | Strings | Only ≤2 executed/provider |
| Orchestrator | Adapters | ScientificSourceSearchOutcome | Results + observability.latency_ms → duration_ms | Timeouts/429 |
| Orchestrator | Deduplicator / Ranker | ranked results | Scores / geo | Policy-in-ranker |
| Orchestrator | ScientificSearchExecutionReport | timings + planSummary | stage3_elapsed_ms | Observability only |

---

## PROVIDERS

| SOURCE | TARGET | CONTRACT | DATA | RISK |
|--------|--------|----------|------|------|
| ScientificSourceSelector | Adapter registry | source keys | openalex, crossref, semantic_scholar, ±fao_stat | Consensus unused by default |
| Intelligence registry | Open-Meteo / MCP / disease | capability selection | Environmental/web | Parallel architecture |
| FaoStatDeveloperPortalAdapter | faostatservices.fao.org | Portal API | QCL filters | Disabled by default; Fenix comments drift |
| Home orchestrator | Concurrency process pool | parallel scholarly | Outcomes map | Crop sequential instead |

---

## EVIDENCE

```
DISCOVERED (adapters)
  → NORMALIZED (ScientificSearchResult)
  → RELEVANCE GATE
  → DIRECTNESS ASSESSOR
  → RANK / filterRelevant
  → VALIDATION (Matcher + EVL refine)
  → COMPOSER eligibility / claims
  → HOME DISPOSITION (Home only)
  → CITATIONS in synthesis
  → PERSISTED metadata / library
```

**RISK:** Same item reclassified multiple times; disposition counts may diverge.

---

## RANKING

`ScientificResultRanker` consumes plan + results; applies domain adjusts (germination, geo, inventory — especially WIP).
**RISK:** Domain policy embedded in ranking infrastructure.

---

## COMPOSER

`AnswerComposer` owns final prose + citations + sufficiency framing; consults gate/assessor/EVL; Home metadata helpers.
**RISK:** Dirty WIP; ownership overlap with disposition service.

---

## PERSISTENCE

`ScientificKnowledgePersistenceService` → `LibraryItem` (`organization_id`, locale ar|en, bilingual fields).
**RISK:** Public caller + org selection; TR/FR collapse.

---

## API

| Boundary | Contract | Risk |
|----------|----------|------|
| `/api/v1/public/research-agent/*` | JSON stages | Unauth + cost + tenant |
| `/api/v1/public/field-crops/*` | Taxonomy + profile | Unauth research |
| Authenticated app APIs | `auth.principal` groups elsewhere | Out of Home/Crop public path |

---

## REACT

| SOURCE | TARGET | CONTRACT | RISK |
|--------|--------|----------|------|
| researchAgent.ts | Backend query | Subset types | Drops many fields |
| HomeScientificResearchSearch | DOM | answer + citations links | No multi-answer; no Google proxy |
| fieldCropCultivation.ts | farming-needs-profile | Crop params | Separate contract |
| i18n + Accept-Language | Backend locale | en/ar/tr/fr | Depends on user language store |

---

## FLUTTER

| SOURCE | TARGET | CONTRACT | RISK |
|--------|--------|----------|------|
| ResearchAgentScreen | researchQuery API | Richer field display | No URL launch; no Accept-Language |
| Plant production screens | Taxonomy | Not research answers | Confusion with Crop web path |
| admin-mobile | Admin APIs | Separate | Not in root CI |

---

## LEGACY

| SOURCE | TARGET | CONTRACT | RISK |
|--------|--------|----------|------|
| Agent (conditional Home) | KnowledgeEngine | RESEARCH_SEQUENCE remap | Dual truth |
| Agent (Crop always) | CropKnowledgeEngine profile | sections/load_state | Shape divergence from Home |

---

## DATABASE

| Entity | Role | Risk |
|--------|------|------|
| Organization | Tenant root | Public resolve by id |
| LibraryItem | Persisted research memory | org_id + locale collapse |
| Migrations/pgvector | Backend CI uses Postgres/pgvector | Ops complexity |

---

## SECURITY BOUNDARIES

```
[Public Internet]
  → nginx / API
    → throttle:60,1
    → /public/research-agent/*     ❌ no auth.principal
    → /public/field-crops/*        ❌ no auth.principal
    → resolveOrganization(client)  ⚠️ tenant selection
    → persist LibraryItem          ⚠️ write
  → /api/v1/... authenticated groups ✅ auth.principal (other features)
```

**RISK KEY:** Public research is a **cost + tenant write** boundary, not a read-only demo boundary.

---

## HOME vs CROP DIVERGENCE SUMMARY

| Concern | Home | Crop |
|---------|------|------|
| Entry | POST query | GET farming-needs-profile |
| QUS | Free-text NLP | understandCropProfileContext |
| Plan | buildGenericKnowledgePlan | buildCropProfileKnowledgePlan |
| Intent | generic_research | crop_profile |
| Scholarly concurrency | May overlap | Sequential |
| Multi-entity coverage | RC-C helper | Skipped |
| Disposition | Applied | No-op |
| Legacy | Conditional skip | Always profile shape |
| FE contract | answer/citations | sections/load_state |

---

## DOCUMENTATION AUTHORITY MAP (AS OF 2026-09-21)

| Doc | Role | Conflict |
|-----|------|----------|
| ADR-019 | Historical Crop pipeline protection | vs ADR-021 Crop-in-scope |
| ADR-020 | Historical 8-phase Home surgical repair | vs ADR-021 10-phase master |
| ADR-021 | Mission / master Fastest Safe Remediation plan | Recovered to worktree from `1eaced9`; not on HEAD tip |
| Phase 1 trio | Forensic baseline | Untracked working-tree docs |
| `WSA-ENTERPRISE-PROBLEM-REGISTRY.md` | `#1`–`#31` phase map scaffolding | Definitions NOT LOCALLY RECOVERABLE |
