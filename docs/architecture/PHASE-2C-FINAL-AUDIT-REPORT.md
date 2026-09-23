# PHASE 2C — FINAL CLOSURE REPORT

**Date:** 2026-09-24  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**HEAD:** `879f67dc9b4de3a696dc81344acc9aa65a9c4796` (unchanged)  
**Staged:** none  
**Commit / push / stage:** none  
**Destructive git:** none  
**Production code changed during this closure:** NONE  
**Final gate:** **PASS WITH DOCUMENTED BLOCKERS**

Wheat is a diagnostic row. The architecture is generic if and only if the same methods accept crop identity as DATA.

---

## 1. Executive Summary

Phase 2C final closure re-verified the generic scientific research architecture against source, live Stage 2–5 Home retrieval, a Home/Crop semantic ledger, a generic entity-resolution contract, targeted regression, frontend full gate, and a full PHPUnit attempt.

The central claim remains true:

> Wheat passes the semantic/gate layer because the generic architecture is correct, not because Wheat was special-cased.

**What this closure newly proved (was only PARTIALLY VERIFIED before):**

1. Home bread-wheat live Stage 3 is **70 raw → 70 after dedup → 8 after rank/gate**. The old ranking wipe (`70 → 10 → 0`) is **not** inferred from code alone. The corrected agronomic topics are the topics actually used by search and gate.
2. Future-crop-x / Futurus cropus was run through **live provider retrieval**. Crossref returned 50 raw hits; the generic gate kept **0**. Full validation/synthesis is **NOT TESTABLE** for an artificial taxon. That boundary is now measured, not assumed.
3. Uncatalogued Home names (strawberry, lettuce, Futurus) follow a **generic** unresolved-entity path. No strawberry/lettuce production branch exists or was added.

**What remains honestly insufficient:**

- This Home live pile produced **SUPPORTING-only** usable evidence (2 of 8). Composer correctly returned `insufficient_evidence` / `citations=0` under unchanged DIRECT-only citation rules. That is **not** a 2C threshold cut.
- Full PHPUnit is **NOT GREEN**: it died at **128 MB memory** after ~9.4 minutes, after already showing large pre-existing/WIP/legacy failure clusters.
- MCP remains enabled and blocking on insufficient Home Stage 5 — product decision, unchanged.
- Crop Stage 3 remains sequential for `crop_profile` — intentional contract, unchanged.

No genuine Phase 2C scientific regression requiring a production revert was found. No new special case was introduced. No unrelated WIP was edited. Nothing was staged, committed, or pushed.

---

## 2. Baseline Git State

Recorded at the start of this closure and re-confirmed at the end.

| Item | Value |
|------|--------|
| Branch | `phase-18-m18-ai-marketing-communications` (tracks `origin/phase-18-m18-ai-marketing-communications`) |
| HEAD | `879f67dc9b4de3a696dc81344acc9aa65a9c4796` |
| Staged | **none** |
| Destructive operations | **none** (`reset` / `restore` / `clean` / `stash` / `rebase` / force-push / destructive checkout: not used) |
| Commit | **none** |
| Push | **none** |

**Pre-existing WIP (dirty before this task; not edited):**

- Catalogs / planner / ADR-021 / `.env.example` / `composer.lock`
- Dirty Stage3 / Compositional / Accuracy / Relevance / Upstream / SweetPotato tests
- Untracked SearchFlow / HomeMultilingual / GenericAgriculturalEntity / SemanticTarget / ProviderActivation tests
- `admin-mobile/e2e-tmp/`, `backend/e2e-tmp/` (this closure only added read-only probe scripts under `backend/e2e-tmp/`)

**Phase 2 / 2B / 2C files** — see §25.

---

## 3. Verification of Previous Claims

Previous reports: `PHASE-2C-GENERIC-RESEARCH-CLOSURE-REPORT.md` and the prior revision of this audit. Claims were re-checked against source and runtime. Do not treat the older audit as sufficient by itself.

| Prior claim | This closure |
|-------------|--------------|
| `ScientificQuestionSemantics` is generic (no crop names, no Home/Crop switch) | **VERIFIED** — file grep: no wheat / Triticum / strawberry / lettuce / farming-needs |
| Wheat is not special-cased in semantic / query / relevance algorithm | **VERIFIED** |
| Crop identity is DATA | **VERIFIED** |
| Home and Crop no longer differ in knowledge-target classification for the same semantic class | **VERIFIED** (live Home vs Crop wheat ledger) |
| `farming-needs` is not required to inject agronomic topics | **VERIFIED** |
| Evidence thresholds not lowered | **VERIFIED** — `MIN_SCORE_CROP_TOPIC = 70.0` |
| DIRECT citation rules not loosened | **VERIFIED** — `isPrimaryCitationEligible` is DIRECT-only |
| Multi-crop generic tests exist | **VERIFIED** — `GenericScientificResearchArchitectureTest` 18/18 |
| Frontend tests / tsc / production build | **VERIFIED** (re-run; full Vitest is 287, not the previously scoped 84) |
| `FieldCropCultivationTest` 8/8 | **VERIFIED** (re-run in targeted batch) |
| Full PHPUnit attempted but unfinished | **VERIFIED** — this closure: **OOM 128 MB**, not finished |
| LanguageContract 7 obsolete vs R2 | **VERIFIED** (each of 7 re-proven) |
| SearchFlow / Accuracy = WIP / destack / obsolete R2 | **VERIFIED** (re-run and reclassified) |
| MCP documented blocker | **VERIFIED** — enabled `true`, timeout `30000` |
| Crop Stage 3 sequential intentional | **VERIFIED** in code; Home overlap remesured; Crop sequential not remesured |
| Uncatalogued Home names need a generic entity-resolution contract | **VERIFIED** — contract A/B/C documented; no crop-specific fallback added |
| Home Wheat not yet re-proven live after final semantic repair | **NOW VERIFIED** through Stage 2–5 in-process (no MCP / persist / HTTP / frontend paint) |
| Future-crop “full pipeline” | Prior report **over-claimed**. This closure: live retrieval **PROVEN**; Stage 4–5 **NOT TESTABLE** |
| Home `70→10→0` fixed because code changed | Prior report **over-claimed** live counts. This closure: **70→70→8** live rank/gate **PROVEN**. Stage 5 citations remain 0 for this pile because DIRECT was absent |

