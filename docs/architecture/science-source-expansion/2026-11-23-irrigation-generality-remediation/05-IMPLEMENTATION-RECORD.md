# 05 — Implementation Record

## Files modified

| File | Change |
|---|---|
| `backend/app/Services/Agriculture/Research/AgriculturalEntityCatalog.php` | GAP A helpers; disease intent keywords; `irrigationRetrievalEnglishConcept` |
| `backend/app/Services/Agriculture/Research/QueryUnderstandingService.php` | GAP B promotion blockers; GAP C sense precedence + CWR gate; Home property tighten |
| `backend/app/Services/Agriculture/Research/Search/ScientificSearchQueryBuilder.php` | GAP D fallback delegates to concept mapper |
| `backend/tests/Unit/Agriculture/Research/IrrigationGeneralitySemanticMatrixTest.php` | New EN/AR/TR/FR positive/negative matrix |

## Key behavioral changes

1. Systems phrases removed from positive irrigation-requirement signals.
2. Disease+irrigation incidental mentions no longer force CWR.
3. `researchIntent === 'irrigation'` alone no longer sets CWR.
4. Arabic/TR/FR quantity and scheduling map to non-deficit English concepts when appropriate.
