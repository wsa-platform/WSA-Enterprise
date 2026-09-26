# ADR-022 — Canonical Scientific Question (CSQ) V1

**Status:** ACCEPTED — Architectural Decision  
**Scope:** Scientific research understanding and retrieval architecture  
**Decision type:** Semantic architecture / contract  
**Branch:** `phase-18-m18-ai-marketing-communications`  
**Baseline:** `994a400a4a8505025a998f9bb27d38aceaa4b14b`  
**Date:** 2026-09-26

## 1. Decision

Adopt **Canonical Scientific Question (CSQ) V1** as the single frozen, language-independent, provider-independent semantic authority for a scientific research question.

CSQ is created at the end of `QueryUnderstandingService::understand()`, before planning and retrieval compilation.

The canonical role graph is:

- **ENTITY** — the scientific subject/entity being studied.
- **TARGET** — the product, output, or affected object of the question.
- **PROCESS** — the cause, intervention, operation, or requested factor.
- **PROPERTY** — the measure/property being requested.
- **RELATION** — the relationship between the roles, including causal, comparative, associative, descriptive, temporal, or spatial relationships.

Downstream layers may compile, retrieve, rank, validate, compose, and present from CSQ, but must not redefine these semantic roles.

## 2. Core semantic rule

CSQ must preserve independent semantic roles instead of collapsing them into an entity/property bag.

For a question such as:

`تأثير التغذية على إنتاج اللبن في الأبقار`

the intended semantic structure is:

- ENTITY = cattle
- TARGET = milk production
- PROCESS = feeding/nutrition
- PROPERTY = production/yield/quantity of the target
- RELATION = causal(process → target), scoped to the entity

This is a structural rule, not a milk/cattle special case.

The implementation must therefore prevent a production/output phrase from being silently converted into the entity or reduced to a property-only representation.

## 3. Target identity policy

TARGET is first-class but does **not** require a canonical ontology ID.

Target fields must preserve:

- source-language surface
- `kind`: `product_output` | `affected_object` | `none`
- verified `normalized_key` when available
- `resolution`: `none` | `unresolved` | `resolved`
- `canonical_id`, only when independently verified
- `canonical_namespace`, only when independently verified

Unknown targets retain their surface and remain unresolved.

The system must never invent target IDs and must never use a FAOSTAT item code as a CSQ target/entity ID.

## 4. Process policy

PROCESS is a first-class semantic role.

Its primary source is the causal/intervention `affector`, followed by an existing catalog factor that explicitly occupies the causal/intervention role.

Existing factor lists remain available for retrieval compilation and compatibility, but `topics[]` is not the authority for PROCESS.

An unknown process must remain unresolved with its surface; it must never be replaced by generic `agriculture`.

## 5. Property policy

CSQ PROPERTY is the single semantic authority for the requested property.

The property key, surface, and relation to the relevant role are frozen once CSQ is created.

Duplicate property names such as `requested_property` and `requested_property_key` must not become competing semantic authorities.

Property-family safety rules, including R7, remain mandatory. Water, temperature, classification, and quantity questions must not acquire `yield`/`production` terms merely because an older extraction path produced them.

## 6. Relation policy

RELATION is explicit and must not be invented from keywords alone.

A causal relation requires an actual causal argument pair from the existing semantic extraction.

For causal questions:

- `from_role = process`
- `to_role = target` when TARGET exists
- otherwise the relation may resolve to PROPERTY according to the frozen semantic contract.

A keyword such as “effect” alone is insufficient to manufacture a causal relation.

## 7. Immutability and freeze point

CSQ is an immutable value object.

It is frozen exactly once at the end of Query Understanding and before the Planner consumes the query.

Home and Crop use the same CSQ shape with context-specific filling:

- Home: `research_context = home`
- Crop: `research_context = crop_profile`

Home/Crop episode ownership remains unchanged.

Legacy semantic fields remain temporarily available as compatibility/dual-write fields during migration, but they are not allowed to regain semantic authority after CSQ is frozen.

## 8. KnowledgeQueryPlan boundary

`KnowledgeQueryPlan` remains an execution envelope.

It may retain compatibility copies such as topics, subject, research intent, and requested information, but these fields are not a second semantic authority.

The Plan references CSQ through the normalized query rather than duplicating the role graph.

## 9. RetrievalSpecification

Introduce a derived `RetrievalSpecification` between CSQ/Plan and query compilation.

It contains provider-neutral retrieval concepts tagged by role, including:

- entity
- target
- process
- property
- geography
- time

It may contain compile terms, ordering rules, and provider compilation requirements.

RetrievalSpecification is not semantic authority and must never be placed on CSQ as English query strings, provider filters, variants, FAOSTAT codes, evidence items, or final answers.

## 10. RetrievalSemanticContract migration

`RetrievalSemanticContract::apply()` remains active during migration.

Migration is staged:

1. CSQ dual-write while existing behavior remains unchanged.
2. RetrievalSpecification is derived from CSQ while RSC still runs.
3. Golden/equivalence tests compare the new specification against existing RSC behavior.
4. Only after the equivalence gate passes may RSC `apply()` become bypassable behind a controlled mechanism.
5. Deleting or materially removing the existing RSC behavior requires a later explicit architectural authorization.

R7, R13, and R17 are protected and must never disappear.

R7 must remain a compile-time semantic safety constraint: non-productivity property families must not receive productivity/yield terms.

## 11. Query Builder boundary

`ScientificSearchQueryBuilder` becomes a compiler:

**CSQ + RetrievalSpecification → provider-neutral/ provider-specific query variants**

The Builder must not:

- parse `original_question` to recreate roles
- infer TARGET or PROCESS
- run causal extraction as a second semantic authority
- use `plan.topics` as semantic authority
- use English query variants as the canonical meaning

