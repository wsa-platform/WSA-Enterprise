# Scientific Search Pipeline — Actual Architecture Map (Rev 11.0.6)

**Branch:** `phase-18-m18-ai-marketing-communications`  
**HEAD at amend:** `d198919`  
**EXIT mapping:** EXIT-08 (routing), EXIT-09 (CSQ/identity), EXIT-10 (evidence→answer)  
**Verdict scope:** Engineering discovery map — not Phase 1 EXIT closure.  
**Discovery sources:** code call-chain audit + explore agents [pipeline map](4a907321-440c-4265-9d65-d58025a3bd9b) / [activation gaps](fec37f2a-2cdb-42ee-aed6-7283f3735a5c); C1→C3 mirrors at `b42d314`.

## Order correction (authoritative)

`ScientificSearchQueryBuilder` runs **inside Stage 3** (orchestrator), **after** `ResearchPlanner` / CSQ freeze — not between CSQ and Planner.

## Actual path (code call chain)

```
Entry (HTTP / crop profile)
  → AgriculturalResearchAgent::{plan|search|validate|synthesize|conduct}Research
  → ResearchPlanner::planKnowledgeQuery
      → QueryUnderstandingService::understand
      → freezeCanonicalScientificQuestion (CSQ on AgriculturalKnowledgeQuery)
      → KnowledgeQueryPlan
  → [Stage 3] AgriculturalScientificSearchService::search
      → RetrievalSemanticContract::apply
      → MultiSourceScientificSearchOrchestrator::execute
          → ScientificSearchQueryBuilder::buildVariantsFromPlan
              (CSQ → RetrievalSpecification when CSQ present; else legacy variants)
          → ScientificSearchVariantBudget::apply
          → ScientificSourceSelector::selectSources (+ selectionTrace)
          → ScientificSourceAdapterRegistry::resolveMany
          → Adapter::search / searchRequest (REST / LocalIndex / Stage-3)
          → normalize → dedupe → rank → filter
  → [Stage 4] AgriculturalScientificValidationService::validate
      → ScientificEvidenceItem[]
  → [Stage 5] AnswerComposer::compose (+ ScientificUserPresentation; Outcome B DIRECT gate)
  → Response contract / frontend
```

## Entry surfaces

| Surface | Path |
|---|---|
| Full / staged research HTTP | `AgriculturalResearchAgentController` → `POST /research-agent/{query,plan,search,validate,synthesize}` |
| Crop farming-needs | `PublicFieldCropCultivationController` / `FieldCropCultivationProfileService` |
| Alternate ADR-002 | `UniversalAnswerOrchestrator` via `answerUniversal` (flagged; no dedicated HTTP route) |
| LocalIndex offline | Harvest/ingest/project console commands — do **not** start QUS→answer |

## Component inventory (primary)

| Layer | Symbol / path |
|---|---|
| Agent entry | `AgriculturalResearchAgent.php` |
| Planner / plan | `ResearchPlanner`, `KnowledgeQueryPlan` |
| Understanding | `QueryUnderstandingService`, `CanonicalScientificQuestion` |
| Stage 3 service | `Search/AgriculturalScientificSearchService.php` |
| Semantics gate | `RetrievalSemanticContract` |
| Query compile | `Search/ScientificSearchQueryBuilder.php` (+ `RetrievalSpecification`) |
| Orchestrator | `Search/MultiSourceScientificSearchOrchestrator.php` |
| Selection | `Search/ScientificSourceSelector.php` |
| Registry | `ScientificSourceAdapterRegistry`, `Registry/ScientificSourceCatalog` |
| Governed seats | `NextGateEligibleSeatCatalog`, `CurrentMembershipNearReadySeatCatalog`, `AcceptedOnboardingDecisionCatalog` |
| Adapters | Stage-3 `Search/Adapters/*`; `LocalIndexScientificSourceAdapter`; `RestJsonScientificSourceAdapter`; FAOSTAT portal |
| Validation | `AgriculturalScientificValidationService`, `ScientificEvidenceItem` |
| Synthesis | `AnswerComposer`, `ScientificUserPresentation` |

## Runtime catalog keys (10)

**Governed ACTIVE (110.v1):** `agris` (G1-01), `organic_eprints` (G1-02), `dr_duke` (G1-17), `gbif` (G5-04), `bhl` (G6-20)  
**Legacy Stage-3 (outside 110.v1 membership):** `openalex`, `crossref`, `semantic_scholar`, `fao_stat`  
**Optional explicit-only:** `consensus` (never auto-selected)

**Activation verdict ([activation gaps](fec37f2a-2cdb-42ee-aed6-7283f3735a5c)):** no READY auto source is code-disconnected from catalog→registry→selector→orchestrator. Remaining gaps are credentials/config/data/§8.1 evidence — or absent adapters for 105/110 seats.

## Alternative / fallback paths

- Clarification short-circuit when `needsClarification()` and `force_execute` false.
- Legacy query-builder / `RetrievalSpecification::fromLegacyQuery` when CSQ absent.
- UniversalAnswerOrchestrator: same Stage 3–5 + web fusion when orchestrator flag on.
- LocalIndex offline harvest/ingest feeds adapters; separate from live QUS entry.
- `consensus` only via explicit `sourceKeys`; FAOSTAT via enable flag + credentials.

## Identity-loss risk points (mitigated / monitored)

| Risk | Location | Status |
|---|---|---|
| Egypt geography on land_classification | CSQ → QueryBuilder | Fixed + regression |
| Cucurbitaceae Latin family | plant_family_members | Fixed + regression |
| Knowledge-option echo | QUS Level-1 strip | Fixed (WIP crop-binding preserved) |
| SUPPORTING → DIRECT citations | AnswerComposer Outcome B | Contract PASS |
| LocalIndex scientificName prefer empties fixture hits | Orchestrator `variantsForAdapter` | Documented; AGRIS bridge keeps scientificName null when fixture title-bound |
| Seat blockers misread from contract | Activation probe | Corrected to `$seat->blockers` |
| ACTIVE without answer identity | C1→C3 bridges | PARTIAL for 5 ACTIVE seats (`b42d314`); not §8.1 VERIFIED |

## Persistence boundaries

- App operational DB ≠ Scientific LocalIndex SQLite ≠ CapVer ≠ Mission Artifact Archive.
- Reference: `docs/architecture/DATABASE-AND-ARCHIVE-STORAGE-INVENTORY.md`.
- No Archive Supersession in this package (§10.1 Inspection not opened).
