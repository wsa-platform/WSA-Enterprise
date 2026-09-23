# PHASE 2C — GENERIC RESEARCH CLOSURE REPORT

**Date:** 2026-09-24  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**HEAD:** `879f67dc9b4de3a696dc81344acc9aa65a9c4796` (unchanged)  
**Commit/push:** none  
**Gate:** PASS WITH DOCUMENTED BLOCKERS

Wheat passes because the generic architecture is correct.  
Wheat was not fixed as Wheat.

---

## 1. Executive Summary

Phase 2C repaired the **class of problem** that Home vs Crop exposed on agronomic-requirement questions.

The failure was not Wheat. It was this contract:

- Crop `knowledge_option === 'farming-needs'` injected agronomic topics and agronomic search variants.
- Home free-text questions with the same semantics (`question_type = requirements`) did not.
- Shared ranking then gated Home on plant-growth physiology needles (`growth`, `physiology`, `cultivation`, `yield`).
- Valid agronomic-requirement papers were discarded as `missing_topic_or_factor`.

The repair is a new shared layer, `ScientificQuestionSemantics`:

- question type / evidence type / factors / sense → knowledge-target topics
- entity is DATA joined into generic variant tails
- Home and Crop call the same methods
- no `if wheat`, no Home-only exception, no crop-id algorithm branch

Proven on Wheat, Maize, Rice, Barley, Tomato, Potato, Strawberry (soil), Lettuce (range), Tomato/Wheat diseases, and an uncatalogued `future-crop-x` / `Futurus cropus` identity.

---

## 2. Baseline / Git State

| Item | Value |
|------|--------|
| Branch | `phase-18-m18-ai-marketing-communications` |
| HEAD | `879f67dc9b4de3a696dc81344acc9aa65a9c4796` |
| Staged | none |
| Destructive git | none (`reset` / `restore` / `clean` / `stash` / `rebase` / force-push not used) |
| Commit | not created |
| Push | not performed |

---

## 3. Pre-existing WIP Identification

**Phase 2 (preserve):** AnswerComposer destack + measurement-gate exemption for `requirements`; Crop QUS farming-needs agronomic topics (now lifted to question-type); QueryBuilder agronomic variants (now question-type); frontend empty Sources / Uncertainty / title-as-link / additional_information; related tests.

**Phase 2B (preserve):** `ScientificSourceDiscoveryPipeline::discoverMissingSections(..., $skipExternalDiscoverers)`; Home generic path skip-external; `PostSynthesisBlockingWorkGateTest`.

**Pre-existing dirty WIP (not modified in 2C):**  
`FieldCropTaxonomyCatalog.php`, `AgriculturalEntityCatalog.php`, `ResearchPlanner.php`, ADR-021, Stage3/Compositional/Accuracy/Relevance/Upstream/SweetPotato tests, `.env.example`, `composer.lock`, untracked e2e-tmp / BASELINE / REMEDIATION docs, Admin-mobile e2e-tmp.

**2C-owned new/changed production files:**  
`ScientificQuestionSemantics.php` (new), `QueryUnderstandingService.php`, `ScientificSearchQueryBuilder.php`, `ScientificEvidenceRelevanceGate.php` (was clean at HEAD), `GenericScientificResearchArchitectureTest.php` (new), `FieldCropCanonicalAnswerView.tsx` (unused `_index` for `tsc -b`), this report.

---

## 4. Generic Root Cause

Requirement-specification was implemented as a **Crop product option**, not as a **question-type / evidence-type contract**.

| Layer | Defect |
|-------|--------|
| QUS Crop | `if ($knowledgeOption === 'farming-needs')` injected agronomic topics |
| QUS Home | same `question_type=requirements` did not receive those topics; `requested_property=requirements` turned the topic gate on with empty property terms |
| QueryBuilder | `isCropFarmingNeedsPlan()` required `knowledge_option === 'farming-needs'` |
| Relevance gate | `plant_growth` sense needles were physiology; `scientific_topics` were ignored once any topic factor existed |
| Ranker / validator / composer | shared — they consumed the diverged plan |

