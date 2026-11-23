# 10 — Git and Release Evidence

## Staging policy

Mission-only paths committed:

- `backend/app/Services/Agriculture/Research/AgriculturalEntityCatalog.php`
- `backend/app/Services/Agriculture/Research/QueryUnderstandingService.php`
- `backend/app/Services/Agriculture/Research/Search/ScientificSearchQueryBuilder.php`
- `backend/tests/Unit/Agriculture/Research/IrrigationGeneralitySemanticMatrixTest.php`
- `docs/architecture/science-source-expansion/2026-11-23-irrigation-generality-remediation/`

Unrelated WIP explicitly excluded.

## Commit

| Field | Value |
|---|---|
| SHA | `8d0062cdba272d120c7de7c00fec0b93fc346bc1` |
| Short | `8d0062c` |
| Message | `fix(science): generalize multilingual irrigation intent, sense, and retrieval` |
| Branch | `phase-18-m18-ai-marketing-communications` |
| Parent baseline | `63d693fd7a0f4686f37f7e55b2d088b6b6152a7e` |

## Push

| Field | Value |
|---|---|
| Remote | `origin` (`https://github.com/wsa-platform/WSA-Enterprise.git`) |
| Result | `63d693f..8d0062c  HEAD -> phase-18-m18-ai-marketing-communications` |
| Force push | **No** |
| Local HEAD | `8d0062cdba272d120c7de7c00fec0b93fc346bc1` |
| Remote HEAD | `8d0062cdba272d120c7de7c00fec0b93fc346bc1` |
| Divergence | none after push |

## Docs sync commit (evidence SHA fill)

| Field | Value |
|---|---|
| SHA | `3166b3dcd3f17f9581c53b65016019e4cf39d6c6` |
| Message | `docs(science): record irrigation generality remediation release SHA` |
| Local/Remote HEAD after | `3166b3dcd3f17f9581c53b65016019e4cf39d6c6` |

## Secrets review

No secrets staged. No `.env` committed.