Corrected over-claims are not hidden: live Stage 5 did **not** produce citations. That does not undo the semantic/gate proof.

---

## 4. Home End-to-End Verification

**Question:** `What are the agronomic requirements for cultivating bread wheat?`

**Method:** in-process `ResearchPlanner` → `AgriculturalScientificSearchService::search` → `AgriculturalScientificValidationService::validate` → `AnswerComposer::compose`.  
**Not executed:** MCP, library persist, HTTP research-agent, browser paint.

### Pipeline trace

| Stage | Result |
|-------|--------|
| User question | English agronomic-requirements question; entity surface `bread wheat` |
| Question understanding | `research_intent=general_knowledge`, `crop_id=wheat`, `scientificName=Triticum aestivum`, `named_entity_state=resolved` |
| `ScientificQuestionSemantics` | `question_type=requirements`, `scientific_sense=agronomic_requirements`, `required_evidence_type=requirement_specification` |
| Knowledge targets | `agronomic requirements`, `cultivation practices`, `crop management` |
| Query planning | `plan_intent=generic_research` |
| Generated queries | `Triticum aestivum agronomic requirements` / `cultivation practices` / `crop management`; plus `bread wheat agronomic requirements` / `cultivation practices` |
| Provider retrieval | OpenAlex 1958 ms, Crossref 2102 ms, Semantic Scholar 4225 ms; FAOSTAT empty. Stage 3 elapsed **7488 ms**. Search wall **7611 ms**. Status `search_completed` |
| Normalization / raw | **70** |
| Deduplication | **70** |
| Ranking + relevance gate | **8** survivors, scores 163.5–188.5, all bread-wheat / cereal-agronomy titles (not a physiology wipe) |
| Evidence evaluation | sources received **8**; validated **2**; rejected **0**; quality high 1 / medium 1 / low 6; `evidence_sufficient=false` |
| Directness | both usable items `supporting` |
| Claim relation | both `partially_supported` |
| Disposition | `insufficient_direct_supporting_retained` |
| Citation eligibility | 0 primary citations (DIRECT-only rule) |
| Synthesis / composer | `insufficient_evidence`, `failure_reason=insufficient_direct_evidence` |
| Answer | `Insufficient direct scientific evidence was found for a definitive answer.` |
| `additional_information` | 584 chars of supporting/contextual text (cereal overview). Not promoted to main answer or citations |
| Sources | empty `citations[]` — frontend contract hides empty Sources |
| Answer language | `en` (question language `en`) |

### Proof that the repaired semantic/gate path is actually used

- Search queries are the agronomic tails, not `cultivation growth` physiology tails.
- Rank survivors are wheat/agronomy papers with scores **> 70**.
- `70 → 8` is a live measurement. The previous `70 → 0` ranking class of failure is **not** present on this run.
- Stage 5 citation emptiness is **DIRECT sufficiency**, not a return of the physiology topic-gate wipe.

### Stages not executed

MCP, persist, HTTP API envelope, live browser rendering of this payload.

---

## 5. Home vs Crop Comparison

Same semantic question: `What are the agronomic requirements for cultivating wheat?`

| Field | HOME | CROP | Class |
|-------|------|------|--------|
| Entry | free-text query | selector `wheat` + `Triticum aestivum` + `knowledge_option=scientific-research` | **legitimate product** |
| `research_intent` | `general_knowledge` | `scientific_literature` | **legitimate scoring / option default** |
| `plan_intent` | `generic_research` | `crop_profile` | **legitimate product path** |
| `question_type` | `requirements` | `requirements` | **same** |
| `scientific_sense` | `agronomic_requirements` | `agronomic_requirements` | **same** |
| `required_evidence_type` | `requirement_specification` | `requirement_specification` | **same** |
| Knowledge targets | agronomic requirements / cultivation practices / crop management | identical | **same** |
| `v0` | `Triticum aestivum agronomic requirements` | identical | **same** |
| Variants (first 5) | identical agronomic tails × entity DATA | identical | **same** |
| `named_entity_state` | `resolved` | `null` (selector-supplied crop, not Home named-entity path) | **legitimate product** |
| Evidence / ranking / citation rules | shared gate, validator, DIRECT composer | shared | **same algorithm** |
| Execution | Home scholarly overlap allowed | Crop `shouldOverlapIndependentScholarlyProviders` = false | **legitimate contract** |
| CropKnowledgeEngine | skipped when Home DIRECT gate PASSED | always runs | **existing contract** |

**Remaining semantic divergence:** none on question type, sense, evidence type, knowledge targets, or generated agronomic query. Intent labels and execution strategy still differ by product path. That is allowed.

---

## 6. ScientificQuestionSemantics Audit

| Question | Answer |
|----------|--------|
| Depends on crop names? | **NO** |
| Depends on a stored user-question string? | **NO** — question text is a semantic haystack (soil / water / disease / practices / range tokens), not a whitelist |
| Depends on Home vs Crop? | **NO** |
| Depends on UI option names? | **NO** — `farming-needs` is only a **producer** of `question_type` when Crop question is empty (`"{crop} farming-needs"` contains `needs`) |
| Reusable outside Wheat? | **YES** — maize, rice, tomato, strawberry soil, lettuce range, future-crop-x |
| Independently testable? | **YES** — 18 generic architecture tests + this closure matrix |

`applyQuestionTypeKnowledgeTargets()` is called on both Home and Crop QUS paths.

---

## 7. Generic Entity Resolution Contract

**No crop-specific fallback was added.** Strawberry and lettuce do not get production `if` branches.

### Contract (generic, data-driven)

| Path | Condition | Behavior |
|------|-----------|----------|
| **A — Known entity** | Catalog / selector recognizes the surface | `resolution=resolved`, `crop_id` + scientific name from DATA, query uses scientific name then common aliases |
| **B — Unknown but identifiable named surface** | `extractNamedAgriculturalEntityCandidate` finds a distinctive surface; catalog miss | `named_entity_state=unresolved`, `subject.type=crop`, query uses the **surface string** as entity DATA, same semantic tails |
| **C — Unresolvable / no named entity** | No catalog hit and no distinctive named surface | `named_entity_state=none` (or null on Crop selector path); soil / livestock / beekeeping / category fallbacks may apply; no invented scientific name |