---

## 5. Why Wheat Exposed the Problem

The Wheat Crop farming-needs path already produced:

`Triticum aestivum agronomic requirements` → 70 raw → ~62 usable → 6 citations.

The related Home agronomic-requirements question produced:

70 raw → 10 after physiology topic gate → 0 accepted → `no_verified_claims`.

Wheat is a catalogued crop with a scientific name. It is a convenient diagnostic, not the defect.

---

## 6. Why the Problem Is Not Wheat-Specific

Any crop + any requirements-class question on Home would miss agronomic retrieval and fail the physiology topic gate. Maize, rice, barley, tomato, potato, and uncatalogued `Futurus cropus` now use the same methods with different entity DATA.

---

## 7. Home vs Crop Root-Cause Analysis

| Question | Home before 2C | Crop before 2C |
|----------|----------------|----------------|
| Why classify differently? | Free text; no `knowledge_option`; selector-gated QUS branch | Selector + `farming-needs` synthesizes `"{crop} farming-needs"` → `requirements` + agronomic topics |
| Why ranking differs? | `requiresTopic=true` (`requested_property=requirements`) against physiology needles | `requiresTopic=false` (no property, topics ignored unless plant_family) |
| Why evidence differs? | 10 physiology leftovers fail DIRECT / claim match | Agronomic corpus can become DIRECT |
| Where valid results discarded? | `ScientificEvidenceRelevanceGate::filterRelevant` / `missing_topic_or_factor` | Not discarded for entity-only wheat hits |
| Intentional? | Crop option was a product shortcut, not a scientific contract | Accidental Home/Crop split |
| Shared layer wrong? | Yes — question-type knowledge targets were option-gated | Same |

2C does **not** make Home copy Crop. Both consume `ScientificQuestionSemantics`. Crop `knowledge_option` remains only a **producer** of `question_type=requirements` when the question text is empty.

Home intent may still be `general_knowledge` when “cultivating” does not win intent scoring. Sense, topics, and variants are now `agronomic_requirements` regardless.

---

## 8. Query Understanding Findings

Generic mapping now applied on **both** Home and Crop after `question_type` + `required_evidence_type`:

- `requirements` / `requirement_specification` / qualifier `requirement` → agronomic, soil, or water targets from factors/haystack
- `symptoms` or disease inventory wording → plant-disease targets (before bare “what are” collapses to definition)
- cultivation-practices wording → cultivation-practice targets
- temperature + optimal/range dominating a “requirements” keyword hit → `range` (quantitative), not agronomic specification
- `preferredSenseForRequirement()` replaces `plant_growth` when the qualifier is requirement and no more specific factor sense applies

---

## 9. Query Planning Findings

`ScientificSearchQueryBuilder` no longer gates agronomic variants on `farming-needs`.

`hasSpecializedSearchTails()` + `searchVariantTails()` emit entity-agnostic tails. The builder joins the resolved scientific name / common name as DATA.

Example runtime `v0`:

- Home maize → `Zea mays agronomic requirements`
- Home rice → `Oryza sativa agronomic requirements`
- Home tomato diseases → `Solanum lycopersicum plant diseases`
- Crop future-crop-x → `Futurus cropus agronomic requirements`

---

## 10. Evidence Evaluation Findings

Shared validator / matcher / DIRECT citation rules were **not** weakened.

`requiresSupportedMeasurement()` still treats `question_type=requirements` as qualitative (Phase 2). 2C did not change thresholds, directness enums, claim_relation, or disposition.

The gate now consults knowledge-target `scientific_topics` even when factors are present, and merges `ScientificQuestionSemantics::senseQueryTerms()` for the new senses. `MIN_SCORE_CROP_TOPIC` remains 70.

A tomato **disease** plan correctly **rejects** an agronomic-requirements title (`missing_topic_or_factor`). That is intended.

---

## 11. Ranking Findings

