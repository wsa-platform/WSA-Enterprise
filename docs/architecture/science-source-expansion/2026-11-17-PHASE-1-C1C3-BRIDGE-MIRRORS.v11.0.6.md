# C1→C3 Evidence Bridge Mirrors — Rev 11.0.6

**Baseline:** `416e389`  
**Claim guard:** لا `SCIENTIFIC_INTEGRATION_VERIFIED` — الشروط الستة غير مكتملة. عضوية/مصفوفة 110 محفوظتان.

## المكوّن المشترك (بدون مسار إنتاج بديل)

| عنصر | المسار |
|---|---|
| Helper (اختبارات فقط) | `backend/tests/Support/ScientificEvidenceAnswerCitationBridge.php` |
| عقد الجسر المشترك | `GovernedSourceC1C3EvidenceBridgeContractTest` |
| مرايا المصادر | `GovernedActiveSourceC1C3EvidenceBridgeMirrorsTest` |
| انحدار GBIF | `GbifOrchestratorEndToEndTest` (أعيد استخدام الـ helper) |

الجسر = `ScientificSearchResult` (من Orchestrator) → `ScientificEvidenceItem` محافظ على الهوية → `AnswerComposer::buildCitations`.

## لكل مصدر

| adr_id | المصدر | كان موجودًا | ما أُصلح / أُضيف | اختبارات | وصول الإجابة | الحالة النهائية |
|---|---|---|---|---|---|---|
| G1-01 | agris | Orchestrator fixture + activation | جسر C1→C3؛ DOI كـ source_identifier | mirrors + contract + regression suite | citation `sourceKey=agris` + DOI id | ACTIVE · C1–C3 **PARTIAL** · §8.1 **غير VERIFIED** |
| G1-02 | organic_eprints | OAI→LocalIndex→Orchestrator | جسر C1→C3 (`oai:orgprints.org:573`) | mirrors | citation `sourceKey=organic_eprints` | ACTIVE · PARTIAL · غير VERIFIED |
| G1-17 | dr_duke | LocalIndex + scientificName variant prefer | جسر C1→C3 (`drduke-fixture-1`) | mirrors | citation `sourceKey=dr_duke` | ACTIVE · PARTIAL · غير VERIFIED |
| G6-20 | bhl | REST fixture orchestrator | جسر C1→C3 (`bhl-fixture-part-1`) | mirrors | citation `sourceKey=bhl` | ACTIVE · PARTIAL · غير VERIFIED |
| G5-04 | gbif | جسر سابق | Refactor إلى helper + regression في mirrors | Gbif E2E + mirrors regression | citation `sourceKey=gbif` / `2704179` | ACTIVE · PARTIAL · غير VERIFIED |

## نتائج الاختبارات

`11 passed / 163 assertions` على:
- `GovernedSourceC1C3EvidenceBridgeContractTest`
- `GovernedActiveSourceC1C3EvidenceBridgeMirrorsTest`
- `GbifOrchestratorEndToEndTest`