Promotion from B → A is **catalog DATA**, not a new algorithm branch.

Detect order in `QueryUnderstandingService::detectSubject`: explicit entities → `recognizeCrop` → botanical family → livestock → **named-entity candidate (B)** → crop category → soil fallback → beekeeping system. Soil fallback cannot wipe a named crop surface (strawberry soil stays `subject.type=crop`).

### Runtime matrix

| Case | Catalog | Path | Scientific / common | Aliases used in variants | Resolved entity | Query behavior |
|------|---------|------|---------------------|--------------------------|-----------------|----------------|
| Wheat | yes | **A** | *Triticum aestivum* / bread wheat | wheat, bread wheat | `crop_id=wheat` resolved | `Triticum aestivum agronomic requirements` |
| Maize / Corn | yes | **A** | *Zea mays* / maize | maize | `crop_id=corn` resolved | `Zea mays agronomic requirements` |
| Rice | yes | **A** | *Oryza sativa* / rice | paddy | `crop_id=rice` resolved | `Oryza sativa agronomic requirements` |
| Tomato | yes | **A** | *Solanum lycopersicum* / tomatoes | lycopersicon esculentum | `crop_id=tomato` resolved | `Solanum lycopersicum agronomic requirements` |
| Strawberry | **no** | **B** | none invented | surface `strawberries` | unresolved crop | `strawberries` + **question** tails (`soil requirements` because the question is soil, not because the crop is strawberry) |
| Lettuce | **no** | **B** | none invented | surface `lettuce` | unresolved crop | `lettuce temperature range` (range class) |
| future-crop-x (Crop selector) | selector DATA | **A** (injected) | *Futurus cropus* / Future Crop X | future crop x | `crop_id=future-crop-x` | `Futurus cropus agronomic requirements` |
| Futurus cropus (Home text) | **no** | **B** | none invented | extracted surface `agronomic cultivating futurus` (extractor is greedy; still unresolved, no fake taxon) | unresolved | surface + agronomic tails |

**Acceptable contract:** Home vernacular resolution to a scientific name **requires catalog DATA**. That is intentional. Do not invent external taxonomy at runtime.

**Documented catalog-WIP quality issue (not fixed in 2C):** `extractNamedAgriculturalEntityCandidate` can over-fire on entity-less questions (`How to improve soil fertility for farming?` → subject `crop`; `beekeeping pollination management practices` → subject `crop`). That lives in dirty `AgriculturalEntityCatalog` (Group E). Tightening stop-tokens would be a **generic** catalog fix later — not a strawberry/lettuce branch, and not this commit.

---

## 8. Future Crop Verification

| Layer | Status |
|-------|--------|
| Crop selector DATA → QUS / semantics / plan / variants / gate | **PROVEN** |
| Live provider retrieval | **PROVEN** — queries `Futurus cropus agronomic requirements` (+ practices / management / common-name tails). Raw **50** (Crossref success; OpenAlex / Semantic Scholar / FAOSTAT empty). After rank/gate **0** |
| Stage 4 validation | **NOT TESTABLE** — no ranked survivors |
| Stage 5 synthesis / citations | **NOT TESTABLE** — no validated pile |
| HTTP / frontend | **NOT TESTABLE** this closure |

Do not fabricate provider success for an artificial taxon. The generic gate correctly refused off-entity “future crop” literature.

---

## 9. Question-Type Verification

`requirements` follows **question semantics**, not crop identity.

| Case | `question_type` | Sense | Evidence | `v0` |
|------|-----------------|-------|----------|------|
| Wheat / maize / rice / tomato agronomic requirements | `requirements` | `agronomic_requirements` | `requirement_specification` | `{scientific_name} agronomic requirements` |
| Wheat soil requirements | `requirements` | `soil_requirements` | `requirement_specification` | `Triticum aestivum soil requirements` |
| Maize water requirements | `requirements` | `crop_water_requirement` | `requirement_specification` | `Zea mays water requirements` |
| Rice cultivation practices | `general` | `plant_growth` | `topic_aligned_scientific_claim` | `Oryza sativa cultivation practices` |
| Tomato major diseases | `symptoms` | `disease` | `symptom_description` | `Solanum lycopersicum plant diseases` |
| Wheat pests | `causes` | `pest` | `causal_relationship` | `Triticum aestivum crop pests` |
| Lettuce optimal temperature range | `range` | `plant_growth` | `numeric_range_or_optimal_value` | `lettuce temperature range` |
| Wheat seed rate kg/ha | `quantity` | `general_knowledge` | `numeric_rate_or_quantity` | quantity/production tails |

`AnswerComposer::requiresSupportedMeasurement()` still returns **true** for `quantity` and `range`, and still **exempts** `requirements`. Measurement validation was **not** globally disabled.

---

## 10. Evidence Integrity

| Rule | Status |
|------|--------|
| `MIN_SCORE_CROP_TOPIC` | **70.0** — unchanged |
| Gate hard-reject below 70 when entity+topic required | unchanged |
| Verification layer | unchanged in 2C |
| Directness enums | unchanged |
| Claim relation enums | unchanged |
| Disposition | unchanged (`insufficient_direct_supporting_retained` observed live) |

**2C gate change (not a threshold cut):**

| | |
|--|--|
| OLD | topics ignored when any factor existed → physiology needles could wipe agronomic papers |
| NEW | topics scored even when factors exist; sense overlay from catalog + `ScientificQuestionSemantics::senseQueryTerms` |
| WHY | generic class of Home `requirements` questions was routed through an empty-property + plant-growth gate |
| IMPACT | agronomic titles can pass 70; threshold itself is the same |
| TEST | `GenericScientificResearchArchitectureTest`; live Home 70→8 |

---

## 11. Ranking Integrity

Ranking still: dedup → rank → `filterRelevant` (gate) → diversify.  
Home wheat live: **70 → 70 → 8**. Survivors are wheat/agronomy, scores ≥ 163.5.  
No crop-specific ranker branch. No 2C change to ranker scoring formula.

---

## 12. Citation Integrity

`EvidenceVerificationLayer::isPrimaryCitationEligible` remains:

```php
return $directness === ScientificEvidenceDirectnessAssessor::DIRECT;
```