70 → 10 on Home was **not** a ranker top-K of 10. Provider `limit=10` is the per-provider page size. Home’s 10 was `filterRelevant()` after the physiology topic gate.

2C does not raise a numeric cap. It supplies the correct topic needles so agronomic papers are not discarded as irrelevant.

Unit/runtime: Home maize plan + title `Agronomic requirements of Zea mays...` → `relevant=true`, `topic_matched=true`.

---

## 12. Citation Findings

Citation eligibility unchanged: primary `citations[]` remain DIRECT-only. References are not promoted. Empty Sources stay hidden (Phase 2 frontend). Title remains the clickable HTTP(S) link.

2C does not force citations.

---

## 13. Answer Composition Findings

Answer / additional_information / uncertainty / limitations / conflicts / citations remain destacked (Phase 2). 2C did not change composer concatenation rules.

Accuracy-test failures that expect entity names inside the main `answer` string remain classified as destack/WIP, not 2C regressions.

---

## 14. MCP Findings

Unchanged product contract (Phase 2B):

| Question | Finding |
|----------|---------|
| Why MCP exists | Legacy post-synthesis enrichment when Stage 5 is insufficient |
| Who invokes it | Home generic path after insufficient DIRECT / empty citations |
| Mandatory? | Yes for that fallback per `PostSynthesisBlockingWorkGateTest` |
| Blocking? | Yes today (`FREE_SEARCH_MCP_ENABLED=true`, ~30s timeout) |
| Timeout / empty | Wait completes; empty MCP ≠ verified evidence |
| 2C change | None — product decision required to make MCP non-blocking |

---

## 15. Performance Findings

2C is an architecture/correctness phase, not a latency optimization.

| Path | Architectural effect | Latency claim |
|------|----------------------|---------------|
| Home requirements | First variants are now agronomic/soil/water/disease tails, not physiology-only | Not claimed as faster; retrieval corpus should be more relevant |
| Crop sequential Stage 3 | Unchanged | Provider-bound ~6–10s Stage 3 remains |
| Home MCP | Unchanged | Remaining ~30s on insufficient fallback |
| Skip-external (2B) | Preserved | Avoids duplicate OA/CR section discovery |

Do not treat provider variance as 2C improvement.

**Previous documented numbers (2B, not re-claimed as 2C wins):**

| | Crop wheat | Home agronomy |
|--|------------|----------------|
| Before 2B skip-external | T_total 16326 / stage3 9668 / citations 6 | T_total 48397 / stage3 8731 / citations 0 |
| After 2B skip-external | T_total 10274 / stage3 6561 / citations 6 | T_total 42405 / stage3 4661 / citations 0 / library_keyword |

2C live HTTP provider remesure was not used as a green gate. In-process Home/Crop/future-crop semantics + gate were remesured (section 25).

---

## 16. Generic Architectural Changes Implemented

1. **`ScientificQuestionSemantics`** — question-type → knowledge targets, variant tails, property terms, sense terms, range-vs-requirements, disease-inventory preference. Entity is never an input to the algorithm.
2. **QUS** — `applyQuestionTypeKnowledgeTargets()` on Home and Crop; removed `knowledge_option === 'farming-needs'` topic injection; `preferredSenseForRequirement`; `preferRangeOverRequirements`; disease inventory before bare definition.
3. **QueryBuilder** — `isRequirementSpecificationPlan` / `buildRequirementSpecificationVariants` from semantics, not option.
4. **Relevance gate** — always score `scientific_topics`; overlay semantics sense terms.
5. **Tests** — data-driven multi-crop + multi-question + anti-special-case + gate + no-wheat-branch scan.
6. **Frontend** — unused `index` → `_index` so `tsc -b` / Vite production build pass (no rendering change).

---

## 17. Special Cases Removed

- Crop-only `if ($knowledgeOption === 'farming-needs' && $topicFactors === [])` agronomic topic injection
- QueryBuilder `isCropFarmingNeedsPlan()` / `buildCropFarmingNeedsVariants()` option gate

