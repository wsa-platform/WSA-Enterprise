# Scientific Search Pipeline — Actual Architecture Map (Rev 11.0.6)

**Branch:** `phase-18-m18-ai-marketing-communications`  
**Baseline HEAD:** `5a643f3`  
**EXIT mapping:** EXIT-08 (routing), EXIT-09 (CSQ/identity), EXIT-10 (evidence→answer)  
**Verdict scope:** Engineering discovery map — not Phase 1 EXIT closure.

## Actual path (code call chain)

```
User question / API input
  → AgriculturalResearchAgent::{searchResearch|validateResearch|synthesizeResearch}
  → ResearchPlanner::planKnowledgeQuery
      → QueryUnderstandingService (entity/process/constraints + CSQ freeze)
      → CanonicalScientificQuestion (semantic authority when present)
  → ScientificSearchService::search(KnowledgeQueryPlan)
      → MultiSourceScientificSearchOrchestrator
          → ScientificSearchQueryBuilder::buildVariantsFromPlan  [CSQ path specializes geography / plant family]
          → ScientificSearchVariantBudget::apply
          → ScientificSourceSelector::selectSources (+ selectionTrace)
          → ScientificSourceAdapterRegistry::get(source_key)
          → Adapter::{search|retrieve} (REST / LocalIndex / legacy Stage-3)
          → normalize / validate evidence units
  → AgriculturalScientificValidationService (Stage 4)
  → AnswerComposer + ScientificUserPresentation (Stage 5; Outcome B DIRECT gate)
  → Response contract / frontend
```

## Component inventory (primary)

| Layer | Symbol / path |
|---|---|
| Agent entry | `backend/app/Services/Agriculture/Research/AgriculturalResearchAgent.php` |
| Planner | `ResearchPlanner` |
| Understanding | `QueryUnderstandingService`, `CanonicalScientificQuestion` |
| Query compile | `Search/ScientificSearchQueryBuilder.php` |
| Orchestrator | `Search/MultiSourceScientificSearchOrchestrator.php` |
| Selection | `Search/ScientificSourceSelector.php` |
| Registry | `Search/ScientificSourceAdapterRegistry.php`, `Search/Registry/ScientificSourceCatalog.php` |
| Governed seats | `Onboarding/NextGateEligibleSeatCatalog.php`, `Onboarding/CurrentMembershipNearReadySeatCatalog.php`, `Onboarding/AcceptedOnboardingDecisionCatalog.php` |
| Adapters | `Search/Adapters/*`, `Search/LocalIndex/LocalIndexScientificSourceAdapter.php`, `Search/Family/Rest/RestJsonScientificSourceAdapter.php`, FAOSTAT portal adapter |
| Synthesis | `Synthesis/AnswerComposer.php`, `Synthesis/ScientificUserPresentation.php` |

## Runtime catalog keys (10)

**Governed ACTIVE (110.v1 seats):** `agris` (G1-01), `organic_eprints` (G1-02), `dr_duke` (G1-17), `gbif` (G5-04), `bhl` (G6-20)  
**Legacy Stage-3 (outside 110 membership membership claim):** `openalex`, `crossref`, `semantic_scholar`, `fao_stat`  
**Optional explicit-only:** `consensus` (not selected on internet-first defaults)

## Alternative / fallback paths

- Clarification short-circuit when `KnowledgeQueryPlan::needsClarification()` and `force_execute` false.
- Legacy query-builder branch when CSQ absent (must not replace CSQ when present).
- LocalIndex vs live REST per seat family profile.
- Capability denial / inactive skip recorded in `selectionTrace`.

## Identity-loss risk points (mitigated / monitored)

| Risk | Location | Rev 11.0.3/11.0.6 status |
|---|---|---|
| Egypt geography dropped on land_classification | CSQ → QueryBuilder | Fixed + regression locked |
| Cucurbitaceae Latin family drop | plant_family_members path | Fixed + regression locked |
| Knowledge-option echo as false role | QUS Level-1 strip | Fixed (WIP crop-binding preserved separately) |
| SUPPORTING promoted to DIRECT citations | AnswerComposer Outcome B | Contract tests PASS |
| Seat blockers misread from contract vs seat | Activation probe | Corrected to `$seat->blockers` |

## Persistence boundaries

- App operational DB ≠ Scientific LocalIndex SQLite ≠ CapVer ≠ Mission Artifact Archive.
- Reference: `docs/architecture/DATABASE-AND-ARCHIVE-STORAGE-INVENTORY.md`.
- No Archive Supersession in this package (§10.1 Inspection not opened).