SUPPORTING may appear in `additional_information`. It is not promoted to `citations[]`.  
Home live run: 2 SUPPORTING usable → 0 citations. That is the approved rule, not a loosening.

Frontend still hides empty Sources and uses title as HTTP(S) link (`FieldCropCanonicalAnswerView`, `HomeScientificResearchSearch`).

---

## 13. Language Contract

Approved R2: **answer language = question language** (`resolveAnswerLanguageFromQuestion`).

Live QUS matrix this closure:

| Question language | `question_language` | `answer_language` |
|-------------------|---------------------|-------------------|
| English wheat requirements | `en` | `en` |
| Arabic wheat requirements | `ar` | `ar` |
| Turkish wheat requirements | `tr` | `tr` |
| French wheat requirements | `fr` | `fr` |

`Phase5UnitB1AnswerLanguageContractTest`: **7 passed**.

`ScientificResearchLanguageContractTest`: **7 failed**. Each asserts **platform locale = answer language**.

| Test | OLD EXPECTATION | CURRENT APPROVED CONTRACT | WHY OBSOLETE |
|------|-----------------|---------------------------|--------------|
| `test_accept_language_sets_answer_language_not_question_language` | EN question + `Accept-Language: ar` → `answer_language=ar` | EN → `en` | Contradicts R2 |
| `test_platform_versus_question_language_matrix` | `answer_language === platform` | `=== question language` | Contradicts R2 |
| `test_retrieval_variants_stay_english_when_platform_is_arabic` | `answer_language === platform` | Arabic question → `ar` | Answer-language assert obsolete; retrieval-English is a separate concern |
| `test_arabic_platform_english_evidence_does_not_yield_english_final_prose` | EN question → Arabic prose | EN → EN | Contradicts R2 |
| `test_english_platform_arabic_question_yields_english_prose` | AR question → EN prose | AR → AR | Contradicts R2 |
| `test_platform_locale_matrix_localizes_composer_prose` | EN question → platform prose | EN → EN | Contradicts R2 |
| `test_missing_locale_falls_back_to_english_like_set_locale_from_header` | AR question → `answer_language=en` | AR → `ar` | Contradicts R2 |

**Exact later test update (do not do it in this closure; do not revert R2):** change every `assertSame($platform, answer_language)` to `assertSame($questionLanguage, answer_language)`, and change prose-language asserts to follow the question. Leave production `resolveAnswerLanguageFromQuestion` as-is.

---

## 14. Sweet Potato Snapshot Analysis

| Item | Value |
|------|--------|
| Test file | `backend/tests/Unit/Agriculture/Research/EntitySpecificityPreservationContractTest.php` |
| Method | `test_crop_profile_sweet_potato_variant_snapshot_is_unchanged` |
| Snapshot / fixture | frozen variant list in the test (not a JSON fixture) |
| Original contract | physiology-first farming-needs tails: `Ipomoea batatas cultivation growth`, `sweet potatoes cultivation growth`, … |
| Current approved contract | agronomic-first requirement-specification tails from `ScientificQuestionSemantics` |
| Actual live variants | `Ipomoea batatas agronomic requirements`, `cultivation practices`, `crop management`, `sweet potatoes agronomic requirements`, `sweet potatoes cultivation practices` |
| Why stale | snapshot froze pre-2C `cultivation growth` planning; 2C replaced that **generic** class, not sweet potato |
| Production correct? | **YES** — same tails as wheat/maize/rice |
| Action | later update the expected array to agronomic-first; **do not revert 2C** |

Other methods in that class (Home sweet potato species, chicken/poultry surfaces) **passed**.

---

## 15. SearchFlow / Accuracy Regression

Re-run this closure.

### `ScientificResearchSearchFlowTest` — 2 passed / 5 failed

| Failure | Class | Why |
|---------|-------|-----|
| `test_land_egypt_multilingual_same_semantics_and_pipeline` (`platformAnswerLanguage()` vs `ar`) | **B** | R2: Arabic question → `answer_language=ar`, not platform `en` |
| `test_multilingual_factual_targets_tomato_wheat_bee_hydroponic` (same) | **B** | same obsolete platform-locale assert |
| `test_land_egypt_negative_offtopic_not_direct_unless_classification` (greenhouse gerbera DIRECT) | **C** | untracked SearchFlow / directness destack; file is not a 2C-owned test; 2C did not change DIRECT assessor rules |
| `test_e2e_contract_understanding_plan_target_variants_directness_answer` (ML soil classification DIRECT vs RELATED) | **C** | same pre-existing directness WIP |
| `test_bilingual_pairs_a_to_h_preserve_question_type_and_required_evidence` (`أنواع أسماك` → `classification` not `species`) | **C** | QUS/catalog destack predates 2C; not caused by `ScientificQuestionSemantics` |

**Why C pre-dates 2C:** `ScientificResearchSearchFlowTest` is **untracked destack**. 2C did not author it and did not change `ScientificEvidenceDirectnessAssessor` DIRECT rules. Failures are R2-obsolete language asserts plus older land/species WIP.

### `ScientificResearchAnswerAccuracyTest` — 33 passed / 7 failed / 2 skipped

| Failure | Class | Why |
|---------|-------|-----|
| `test_adversarial_direct_preferred_over_supporting_for_summary` (expects `germination` in destacked Arabic main answer) | **C** | Phase 2 destack: main answer is DIRECT framing; details live in findings/additional |
| `test_answerability_ginger_cultivation_still_ok` (expects `Zingiber` inside main answer) | **C** | destack / composer presentation WIP |
| `test_residual_a_snippet_prefers_temperature_over_secondary_metrics` | **C** | dirty ranking/presentation in this already-modified Accuracy file |
| `test_residual_ginger_growth_heat_demotes_essential_oil_primary` | **C** | same |
| `test_format_c_wheat_seed_rate_vs_plastic` (`insufficient_evidence`) | **C** | quantity/measurement compose WIP in dirty Accuracy file; 2C did not weaken `requiresSupportedMeasurement` for `quantity` |
| `test_format_d_freshwater_fish_list_not_pollution` | **C** | ranking/presentation destack |
| `test_format_f_ginger_oil_benefits_allowed` (expects oil/Zingiber in destacked body) | **C** | destack |