---

## 18. Special Cases Explicitly NOT Introduced

- no `if wheat` / `Triticum` / crop_id algorithm
- no Home-only ranking exception
- no question-string hardcoding
- no per-crop thresholds, providers, or citation rules
- no MCP timeout change
- no evidence-threshold change
- no auth / Admin / Marketplace / Jobs / Library change

---

## 19. Multi-Crop Test Matrix

| Crop | Home query / Crop path | question_type | Primary variant / topics | Result |
|------|------------------------|---------------|--------------------------|--------|
| Wheat | agronomic requirements | requirements | agronomic requirements | PASS |
| Maize/Corn | agronomic requirements | requirements | `Zea mays agronomic requirements` | PASS |
| Rice | agronomic requirements | requirements | `Oryza sativa agronomic requirements` | PASS |
| Barley | agronomic requirements | requirements | agronomic requirements | PASS |
| Tomato | agronomic requirements | requirements | agronomic requirements | PASS |
| Potato | water requirements (Home) | requirements | water requirements | PASS |
| Future Crop X | Crop selector DATA only | requirements | `Futurus cropus agronomic requirements` | PASS |

Strawberry is not catalogued. Home “soil requirements of strawberries?” still classified `requirements` + soil topics (entity DATA optional).

---

## 20. Multi-Question Test Matrix

| Question class | Example | Type | Knowledge target |
|----------------|---------|------|------------------|
| Agronomic requirements | maize / rice / wheat / barley / tomato | requirements | agronomic requirements |
| Soil requirements | tomatoes, strawberries | requirements | soil requirements |
| Water requirements | potatoes | requirements | water requirements |
| Cultivation practices | rice | definition + practice tails | cultivation practices |
| Diseases | wheat, tomato | symptoms | plant diseases |
| Quantitative/range | lettuce optimal temperature | range | temperature |

---

## 21. Regression Results

| Suite | Passed | Failed | Skipped | Classification |
|-------|--------|--------|---------|----------------|
| GenericScientificResearchArchitectureTest | 18 | 0 | 0 | 2C |
| AnswerComposerEvidenceStateContractTest | 15 | 0 | 0 | Phase 2 preserved |
| Phase6U61CropSelectorAndContextContractTest | 12 | 0 | 0 | Phase 2 agronomic primary preserved |
| PostSynthesisBlockingWorkGateTest | 9 | 0 | 0 | Phase 2B preserved |
| SpaCsrfAuthenticationContractTest | 8 | 0 | 0 | Phase 1 |
| AuthExtensionTest | 9 | 0 | 0 | Phase 1 |
| OAuthProviderIdentityTest | 8 | 0 | 0 | Phase 1 |
| Phase5UnitB1AnswerLanguageContractTest | 7 | 0 | 0 | R2 approved |
| Phase2ContractIntegrityTest R2 | 6 | 0 | 0 | R2 approved |
| FieldCropCultivationTest | 8 | 0 | 0 | Now green (was 3 fail in 2B; not weakened) |
| ScientificResearchLanguageContractTest | 0 | 7 | 0 | **B obsolete** — expects platform locale vs approved R2 |
| ScientificResearchSearchFlowTest | 2 | 5 | 0 | **B/C** — R2 language + land/directness/species WIP |
| ScientificResearchAnswerAccuracyTest | 33 | 7 | 2 | **C** destack/WIP ranking-presentation (was 8 fail) |
| Frontend Home/Crop/API vitest | 84 | 0 | 0 | Phase 2 UI preserved |

No genuine 2C regression was left unclassified.

---

## 22. Full Backend Gate

- Targeted scientific + Phase 2/2B/2C + Phase 1 auth: executed, green where they are the approved contract.
- Full PHPUnit suite: **not** used as a green gate (known mixed WIP + resource). Blocker documented.
- Static analysis: PHP `php -l` clean on 2C PHP files.

---

## 23. Frontend Gate