Variants remain retrieval artifacts.

Existing global variant limits remain unchanged:

- maximum 5 variants
- maximum 2 variants per provider

No change is authorized to these budgets by this ADR.

## 12. Ranking and evidence boundaries

This ADR does not merge ranking, relevance, directness, validation, evidence, synthesis, or presentation.

During migration, `variants[0]` may remain the existing rank input artifact. Later CSQ-derived ranking signals may be added only additively and under separate verification.

Evidence axes remain separate:

- directness
- claim relation
- lifecycle/disposition

No threshold is changed by this ADR.

## 13. AnswerComposer and DIRECT authority

`AnswerComposer::requiresFactualDirectEvidence()` remains authoritative for final DIRECT-evidence requirements.

A CSQ-derived cache may be stored for consistency checking, but it must be behaviorally equivalent to the Composer rule.

If a cache and Composer disagree, Composer wins and the mismatch is a contract failure to investigate.

Validation's own `requiresFactualDirectEvidence` remains separate and must not be unified with Composer by this ADR.

The existing final-answer confidence threshold of **0.50** remains unchanged.

## 14. FAOSTAT boundary

No FAOSTAT adapter, completeness guard, or verified dimension map is changed by this ADR.

CSQ may provide semantic inputs to the existing FAOSTAT resolution path:

`entity + property + geography + time → existing verified FAOSTAT dimensions`

Only verified mappings may produce FAOSTAT requests.

Incomplete dimensions must continue to prevent HTTP execution.

PROCESS and RELATION must never be converted into FAOSTAT elements.

No new milk/cattle FAOSTAT map entries are authorized by this ADR.

## 15. Multilingual policy

CSQ surfaces remain in the source language.

Semantic equivalence across Arabic, English, French, and Turkish is established through the same verified entity/factor keys, property keys, relation type, target kind, and resolution state.

English compilation is retrieval compilation only. English is never the semantic authority.

Answer language remains the language of the user's question.

## 16. Protected contracts

This ADR does not authorize changes to:

- MODEL B public tenant resolution
- answer-language rule
- original source preservation
- Home/Crop episode isolation
- Evidence Verification / save eligibility
- separate evidence axes
- final-answer confidence threshold 0.50
- DIRECT/Composer behavior
- ScientificUserPresentation / primary_answer
- ScientificEvidenceItem identity
- dedup first-wins keys
- FAOSTAT guards/maps
- `994a400` presentation/viewer behavior

## 17. WIP and Git safety

The existing dirty/untracked working tree remains protected.

In particular, implementation touching these WIP areas requires explicit authorization for the relevant phase:

- `QueryUnderstandingService.php`
- `AgriculturalEntityCatalog.php`
- `ResearchPlanner.php`
- existing WIP Feature tests
- `.env.example`
- `composer.lock`
- ADR-021 working-tree changes
- `e2e-tmp/`
- baseline/remediation working files
- any other pre-existing dirty/untracked file

No broad staging, reset, restore, clean, stash, rebase, or force-push is permitted.

Each implementation phase must follow:

**Evidence → Root Cause → Repair → Test → Regression → Verification → Commit**

No implementation is authorized merely by this ADR; implementation remains phase-gated and requires explicit execution authorization.

## 18. Required implementation order

The architectural migration is staged as:

0. Baseline/WIP protection and explicit grant.
1. Add CSQ type and attach it to AgriculturalKnowledgeQuery without behavior change.
2. Freeze ENTITY/TARGET/PROCESS/PROPERTY/RELATION in QUS with dual-write compatibility.
3. Make Plan reference CSQ without duplicating semantic authority.
4. Add RetrievalSpecification while RSC remains active.
5. Compile search variants from CSQ + RetrievalSpecification while preserving existing budgets.
6. Prove RSC equivalence, especially R7/R13/R17, before any bypass.
7. Add ranking consumption of CSQ only as a separately verified additive change.
8. Let relevance/composer read frozen CSQ labels without merging evidence axes or changing DIRECT rules.
9. Run the full regression matrix and release gate.

Each phase stops on contract drift.

## 19. Hard-stop conditions

Stop implementation immediately if any of the following occurs:

- TARGET receives an invented ontology/FAOSTAT ID.
- ENTITY/TARGET/PROCESS/PROPERTY/RELATION are collapsed again.
- English variants become CSQ meaning.
- `topics[]` or `variants[0]` becomes semantic authority.
- R7/R13/R17 are removed.
- RSC is bypassed before equivalence tests pass.
- FAOSTAT guard/map changes without a separate decision.
- Composer and Validation DIRECT rules are unified or weakened.
- 0.50 threshold changes.
- Home/Crop isolation changes.
- `994a400` presentation/viewer behavior is altered.
- Pre-existing WIP is overwritten without explicit authorization.

## 20. Acceptance principle

CSQ V1 is accepted as the architectural semantic contract.

Implementation is considered complete only when:

1. CSQ is frozen before planning/retrieval compilation.
2. E/T/P/M/R remain independently addressable.
3. Target/process information is preserved through retrieval compilation.
4. RetrievalSpecification is derived rather than authoritative.
5. RSC safety behavior is preserved and equivalence-tested before bypass.
6. Query Builder no longer redefines semantic meaning.
7. FAOSTAT, Composer, DIRECT, 0.50, Presentation, evidence axes, and Home/Crop contracts remain unchanged.
8. Multilingual semantic equivalence is verified.
9. The full regression suite passes.
10. Final diff and working-tree safety are reviewed before any release commit.

**Final status:** ACCEPTED as an architectural decision.  
**Implementation status:** Not authorized by this ADR alone.