**Why C pre-dates 2C:** `ScientificResearchAnswerAccuracyTest` was already dirty destack before 2C. 2C did not rewrite Accuracy fixtures. Failures assert concatenated-answer / ranking behavior that Phase 2 destack intentionally split.

Do not rewrite these tests in this closure to force green. Do not weaken production.

---

## 16. MCP Status

Docker runtime this closure:

| Field | Value |
|-------|--------|
| Enabled | **true** (`FREE_SEARCH_MCP_ENABLED`) |
| Command | `uvx` |
| Arguments | `free-search-mcp` |
| Timeout | **30000** ms |
| This Home E2E | MCP **not invoked** (in-process compose only) |
| Product contract | `PostSynthesisBlockingWorkGateTest` **9/9**: sufficient DIRECT skips MCP; insufficient / supporting-only / empty citations **keep** legacy execute (includes MCP) |
| Empty / blocking | When invoked on insufficient Home, wait is the configured 30s. Still a **product decision** (blocking vs non-blocking). Not changed |

Do not silently change timeout, enablement, or fallback.

---

## 17. Crop Stage 3 Status

| Item | Value |
|------|--------|
| Current contract | `shouldOverlapIndependentScholarlyProviders` returns **false** when `isCropProfileIntent()` |
| Home | overlap allowed for `generic_research` (this Home run: OA+CR+SS concurrent, Stage 3 **7.5 s**) |
| Crop | sequential scholarly providers |
| Retry / budget | `ADEQUATE_RESULT_COUNT=8`, `MAX_VARIANTS_PER_PROVIDER=2`, search budget default 45s — unchanged |
| Crop remesure this closure | **not repeated** (prior 2B order of magnitude ~6–10 s Stage 3) |
| Why no generic optimization now | Changing Crop to overlap would be a **concurrency product change**, not a 2C semantic defect. Sequential remains the contract |

`ProviderConcurrencyOrchestratorTest` including `crop profile does not enter scholarly concurrency path` **passed** in the full-suite prefix.

---

## 18. Frontend Gate

Re-run this closure from `frontend/`:

| Gate | Result |
|------|--------|
| `npm test` (Vitest) | **42 files, 287 passed** |
| `tsc -b` | **PASS** (via `npx tsc -b` then `vite build`) |
| Vite production build | **PASS** — 217 modules, `dist/assets/index-ByLNhHpG.js` |

Home/Crop UI contracts (unit, not a live browser session):

- Main answer destacked from `additional_information`
- Sources rendered only when `citations.length > 0`
- Title is the HTTP(S) link
- Empty Sources hidden
- Duplicate uncertainty heading hidden
- Language follows result / question contract in i18n tests

Live browser paint of the Home wheat payload was **not** executed this closure. The live composer payload is: insufficient main answer + supporting additional information + empty citations — which the UI tests already cover.

Prior “84 tests” referred to a Home/Crop/API subset. The full frontend gate is **287**.

---

## 19. Backend Gate

Targeted scientific + Phase 1 + Crop cultivation (Docker `php artisan test --filter=...`):

| Suite | Result | Notes |
|-------|--------|--------|
| `GenericScientificResearchArchitectureTest` | **18 passed** | 2C-owned |
| `AnswerComposerEvidenceStateContractTest` | **15 passed** | includes agronomic measurement-exemption |
| `Phase6U61CropSelectorAndContextContractTest` | **12 passed** | Phase 2 agronomic Crop |
| `PostSynthesisBlockingWorkGateTest` | **9 passed** | Phase 2B |
| `FieldCropCultivationTest` | **8 passed** | Crop HTTP |
| `SpaCsrfAuthenticationContractTest` | **8 passed** | Phase 1 |
| `AuthExtensionTest` / `OAuthProviderIdentityTest` | **passed** (in 97-pass targeted batch) | Phase 1 |
| `Phase5UnitB1AnswerLanguageContractTest` | **7 passed** | R2 |
| `ScientificEvidenceDirectnessAssessorTest` | **4 passed** | |
| Phase 4 relevance / ranker / verification (in filter batch) | **passed** (not in the 14-fail set) | |
| `ScientificResearchLanguageContractTest` | **0/7** | **B** obsolete vs R2 |
| `EntitySpecificityPreservationContractTest` snapshot | **1 fail** | **B** stale agronomic-first |
| `AgriculturalResearchAgentStage2Test` | **24 pass / 2 fail** | **C** catalog extractor over-fire |
| SearchFlow / Accuracy | see §15 | **B/C** |

Targeted approved-contract gate: **PASS**.  
Failures in that run are classified B/C/E, not A.

---

## 20. Full PHPUnit Result

**FULL PHPUNIT = NOT GREEN**

| Item | Value |
|------|--------|
| Command | `docker exec wsa-enterprise-backend-1 php artisan test --exclude-group=live` |
| Duration | ~563 s then **abort** |
| Cause | `Fatal error: Allowed memory size of 134217728 bytes exhausted` in PHPUnit `TestCase.php` / Laravel `HandleExceptions.php` |
| Finished? | **NO** — died during/after `Ai02CoreFoundationTest` |
| Usable as green gate? | **NO** |

### Phase 2C-owned vs pre-existing in the incomplete run

**Not Phase 2C regressions (B/C/E):**

- Sweet-potato Crop variant snapshot (**B**)
- LanguageContract / SearchFlow language (**B** vs R2)
- Accuracy destack/ranking (**C**)
- Stage 2 soil/beekeeping subject type (**C** catalog WIP)
- Stage 3/4/5 large fail clusters (**C** dirty tests + destack)
- `AgriculturalScientificKnowledgeEngineTest::test_engine_executes_generic_research_with_external_discovery` — **B** vs Phase 2B `skipExternalDiscoverers: true`
- FAOSTAT / capability / statistical aligner / modality / ProviderActivation (**C** / untracked WIP)
- `RetrievalSemanticContractTest::test_primary_query_contains_mandatory_semantic_components` — synthetic plan expects `drought` inside `v0`; approved 2C `v0` is entity + question-type tail (live maize water = `Zea mays water requirements`). **B** stale v0 token snapshot, not a wheat special-case

**Phase 2C A:** none identified.

---

## 21. Runtime Matrix