`vitest run` Home + Crop + API: **9 files, 84 tests passed**.

---

## 24. Production Build Result

- `npx tsc -b`: PASS (after unused `_index` in `FieldCropCanonicalAnswerView`)
- `npx vite build`: PASS — 217 modules, `dist/assets/index-ByLNhHpG.js` 962.89 kB

---

## 25. Runtime Verification

In-process ledger (Docker `wsa-enterprise-backend-1`):

```
HOME_MAIZE  intent=general_knowledge qtype=requirements sense=agronomic_requirements
            crop=corn sci=Zea mays
            v0=Zea mays agronomic requirements
            gate_relevant=true gate_topic=true

HOME_RICE   same contract; v0=Oryza sativa agronomic requirements; gate_relevant=true

HOME_TOMATO_DIS qtype=symptoms topics=plant diseases
            v0=Solanum lycopersicum plant diseases
            agronomic title correctly rejected (missing_topic_or_factor)

CROP_RICE   qtype=requirements sense=agronomic_requirements
            v0=Oryza sativa agronomic requirements; gate_relevant=true

CROP_FUTURE crop=future-crop-x sci=Futurus cropus
            v0=Futurus cropus agronomic requirements; gate_relevant=true
```

HTTP 200 live provider remesure for Home/Crop was not repeated as a 2C latency claim. Schema/language/destack/citation rules remain those of Phase 2.

---

## 26. Scientific Integrity Verification

| Contract | Changed? |
|----------|----------|
| Evidence threshold `MIN_SCORE_CROP_TOPIC=70` | No |
| Verification / DIRECT citation eligibility | No |
| Directness / claim_relation / disposition enums | No |
| Source quality standards | No |
| `requiresSupportedMeasurement` for quantity/range | No |
| Requirements qualitative exemption | Preserved (Phase 2) |

**Gate topic-consultation change (proven generic bug, not a weaker threshold):**

- OLD: if any `scientific_factors` exist, `scientific_topics` and sense terms were skipped
- NEW: knowledge-target topics are scored even when factors exist; factor signals still apply
- WHY: Home could extract a factor and then ignore agronomic/soil/disease topics
- TESTS: `GenericScientificResearchArchitectureTest`
- IMPACT: more correct topic matching; disease plans still reject agronomic titles

---

## 27. Remaining Blockers

1. Home Free Search MCP still blocks ~30s on insufficient Stage 5 — product decision (do not silently disable).
2. Crop scholarly Stage 3 remains sequential by existing contract — not changed in 2C.
3. Obsolete language tests still fail (must not revert R2).
4. Untracked SearchFlow / dirty Accuracy WIP still fail (directness/species/destack presentation).
5. Full PHPUnit suite not green-gated.
6. Home `research_intent` may remain `general_knowledge` when “cultivating” does not win intent scoring; sense/topics/variants are still correct. Catalog intent-signal expansion is WIP and was not touched.

---

## 28. Product/Architecture Decisions Required

1. Should Free Search MCP be non-blocking, fallback-only, or shorter-timeout on empty results? Current tests require it on insufficient Home synthesis.
2. Should obsolete LanguageContract / SearchFlow platform-locale assertions be updated to R2? 2C did not rewrite those tests.
3. Should Crop `requiresTopic` stay equally strict now that property terms are shared? Current 2C choice: same honest topic contract for requirement-specification on Home and Crop.

---

## 29. Git/WIP Safety Verification

- HEAD still `879f67dc9b4de3a696dc81344acc9aa65a9c4796`
- Nothing staged
- No commit, no push
- Unrelated WIP files listed in section 3 were not edited for 2C
- Untouched systems: wsa_token/auth, Sanctum, Admin Program, Marketplace, Jobs, Library, Phase 10 release-gate

---

## 30. Final Phase 2C Gate

**PASS WITH DOCUMENTED BLOCKERS**

Acceptance checklist:

