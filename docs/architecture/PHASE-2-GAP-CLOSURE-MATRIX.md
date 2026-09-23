# PHASE 2 — GAP CLOSURE MATRIX (P2-C01 … P2-C18)

**Date:** 2026-09-21  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Baseline HEAD:** `23c57b87c695366ad2a7a2ea0e4c981629a99cdd`

Classification values: `RESOLVED` | `INTENTIONALLY_RETAINED_WITH_DOCUMENTED_RATIONALE` | `BLOCKED_REQUIRES_HUMAN_DECISION`

---

| Finding | Status | Root cause | Implementation | Tests / Evidence |
|---------|--------|------------|----------------|------------------|
| **P2-C01** | RESOLVED | HEAD MODEL A vs WIP MODEL B | R1 MODEL B: `PublicTenantResolver` binds server public org; client org ignored | `PublicTenantBindingSecurityTest`, `Phase2ContractIntegrityTest` |
| **P2-C02** | RESOLVED | Client-selectable org → Library write | Same MODEL B + persistence mismatch reject | Security tests TEST-03/04 + persistence defense |
| **P2-C03** | RESOLVED | Home `status` vs Crop `load_state` | Dual-emit + shared `error.{code,http_status,message,details}` | `Phase2ContractIntegrityTest::test_public_tenant_missing…` |
| **P2-C04** | RESOLVED | Mobile omitted Accept-Language | `mobile/.../http_client.dart` sends UI `Accept-Language` | Code inspection + mobile client |
| **P2-C05** | RESOLVED | Overloaded `supported` | R6: deprecate directness `SUPPORTED`→`supporting`; separate claim_relation; docs | DirectnessAssessor comments + Canonical Contract |
| **P2-C06** | RESOLVED | Docs FENIX lag | ADR-001 historical note; `.env.example` FENIX superseded; ADR-004 already Portal | ADR-001 / ADR-004 / env example |
| **P2-C07** | RESOLVED | `contextInput` omitted from `toArray` | `KnowledgeQueryPlan::toArray` includes `context_input` | `Phase2ContractIntegrityTest::test_kqp_to_array…` |
| **P2-C08** | RESOLVED | locale ar\|en collapse | Persist ar\|en\|tr\|fr | Persistence service + Canonical Contract |
| **P2-C09** | RESOLVED | answer_language = platform locale | QUS `answer_language` from question | `Phase2ContractIntegrityTest` R2 matrix |
| **P2-C10** | RESOLVED | Home FE ignored confidence/limitations | `HomeScientificResearchSearch.tsx` renders both | FE file inspection |
| **P2-C11** | RESOLVED | Disposition ownership duplication | Documented: HomeDisposition = labels; EVL/validation = save eligibility | `HomeEvidenceLifecycleDisposition` header + R5 docs |
| **P2-C12** | INTENTIONALLY_RETAINED_WITH_DOCUMENTED_RATIONALE | Dual provider registries | Phase-3 consolidation candidate; not Phase-2 scope | Canonical Contract §12 |
| **P2-C13** | RESOLVED | FAOSTAT codes ≠ crop IDs | Documented identifier boundaries; no implicit joins | Canonical Contract §11 |
| **P2-C14** | INTENTIONALLY_RETAINED_WITH_DOCUMENTED_RATIONALE | Home-only multi-entity coverage | R4 intentional Home search fork | Canonical Contract §12 / R4 |
| **P2-C15** | INTENTIONALLY_RETAINED_WITH_DOCUMENTED_RATIONALE | int org vs string crop ids | Typed boundaries documented; intentional | Canonical Contract §11 |
| **P2-C16** | INTENTIONALLY_RETAINED_WITH_DOCUMENTED_RATIONALE | page/per_page vs research limit | Different API families; not a research semantic bug | Audit design — no unify required in Phase 2 |
| **P2-C17** | INTENTIONALLY_RETAINED_WITH_DOCUMENTED_RATIONALE | Units often implicit in prose | No approved unit DTO in R1–R7; FAOSTAT units preserved where present | Canonical Contract §12 |
| **P2-C18** | RESOLVED | admin-mobile hardcodes `ar` | Dynamic `getAcceptLanguage` on admin HttpClient / M22HttpClient | admin-mobile http clients |

**P0/P1 unresolved:** none.

**R7 (feedback)** added beyond original audit IDs: positive-only dataset + endpoint + tests.

---

## Second forensic pass notes

- **HISTORICAL (at Phase 2 closure):** No Phase-3 work started. **CURRENT:** Phase 3-A (`0ea1c7e`) and Phase 3-B (`ff91d0d`) subsequently completed. This matrix is not a live Phase 3 status document.
- Pre-existing scientific WIP preserved; Phase-2 changes are surgical on tenant/language/persistence/evidence/docs/clients/tests.
- FENIX not reintroduced; Developer Portal remains runtime.
