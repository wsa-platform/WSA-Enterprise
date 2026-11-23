# 02 — Current Architecture and Call Graph

## Pipeline (irrigation-relevant slice)

```
User Question
  → QueryUnderstandingService::understand
      → AgriculturalEntityCatalog topic/factor roles
      → detectResearchIntent
          → asksExplicitIrrigationOrWaterRequirement  (GAP A signal)
          → factorAskedAsAttribute('water')           (GAP B gate)
          → disease/systems promotion blockers        (GAP B)
      → resolveScientificSense
          → extension / economics
          → disease / varieties / organic / systems   (GAP C protections)
          → salinity_physiology
          → crop_water_requirement only with genuine water act (GAP C)
      → Home requested_property contract (tightened)
  → ResearchPlanner / CSQ
  → ScientificSearchQueryBuilder
      → englishRetrievalFallbackForRole(PROCESS)
          → irrigationRetrievalEnglishConcept(surface) (GAP D)
  → Retrieval / Evidence / Synthesis (unchanged contracts)
```

## Responsibility boundaries (preserved)

| Component | Responsibility |
|---|---|
| `ScientificQueryCompiler` | Query metadata / compilation |
| `ScientificSearchQueryBuilder` | Executable retrieval variants |
| `QueryUnderstandingService` | Intent, sense, roles |
| `AgriculturalEntityCatalog` | Reusable semantic helpers |

ADRs 022/023 and Membership ≠ Capability ≠ Eligibility ≠ Activation remain untouched.