- [x] Generic root cause identified
- [x] Generic solution implemented
- [x] No Wheat-specific production logic
- [x] No crop-specific algorithmic branch
- [x] No question-specific production branch
- [x] Query planning semantic/data-driven
- [x] Question-type semantics generic
- [x] Evidence standards intact
- [x] Evidence thresholds not weakened
- [x] Ranking generic
- [x] Home/Crop divergence addressed generically
- [x] Answer composition generic (preserved)
- [x] Citation rendering generic (preserved)
- [x] Empty Sources not rendered (preserved)
- [x] Additional Information structurally separate (preserved)
- [x] Multiple crops, same engine
- [x] Multiple question types, same engine
- [x] Anti-special-case regression exists
- [x] Regression suite executed; failures classified
- [x] No unresolved genuine 2C regression
- [x] Relevant backend gate executed
- [x] Frontend tests executed
- [x] Production build executed
- [x] Runtime multi-crop ledger executed
- [x] Scientific integrity verified
- [x] Performance findings documented
- [x] MCP documented, not silently changed
- [x] Remaining blockers documented
- [x] No unrelated WIP modified
- [x] No destructive git
- [x] No commit
- [x] No push

---

## Change inventory (every 2C file)

| File | Why changed | Generic contract | Root cause | Tests |
|------|-------------|------------------|------------|-------|
| `ScientificQuestionSemantics.php` | New shared question-type contract | Entity is DATA; algorithm is question semantics | Option-gated agronomy | GenericScientificResearchArchitectureTest |
| `QueryUnderstandingService.php` | Apply contract on Home and Crop; remove farming-needs if | Same helper both paths | Crop-only topic injection | same + Phase6U61 |
| `ScientificSearchQueryBuilder.php` | Variants from question type, not option | Tails × entity DATA | `isCropFarmingNeedsPlan` | same + Phase6U61 |
| `ScientificEvidenceRelevanceGate.php` | Topics always scored; new sense overlay | Shared gate | Physiology needles / topics dropped when factors exist | gate test in matrix |
| `GenericScientificResearchArchitectureTest.php` | Data-driven matrix + anti-special-case | Parameterized crops/questions | Proof of generality | 18 passed / 109 assertions |
| `FieldCropCanonicalAnswerView.tsx` | `_index` unused param | No behavior change | `tsc -b` unused local | frontend vitest + tsc |
| `PHASE-2C-GENERIC-RESEARCH-CLOSURE-REPORT.md` | Required closure report | n/a | n/a | this document |

### Investigated, not changed

| File | Why not changed |
|------|-----------------|
| `AnswerComposer.php` | Destack + measurement rule already correct |
| `AgriculturalScientificKnowledgeEngine.php` | 2B skip-external still correct |
| `ScientificSourceDiscoveryPipeline.php` | 2B contract |
| `ResearchPlanner.php` | Dirty WIP; plan shape already shared |
| `AgriculturalEntityCatalog.php` | Dirty WIP; 2C avoided catalog edits |
| `FieldCropTaxonomyCatalog.php` | Crop DATA already sufficient |
| `ScientificResultRanker.php` | No top-10 Home cap; filter uses gate |
| Auth / Admin / Marketplace / Jobs / Library | No proven dependency |
| MCP / PostSynthesis production | Product decision required |

---

## Why this works for future crops without modifying the algorithm

1. **Input contract:** query text and/or `selected_crop_id` + `selected_crop_name` + `scientific_name` as DATA.
2. **Semantic classification:** `question_type`, `required_evidence_type`, factors, sense — no crop switch.
3. **Query planning:** generic tails + joined entity string.
4. **Evidence evaluation:** shared DIRECT / measurement / quality rules.
5. **Ranking:** shared gate using knowledge-target topics.
6. **Synthesis / answer / frontend:** already generic (Phase 2).

A new crop needs catalog/taxonomy **data** if Home must resolve its identity from vernacular text. The Crop selector path already works with an arbitrary id + scientific name (`future-crop-x` / `Futurus cropus`) without a new branch.

STOP. Waiting for human review and explicit GO before any commit or push.