| Case | qtype / sense | Entity | Live retrieval | Citations | Notes |
|------|---------------|--------|----------------|-----------|--------|
| Home bread wheat requirements | requirements / agronomic | A resolved wheat | **70 / 70 / 8** | 0 (SUPPORTING-only) | Gate proven; DIRECT absent this pile |
| Home wheat (comparison question) | same | A | semantics only | n/a | same targets as Crop |
| Crop wheat + scientific-research | same | selector A | FieldCrop HTTP 8/8 | n/a this script | sequential Stage 3 |
| Home maize / rice / tomato requirements | requirements / agronomic | A | semantics | n/a | same tails, different entity DATA |
| Home strawberry soil | requirements / soil | **B** unresolved | semantics | n/a | soil tails because **question**, not crop-id |
| Home lettuce range | range / plant_growth | **B** unresolved | semantics | n/a | measurement class retained |
| Home Futurus | requirements / agronomic | **B** unresolved | semantics | n/a | no invented taxonomy |
| Crop future-crop-x | requirements / agronomic | A via selector DATA | **50 raw / 0 ranked** | n/a | Stage 4–5 NOT TESTABLE |
| AR/EN/TR/FR wheat requirements | requirements | A or language-dependent catalog | QUS only | n/a | answer_language = question language |

---

## 22. Regression Classification

Legend: **A** genuine 2C regression · **B** obsolete / superseded contract · **C** pre-existing WIP · **D** environment · **E** documented product/architecture blocker

| Test / cluster | Status | Class | Root cause | 2C impact | Action |
|----------------|--------|-------|------------|-----------|--------|
| GenericScientificResearchArchitectureTest (18) | PASS | — | — | owns | keep |
| FieldCropCultivationTest (8) | PASS | — | — | none harmful | keep |
| Composer evidence (15) / Phase6U61 (12) / PostSynthesis (9) | PASS | — | — | companions | keep |
| Phase 1 CSRF / Auth / OAuth | PASS | — | — | none | keep |
| Phase5 R2 language (7) | PASS | — | — | none | keep |
| LanguageContract (7) | FAIL | **B** | platform locale vs R2 | none | update tests later; do not revert R2 |
| SearchFlow language (2) | FAIL | **B** | `platformAnswerLanguage()` | none | same |
| SearchFlow greenhouse/ML DIRECT (2) | FAIL | **C** | untracked directness destack | none | leave WIP |
| SearchFlow fish species vs classification | FAIL | **C** | أنواع → classification | none | leave WIP |
| Accuracy 7 | FAIL | **C** | destack + dirty ranking/measurement | none | leave |
| EntitySpecificity sweet-potato snapshot | FAIL | **B** | physiology-first snapshot | agronomic-first variants | update snapshot later |
| Stage2 soil / beekeeping | FAIL | **C** | catalog named-entity over-extract | none | generic catalog stop-tokens later; not this commit |
| KnowledgeEngine external discovery | FAIL | **B** | 2B skip-external | none | update test to expect skip |
| RetrievalSemantic primary `drought` in v0 | FAIL | **B** | stale v0 token snapshot vs requirement tails | generic tail shape | do not revert 2C |
| Stage 3/4/5 / FAOSTAT / aligner / modality | FAIL | **C** | destack / dirty / untracked | none | leave |
| Full PHPUnit OOM 128 MB | ABORT | **D** | PHP memory_limit | none | infra; not a 2C semantic defect |
| MCP blocking 30s | — | **E** | product decision | none | decide later |
| Crop sequential Stage 3 | — | **E** | intentional contract | none | do not parallelize now |

**No A.**

---

## 23. Remaining Blockers

1. **MCP** — enabled, 30s, blocking on insufficient Home Stage 5. Product decision.
2. **Crop Stage 3 sequential** — intentional `crop_profile` contract.
3. **Full PHPUnit NOT GREEN** — 128 MB OOM; leftover WIP/legacy failures in the prefix.
4. **LanguageContract + SearchFlow language** — obsolete vs R2; tests need later migration.
5. **Sweet-potato / RetrievalSemantic v0 snapshots** — stale vs agronomic/requirement tails.
6. **Accuracy + Stage 3/4/5 destack** — pre-existing WIP.
7. **Catalog named-entity over-extract** on entity-less soil/beekeeping questions (Group E catalog).
8. **Home uncatalogued vernaculars** resolve only as Path B until catalog DATA exists.
9. **Future-crop Stage 4–5** not testable with live providers.
10. **This Home wheat pile** had no DIRECT citations — scientific integrity, not a 2C gate bug. A later live HTTP run may still differ by provider draw.

---

## 24. Genericity Verdict

| # | Question | Answer | Evidence |
|---|----------|--------|----------|
| 1 | Is Wheat absent from production **algorithmic** logic? | **YES** | No `wheat` in `ScientificQuestionSemantics`, 2C gate, or requirement-tail builder. Wheat exists only as catalog/UI **DATA** |
| 2 | Is Triticum absent from production **algorithmic** logic? | **YES** | Same. *Triticum aestivum* is catalog DATA used as a query term |
| 3 | Is any crop name used as an algorithmic switch? | **NO** | Lookups are DATA maps, not `if ($crop === '…')` in 2C algorithm |
| 4 | Is any question string used as an algorithmic switch? | **NO** | Haystack tokens, not stored question whitelist |
| 5 | Is `ScientificQuestionSemantics` crop-agnostic? | **YES** | Inputs are type / evidence / factors / sense |
| 6 | Is Query Planning generic? | **YES** | Tails × entity DATA; Home/Crop share builder |
| 7 | Is Evidence Evaluation generic? | **YES** | Shared validator; Home live used it unchanged |
| 8 | Is Ranking generic? | **YES** | Shared rank + gate; 70→8 on wheat is the generic 70 rule |
| 9 | Is Citation eligibility generic? | **YES** | DIRECT-only, crop-agnostic |
| 10 | Is Synthesis generic? | **YES** | Shared composer |
| 11 | Is Answer Composition generic? | **YES** | Destack + shared fields |
| 12 | Is Home/Crop semantic classification consistent? | **YES** | Same type/sense/targets/v0 for the same requirements class |
| 13 | Can known crops use the same architecture? | **YES** | Wheat, maize, rice, tomato, Crop HTTP 8/8 |
| 14 | Can unknown crops follow a generic resolution path? | **YES** | Path B unresolved surface |
| 15 | Can future crops be introduced without algorithmic code changes? | **YES** | Crop selector DATA; Home Path B until catalog DATA |
| 16 | Can new questions of existing semantic classes work without new branches? | **YES** | Soil/water/practices/disease/pest/range/quantity matrix |
| 17 | Are evidence thresholds unchanged? | **YES** | 70 |
| 18 | Are DIRECT citation rules unchanged? | **YES** | DIRECT-only |
| 19 | Is there any new special case introduced by Phase 2C? | **NO** | No wheat / Home-only / question-string production branch |

If any answer were NO, that would be an architectural defect. None is.

---

## 25. Selective Commit Plan

**DO NOT COMMIT. DO NOT STAGE. Human GO required.**

Do not use `git add .` / `git add -A`. Add **only** the paths below after GO.

### GROUP A — Phase 2 semantic/core repair

| PATH | GROUP | WHY | RELATED PHASE | SAFE TO COMMIT? | REASON |
|------|-------|-----|---------------|-----------------|--------|
| `backend/app/Services/Agriculture/Research/QueryUnderstandingService.php` | A (+ C overlay) | Phase 2 Crop context + 2C `applyQuestionTypeKnowledgeTargets` on Home and Crop; farming-needs topic injection removed | 2 + 2C | **YES** | Inseparable; do not split |
| `backend/app/Services/Agriculture/Research/Search/ScientificSearchQueryBuilder.php` | A (+ C overlay) | Phase 2 farming-needs query → 2C `isRequirementSpecificationPlan` + semantic tails | 2 + 2C | **YES** | Inseparable |
| `backend/app/Services/Agriculture/Research/Synthesis/AnswerComposer.php` | A | Measurement exemption for `requirements`; destack; DIRECT citations unchanged | 2 | **YES** | Required companion |
| `backend/app/Services/Agriculture/Research/Synthesis/AnswerSynthesisExecutionReport.php` | A | `additionalInformation` destack field | 2 | **YES** | Required companion |
| `frontend/src/public/HomeScientificResearchSearch.tsx` | A | Destack, hide empty Sources, title links | 2 | **YES** | UI contract |
| `frontend/src/public/FieldCropCanonicalAnswerView.tsx` | A (+ C tsc) | Destack + `_index` unused-arg for `tsc` | 2 + 2C | **YES** | UI + typecheck |
| `frontend/src/public/FieldCropFarmingNeedsPanel.tsx` | A | Crop panel destack | 2 | **YES** | UI contract |
| `frontend/src/api/researchAgent.ts` | A | Home research types / destack fields | 2 | **YES** | API types |
| `frontend/src/api/fieldCropCultivation.ts` | A | Crop canonical types | 2 | **YES** | API types |
| `frontend/src/i18n/locales/en.json` | A | Destack / Sources / uncertainty copy | 2 | **YES** | with UI |
| `frontend/src/i18n/locales/ar.json` | A | same | 2 | **YES** | with UI |
| `frontend/src/i18n/locales/fr.json` | A | same | 2 | **YES** | with UI |
| `frontend/src/i18n/locales/tr.json` | A | same | 2 | **YES** | with UI |

### GROUP B — Phase 2B generic performance repair

| PATH | GROUP | WHY | RELATED PHASE | SAFE TO COMMIT? | REASON |
|------|-------|-----|---------------|-----------------|--------|
| `backend/app/Services/Agriculture/Research/AgriculturalScientificKnowledgeEngine.php` | B | `skipExternalDiscoverers: true` on Home generic path | 2B | **YES** | Home 52s leftover OA/CR discovery |
| `backend/app/Services/Agriculture/ScientificSourceDiscoveryPipeline.php` | B | `discoverMissingSections(..., bool $skipExternalDiscoverers=false)` | 2B | **YES** | companion |

### GROUP C — Phase 2C generic architecture repair

| PATH | GROUP | WHY | RELATED PHASE | SAFE TO COMMIT? | REASON |
|------|-------|-----|---------------|-----------------|--------|
| `backend/app/Services/Agriculture/Research/ScientificQuestionSemantics.php` | C | New crop-agnostic knowledge-target / tail / sense contract | 2C | **YES** | core 2C |
| `backend/app/Services/Agriculture/Research/Search/ScientificEvidenceRelevanceGate.php` | C | Topics always scored; sense overlay; **MIN_SCORE still 70** | 2C | **YES** | core 2C |
| `docs/architecture/PHASE-2C-GENERIC-RESEARCH-CLOSURE-REPORT.md` | C | Closure design record | 2C | **YES** | docs |
| `docs/architecture/PHASE-2C-FINAL-AUDIT-REPORT.md` | C | This final closure report | 2C | **YES** | docs |

QUS + QueryBuilder are listed under A because they already carried Phase 2 dirty hunks; they **must travel with C**.

### GROUP D — Required tests/contracts belonging to A/B/C

| PATH | GROUP | WHY | RELATED PHASE | SAFE TO COMMIT? | REASON |
|------|-------|-----|---------------|-----------------|--------|
| `backend/tests/Feature/GenericScientificResearchArchitectureTest.php` | D | Multi-crop / anti-special-case | 2C | **YES** | 18/18 |
| `backend/tests/Unit/Agriculture/Research/AnswerComposerEvidenceStateContractTest.php` | D | Destack + measurement exemption | 2 | **YES** | 15/15 |
| `backend/tests/Feature/Phase6U61CropSelectorAndContextContractTest.php` | D | Crop agronomic contract | 2 | **YES** | 12/12 |
| `backend/tests/Feature/PostSynthesisBlockingWorkGateTest.php` | D | 2B skip-external / MCP gate | 2B | **YES** | 9/9 |
| `frontend/src/public/homeScientificResearchSearch.test.ts` | D | Home UI destack / Sources | 2 | **YES** | passed |
| `frontend/src/public/fieldCropCanonicalAnswer.test.ts` | D | Crop UI destack / Sources | 2 | **YES** | passed |

### GROUP E — Pre-existing WIP — MUST NOT COMMIT

See §26.

**Suggested commit sequence after GO (still not this task):**

1. One scientific commit: Groups A+B+C+D together (QUS/builder cannot be split from 2C).  
2. Or three commits only if a human splits hunks inside QUS/builder first — not recommended.

---

## 26. Exact Files NOT to Commit

| PATH | GROUP | WHY | RELATED PHASE | SAFE TO COMMIT? | REASON |
|------|-------|-----|---------------|-----------------|--------|
| `backend/app/Services/Agriculture/FieldCropTaxonomyCatalog.php` | E | Catalog destack | pre-2C WIP | **NO** | unrelated DATA churn |
| `backend/app/Services/Agriculture/Research/AgriculturalEntityCatalog.php` | E | Entity extractor destack | pre-2C WIP | **NO** | over-extract / aliases WIP |
| `backend/app/Services/Agriculture/Research/ResearchPlanner.php` | E | Planner destack | pre-2C WIP | **NO** | not required for 2C semantics |
| `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md` | E | ADR WIP | pre-2C | **NO** | unrelated |
| `docs/architecture/BASELINE-RECONCILIATION.md` | E | untracked planning | WIP | **NO** | unrelated |
| `docs/architecture/REMEDIATION-DEPENDENCY-MAP.md` | E | untracked planning | WIP | **NO** | unrelated |
| `backend/.env.example` | E | mixed secrets/config | mixed | **NO** | Phase 1 leftover + MCP comments |
| `backend/composer.lock` | E | untracked lock | env | **NO** | not 2C |
| `backend/tests/Feature/AgriculturalResearchAgentStage3Test.php` | E | dirty Stage 3 | WIP | **NO** | failing destack |
| `backend/tests/Feature/CompositionalAgriculturalSemanticsTest.php` | E | dirty | WIP | **NO** | |
| `backend/tests/Feature/ScientificResearchAnswerAccuracyTest.php` | E | dirty destack | WIP | **NO** | 7 classified C |
| `backend/tests/Feature/ScientificResearchAnswerRelevanceTest.php` | E | dirty | WIP | **NO** | |
| `backend/tests/Feature/ScientificUpstreamRetrievalFixTest.php` | E | dirty | WIP | **NO** | |
| `backend/tests/Feature/SweetPotatoEntityResolutionTest.php` | E | dirty | WIP | **NO** | |
| `backend/tests/Feature/ScientificResearchSearchFlowTest.php` | E | untracked destack | WIP | **NO** | B+C failures |
| `backend/tests/Feature/HomeMultilingualSemanticContractTest.php` | E | untracked | WIP | **NO** | |
| `backend/tests/Feature/GenericAgriculturalEntityArchitectureTest.php` | E | untracked | WIP | **NO** | |
| `backend/tests/Feature/SemanticTargetAndSupportedValueContractTest.php` | E | untracked | WIP | **NO** | |
| `backend/tests/Feature/Agriculture/Intelligence/ProviderActivationAndLevel4FeatureTest.php` | E | untracked FAOSTAT | WIP | **NO** | |
| `backend/e2e-tmp/**` | E | probes / backups / logs | temp | **NO** | generated / local |
| `admin-mobile/e2e-tmp/**` | E | mobile e2e junk | temp | **NO** | unrelated |
| Auth / Admin / Marketplace / Jobs / Library / Phase 10 | — | out of scope | — | **NO** | not in this dirty scientific set; do not add |

Also do not commit `frontend/dist/**` if generated locally.

---

## 27. Final Gate

**PASS WITH DOCUMENTED BLOCKERS**

| Gate | Result |
|------|--------|
| Generic architecture intact | **YES** |
| No crop-specific / question-specific production special case | **YES** |
| Home Wheat maximum practical E2E | **YES** — Stage 2–5 in-process, live providers; MCP/HTTP/UI paint not run |
| Home/Crop semantic equivalence | **YES** for knowledge targets / type / sense / v0 |
| Entity resolution generic A/B/C | **YES** — no strawberry/lettuce algorithm |
| Future-crop characterized honestly | **YES** — retrieval proven; Stage 4–5 not testable |
| Evidence 70 + DIRECT unchanged | **YES** |
| Regression executed; every seen failure classified | **YES** |
| Frontend tests + tsc + production build | **YES** (287 / tsc / Vite) |
| Full PHPUnit attempted | **YES** — **NOT GREEN** (OOM 128 MB) |
| MCP / Crop Stage 3 documented | **YES** |
| Unrelated WIP unchanged | **YES** |
| No destructive git / stage / commit / push | **YES** |
| Selective-commit file list | **YES** — Groups A–E |

### Acceptance checklist

- [x] Generic architecture remains intact
- [x] No crop-specific production special case
- [x] No question-specific production special case
- [x] Home Wheat re-verified through maximum practical E2E
- [x] Home/Crop semantic equivalence verified
- [x] Entity resolution contract is generic
- [x] Strawberry/Lettuce do not require special-case production logic
- [x] Future-crop behavior accurately characterized
- [x] Evidence thresholds unchanged
- [x] DIRECT citation rules unchanged
- [x] Regression suite executed
- [x] Every failure classified
- [x] LanguageContract classified against R2
- [x] Sweet Potato snapshot classified
- [x] SearchFlow/Accuracy classified
- [x] Frontend tests pass
- [x] TypeScript passes
- [x] Production build passes
- [x] Full PHPUnit attempted
- [x] Full PHPUnit blocker documented (OOM)
- [x] MCP blocker documented
- [x] Crop Stage 3 contract documented
- [x] No unrelated WIP changed
- [x] No destructive Git operation
- [x] No files staged
- [x] No commit
- [x] No push
- [x] Exact selective-commit file list produced

---

## Final Git Audit

| Item | Value |
|------|--------|
| Branch | `phase-18-m18-ai-marketing-communications` |
| HEAD | `879f67dc9b4de3a696dc81344acc9aa65a9c4796` |
| Staged files | **none** |
| Unstaged | Phase 2/2B/2C scientific + pre-existing catalog/planner/WIP tests + frontend destack + `.env.example` + ADR-021 (same dirty set as start, plus this report) |
| Untracked | 2C docs/semantics/generic test; e2e-tmp; other WIP tests/docs |
| Destructive git | **none** |
| Unrelated WIP edited | **none** |
| Commit | **none** |
| Push | **none** |

STOP. No commit. No push. Wait for human GO.
