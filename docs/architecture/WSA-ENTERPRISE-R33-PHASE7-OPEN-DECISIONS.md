# R33 — Phase 7 Open Decisions

**Document type:** Phase 7 decision recovery / documentation (P7-U4)
**Requirement ID:** R33 — Answer Presentation & Navigation
**Branch baseline HEAD:** `e7643c6ab8a7e18b4150416d138b344739d62958`
**Status of this document:** ANALYSIS / DOCUMENTATION — pending Human GO for commit
**Does not authorize:** implementation, commit, push, or P7-U5

---

## 1. Purpose

Recover, classify, and document R33 for Phase 7 unit **U7.4 / P7-U4** using only repository evidence.

This document does **not**:

- invent a historical R33 specification;
- invent acceptance criteria;
- reopen R1–R7, STRUCT-03, P7-U1, P7-U2, or P7-U3 as new undecided architecture;
- absorb P7-U5 work.

---

## 2. Scope

**In scope (documentation only):**

- R33 title and phase mapping;
- presentation / navigation topics named by the Master Remediation Plan and Phase 7 forensic audit as R33 / U7.4 open decisions;
- classification of what is already verified vs still open after P7-U1–U3;
- human decisions required before further product UI work;
- phase ownership and deferred items.

**Out of scope:**

- Backend / React / Flutter / test implementation changes;
- Phase 3–6 scientific pipeline changes;
- P7-U5 (Home conflicts presentation + R7 feedback UI wiring).

---

## 3. Historical Recoverability

### HISTORICAL DEFINITION

**NOT FULLY RECOVERABLE FROM AVAILABLE EVIDENCE**

| Recovered | Not recovered |
|-----------|---------------|
| Exact **title**: “Answer Presentation & Navigation” | Full historical R33 inventory / detailed requirement text |
| Phase mapping: Phase **7** with `#20`, `#21`, `#22` | Verbatim definitions of `#20`–`#22` (numeric problem statements remain UNRECOVERED) |
| Working concerns audited from code (multi-answer, citation navigation, Google intermediary, Flutter URL open, internal WSA links) | A standalone historical “R33 inventory” document |

**Evidence:**

- `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md` — §4 Problem Traceability (“R33 — Answer Presentation & Navigation”; Phase 7 map)
- `docs/architecture/WSA-ENTERPRISE-PROBLEM-REGISTRY.md` — “R33 — Answer Presentation & Navigation” (`RECOVERED_EXACT` **title only**)
- `docs/architecture/AUTHORITATIVE-PROBLEM-REGISTRY.md` — §B R33
- `docs/architecture/SYSTEM-WIDE-FORENSIC-AUDIT.md` — §18 (“R33 inventory doc: NOT FOUND”)
- `docs/architecture/MASTER-REMEDIATION-EXECUTION-PLAN.md` — §13 U7.4; §25 Open Decision #5

**Do not treat the title alone as a complete historical specification.**

---

## 4. Evidence Sources

| Source | Role |
|--------|------|
| `docs/architecture/MASTER-REMEDIATION-EXECUTION-PLAN.md` | Authoritative Phase 7 unit plan (U7.1–U7.4); Open Decisions #5–#6 |
| `docs/architecture/ADR-021-system-wide-fastest-safe-remediation-plan.md` | R33 title + Phase 7 mapping |
| `docs/architecture/WSA-ENTERPRISE-PROBLEM-REGISTRY.md` | Title recovery status |
| `docs/architecture/AUTHORITATIVE-PROBLEM-REGISTRY.md` | R33 / NEW-05 / NEW-06 working inventory |
| `docs/architecture/SYSTEM-WIDE-FORENSIC-AUDIT.md` | Navigation/link findings; NEW-IDs |
| `docs/architecture/SYSTEM-ARCHITECTURE-MAP.md` | Client presentation map |
| `docs/architecture/REMEDIATION-DEPENDENCY-MAP.md` | G8 / R33 dependency notes |
| `docs/architecture/WSA-ENTERPRISE-ARCHITECTURAL-DECISIONS-R1-R7.md` | Normative R1–R7 (not reopened) |
| `docs/architecture/CANONICAL-CONTRACT-SPECIFICATION-v1.md` | Locale / Accept-Language contract vocabulary |
| Phase 7 forensic audit (agent transcript; unit table P7-U1…P7-U5) | P7-U4 = R33 docs; P7-U5 optional Home conflicts + R7 UI |
| P7-U1 commit `43f7f70…` | Crop dual-emit Stage 5 root contract |
| P7-U2 commit `2028938…` | React Crop canonical Stage 5 UI |
| P7-U3 commit `e7643c6…` | Flutter Stage 5 model + Accept-Language + citation launcher |
| Tests | `Phase7P7U1CropCanonicalAnswerContractTest.php`; `fieldCropCanonicalAnswer.test.ts`; `phase7_p7_u3_stage5_contract_test.dart`; Home research UI |

**Master plan path note:** Repository copy is `docs/architecture/MASTER-REMEDIATION-EXECUTION-PLAN.md` (untracked in working tree at documentation time). Content inspected for Phase 7 / R33 matches the plan authority described in P7-U4 mission (Phase 7 API + React + Flutter + Answer UI; U7.4 = R33 open decisions documentation). External paste path `/mnt/data/…` was not available in this environment.

---

## 5. Verified Decisions

These are **not** new R33 product choices. They are established decisions/contracts that must not be reopened without contradictory evidence.

| Key | Decision | Status model | Evidence class | Evidence |
|-----|----------|--------------|----------------|----------|
| R1–R7 | Public tenant MODEL B; answer language = question language; ar/en/fr/tr + original source preserve; Home free-form vs Crop predefined; evidence-gated auto-save; separate directness/claim_relation/disposition; positive-only feedback dataset | VERIFIED | VERIFIED_EXISTING_DECISION | `WSA-ENTERPRISE-ARCHITECTURAL-DECISIONS-R1-R7.md` |
| STRUCT-03 / P7-U1 | Crop scientific answer primary surface = Stage 5 root fields; legacy profile retained as compatibility projection | VERIFIED / IMPLEMENTED | VERIFIED_CONTRACT + VERIFIED_IMPLEMENTED_BEHAVIOR | P7-U1 commit; `Phase7P7U1CropCanonicalAnswerContractTest.php` |
| P7-U2 | React Crop UI presents canonical Stage 5 as primary; direct citation `href`; no Google intermediary in Crop canonical view | VERIFIED / IMPLEMENTED | VERIFIED_IMPLEMENTED_BEHAVIOR + VERIFIED_TESTED_BEHAVIOR | P7-U2; `FieldCropCanonicalAnswerView.tsx`; `fieldCropCanonicalAnswer.test.ts` |
| Direct citations (current clients) | Citation URLs open as direct external links / launcher targets; not rewritten through Google/search | VERIFIED / IMPLEMENTED | VERIFIED_IMPLEMENTED_BEHAVIOR + VERIFIED_TESTED_BEHAVIOR | React `HomeScientificResearchSearch.tsx` / Crop view `href={citation.url}`; Flutter `CitationLauncher`; tests asserting no `google.com` |
| Google intermediary (current code) | No Google/search intermediary present in research citation open path | VERIFIED / IMPLEMENTED | VERIFIED_IMPLEMENTED_BEHAVIOR | Forensic audit §18; Master Plan default **no**; client tests |
| Client Stage 5 field set (minimum) | Clients consume/present `status`, `answer`/`concise_summary`, citations(+url), confidence, limitations, language metadata (and related Stage 5 fields where emitted) | VERIFIED / IMPLEMENTED | VERIFIED_CONTRACT + VERIFIED_IMPLEMENTED_BEHAVIOR | Master Plan §13; P7-U1–U3 models/UI |
| NEW-05 remediations (Flutter Accept-Language) | Flutter HTTP client sends `Accept-Language` | VERIFIED / IMPLEMENTED | VERIFIED_IMPLEMENTED_BEHAVIOR + VERIFIED_TESTED_BEHAVIOR | P7-U3 `http_client.dart`; `phase7_p7_u3_stage5_contract_test.dart` Accept-Language group |
| NEW-06 remediations (accuracy signals) | Home React surfaces confidence/limitations; Crop React canonical view surfaces confidence/limitations/uncertainty/conflicts | VERIFIED / IMPLEMENTED | VERIFIED_IMPLEMENTED_BEHAVIOR + VERIFIED_TESTED_BEHAVIOR | `HomeScientificResearchSearch.tsx`; `FieldCropCanonicalAnswerView.tsx`; Crop tests |
| Flutter citation launcher | Platform `url_launcher` opens citation URL unchanged after http(s) validation | VERIFIED / IMPLEMENTED | VERIFIED_IMPLEMENTED_BEHAVIOR + VERIFIED_TESTED_BEHAVIOR | P7-U3 `citation_launcher.dart`; focused launcher tests |

**Note:** Older registry rows that still say “Flutter: no URL launch” / “Flutter missing Accept-Language” / “FE drops confidence” describe **pre–P7-U2/U3** state. On HEAD `e7643c6…` those rows are **STALE relative to current implementation** unless registries are later updated in a separate docs task.

---

## 6. Verified Implemented Behavior

| Behavior | Clients | Evidence |
|----------|---------|----------|
| Single scientific answer block (not multi-answer carousel) | React Home/Crop; Flutter research screen | Forensic audit §18; UI components |
| Direct external citation navigation | React `target="_blank"` + `rel=noopener`; Flutter `launchUrl` | Code + tests |
| No Google intermediary rewrite | All audited research citation paths | Tests + code absence |
| Confidence / limitations displayed when backend provides them | React Home + Crop canonical; Flutter research | UI + tests |
| UI language ≠ answer language | R2 + P7-U3 client language state | `WSA-ENTERPRISE-ARCHITECTURAL-DECISIONS-R1-R7.md`; Flutter `ClientLanguageState` |
| Crop legacy sections not primary scientific answer | React Crop after P7-U2 | `FieldCropCanonicalAnswerView` / profile article tests |

---

## 7. Open Decisions

These remain **OPEN** for product/architecture Human GO. Implementation of new product behavior after documentation requires separate authorization (typically P7-U5 or later UI units — **not** absorbed into P7-U4).

### R33-OD-1 — Multi-answer UI

| Field | Value |
|-------|--------|
| Decision key | `R33-OD-1` |
| Title | Multi-answer / carousel presentation |
| Status | OPEN |
| Evidence class | OPEN_DECISION |
| Evidence | Master Plan §13 U7.4; §25 Open Decision #5; Forensic audit §18 “Multiple answers — Not implemented”; AUTHORITATIVE registry R33 |
| Existing implementation | Single-answer presentation only |
| Already established? | NO |
| Open question | Should Home and/or Crop present multiple alternative scientific answers, and if so under what backend contract? |
| Human decision | **HUMAN_DECISION_REQUIRED** |
| Blocking? | Non-blocking for P7-U1–U3 closure; blocking for any multi-answer UI implementation |
| Phase ownership | Phase 7 product decision (U7.4); UI only after decision |
| Implementation required now? | NO |
| Historical definition recoverable? | NO (concern named; acceptance criteria not recovered) |

### R33-OD-2 — Internal WSA topic / deep-link routing

| Field | Value |
|-------|--------|
| Decision key | `R33-OD-2` |
| Title | Internal WSA link routing from research answers |
| Status | OPEN |
| Evidence class | OPEN_DECISION |
| Evidence | Master Plan §13 U7.4 (“internal WSA links”); Forensic audit §18 “Internal WSA topic links — Not found in Home research UI” |
| Existing implementation | Absence proven for Home research UI |
| Already established? | NO |
| Open question | Should answers/citations deep-link into internal WSA surfaces (Library, Crop profile, training, etc.), and what URL/route contract applies? |
| Human decision | **HUMAN_DECISION_REQUIRED** |
| Blocking? | Non-blocking for current direct-external citation behavior |
| Phase ownership | Phase 7 product decision (U7.4); UI after decision |
| Implementation required now? | NO |
| Historical definition recoverable? | NO |

### R33-OD-3 — Google / search intermediary policy (future)

| Field | Value |
|-------|--------|
| Decision key | `R33-OD-3` |
| Title | Product policy for citation intermediary |
| Status | OPEN (policy) + VERIFIED current behavior = no intermediary |
| Evidence class | OPEN_DECISION (policy) / VERIFIED_IMPLEMENTED_BEHAVIOR (current code) |
| Evidence | Master Plan §13 “Google intermediary… **do not invent**”; §25 Open Decision #5 “(default **no**)”; AUTHORITATIVE R33; client tests forbid `google.com` rewrite |
| Existing implementation | Direct URL only |
| Already established? | **Partial:** current shipped behavior and remediation default is **no intermediary**; formal long-term product policy still listed as open |
| Open question | Confirm permanently: citations must always open the backend-provided URL directly with no Google/search intermediary? |
| Human decision | **HUMAN_DECISION_REQUIRED** (even if expected confirmation is “no intermediary”) |
| Blocking? | Non-blocking while default remains no |
| Phase ownership | Phase 7 U7.4 |
| Implementation required now? | NO (already matches default **no**) |
| Historical definition recoverable? | Partial (default **no** documented; full historical R33 text not recovered) |

### R33-OD-4 — Flutter “must open citation URLs?” (Master Plan Open Decision #6)

| Field | Value |
|-------|--------|
| Decision key | `R33-OD-4` |
| Title | Flutter citation URL open requirement |
| Status | OPEN in Master Plan wording; **IMPLEMENTED** on HEAD via P7-U3 |
| Evidence class | VERIFIED_IMPLEMENTED_BEHAVIOR (code) + OPEN_DECISION (if product still needs formal close of Open Decision #6) |
| Evidence | Master Plan §25 #6; P7-U3 `CitationLauncher` + tests |
| Already established as code? | YES on `e7643c6…` |
| Open question | Does product formally close Open Decision #6 as “required and done,” or retain optional stance? |
| Human decision | **HUMAN_DECISION_REQUIRED** for formal product closure only |
| Phase ownership | Phase 7 |
| Implementation required now? | NO |
| Historical definition recoverable? | Title-level only |

---

## 8. Deferred Decisions

| Item | Owning phase / unit | Why deferred | Evidence |
|------|---------------------|--------------|----------|
| Home conflicts/uncertainty presentation gaps + R7 feedback button UI | **P7-U5** (optional; Phase 7 forensic audit) | Explicitly separate from U7.4 docs | Phase 7 forensic audit unit P7-U5 |
| Full Flutter Crop farming-needs / profile research screen | Outside P7-U3; not U7.4 implementation | P7-U3 scope excluded inventing Crop profile UI | P7-U3 mission / implementation report |
| Persist `tr`/`fr` as first-class library locales | Language / persistence (Master Plan Open Decision #7; Phase 5 lineage) | Not R33 presentation/navigation | Master Plan §25 #7; NEW-04 |
| Public research auth vs demo-org bind (MODEL B vs C) | Phase 8 / security | Security Open Decision #3 | `SECURITY-BOUNDARY-DISCOVERY.md` §19; R1 already documents MODEL B as Phase-2 decision — security residual is separate |
| Numeric `#20`–`#22` problem statement import | Governance / human import | `#1–#31` UNRECOVERED | Problem registries |

**P7-U5 must not be started or absorbed by P7-U4.**

---

## 9. Unrecoverable Historical Requirements

| Missing information | Status | Notes |
|---------------------|--------|-------|
| Full historical R33 specification body | UNRECOVERABLE | Title only in ADR-021 |
| Standalone R33 inventory document | UNRECOVERABLE / NOT FOUND | Forensic audit §18 |
| Verbatim `#20`, `#21`, `#22` statements | UNRECOVERABLE | Mapped to Phase 7 with R33; definitions not in repo |
| Historical acceptance criteria for multi-answer / internal links | UNRECOVERABLE | Named as open concerns only |
| External Master Plan paste file `/mnt/data/…` | NOT AVAILABLE IN THIS ENVIRONMENT | Repo copy used: `MASTER-REMEDIATION-EXECUTION-PLAN.md` |

---

## 10. Conflicts / Contradictions

| Topic | Conflict | Resolution for P7-U4 |
|-------|----------|----------------------|
| Flutter Accept-Language / citation launch | Older registries (AUTHORITATIVE NEW-05; forensic Flutter findings) say absent; HEAD `e7643c6…` implements both | **STALE DOC vs CURRENT CODE** — document current HEAD as authoritative for implemented behavior; do not “fix” registries in this unit unless separately authorized |
| Answer language vs Accept-Language | Historical forensic claimed answer language followed platform locale; R2 documents `answer_language = question_language` | **R2 is normative Phase-2 decision** — do not reopen in R33; Accept-Language is UI/API locale transport, not answer rewrite (see R2 + CANONICAL-CONTRACT-SPECIFICATION-v1) |
| ADR-019 Crop protection vs ADR-021 Crop in-scope | Historical ADR conflict noted in forensic open questions | Outside R33 presentation scope; STRUCT-03 handled in P7-U1 under ADR-021 Phase 7 authority |

**NONE IDENTIFIED** that require inventing a new R33 product answer inside P7-U4.

---

## 11. Human Decisions Required

| ID | Exact question | Affected scope | Blocking? |
|----|----------------|----------------|-----------|
| HD-R33-1 | Should the product support **multi-answer** presentation (Home and/or Crop)? If yes, what backend multi-answer contract is authoritative? | React, Flutter, API shape | Blocks multi-answer UI only |
| HD-R33-2 | Should research answers expose **internal WSA deep-links**? If yes, which destinations and route scheme? | React, Flutter, possibly API metadata | Blocks internal-link UI only |
| HD-R33-3 | Confirm product policy: citation open must remain **direct URL only** with **no Google/search intermediary**? | All clients; citation UX | Non-blocking while default remains no |
| HD-R33-4 | Formally close Master Plan Open Decision #6: Flutter citation open is **required** (now implemented)? | Flutter research UX governance | Non-blocking for code already shipped |
| HD-R33-5 | Authorize **P7-U5** scope (Home conflicts presentation + R7 feedback UI) after U7.4? | Home React (+ optional Flutter feedback UI) | Blocks P7-U5 start |

**Do not rank options. Do not declare winners in this document.**

---

## 12. Phase Ownership

| Concern | Phase / unit |
|---------|--------------|
| R33 documentation | **Phase 7 — U7.4 / P7-U4** (this document) |
| Crop Stage 5 primary API/UI | Phase 7 — P7-U1 / P7-U2 (**CLOSED**) |
| Flutter Stage 5 + Accept-Language + citation launcher | Phase 7 — P7-U3 (**CLOSED + PUSHED** at `e7643c6…`) |
| Remaining R33 product UI (multi-answer, internal links) | Phase 7 — **after** Human GO; not started |
| Home conflicts + R7 feedback UI | Phase 7 — **P7-U5** (NOT STARTED) |
| Language persistence TR/FR | Phase 5 / language lineage |
| Public tenant security residuals | Phase 8 |

---

## 13. Acceptance / Closure Conditions

P7-U4 (documentation) may be considered **closed** when Human GO confirms:

1. This document is accepted as the R33 recovery record for Phase 7;
2. Open decisions HD-R33-1…HD-R33-4 are either decided or explicitly deferred with owners;
3. P7-U5 remains a separate unit and is not silently included;
4. No implementation changes were required for U7.4 itself.

P7-U4 does **not** require implementing multi-answer or internal WSA links to close.

---

## 14. Traceability Matrix

| R33 ID / key | Title | Status | Evidence source | Evidence location | Implemented? | Tests | Contract | Established? | Open question | Human decision? | Doc destination | Phase | Impl required? | Deferred? | Historical recoverable? |
|--------------|-------|--------|-----------------|-------------------|--------------|-------|----------|--------------|---------------|-----------------|-----------------|-------|----------------|-----------|-------------------------|
| R33 (title) | Answer Presentation & Navigation | VERIFIED (title) | ADR-021 | ADR-021 §4 | N/A | N/A | Title only | YES (title) | Full historical body | NO | This doc | 7 | NO | NO | Title YES / body NO |
| R33-V-DIRECT | Direct citation URLs | VERIFIED / IMPLEMENTED | Code + tests | Home/Crop React; Flutter launcher | YES | Crop + Flutter tests | Stage 5 citations.url | YES | — | NO | This doc §5–6 | 7 | NO | NO | Concern YES |
| R33-V-NO-GOOGLE | No Google intermediary (behavior) | VERIFIED / IMPLEMENTED | Code + tests + Master Plan default | Clients; Master Plan §13/§25 | YES | assert no google.com | — | YES (behavior) | Long-term policy confirm | HD-R33-3 | This doc | 7 | NO | NO | Partial |
| R33-V-SIGNALS | Confidence/limitations presentation | VERIFIED / IMPLEMENTED | React Home/Crop; Flutter | UI components | YES | Crop + Flutter | Stage 5 fields | YES | — | NO | This doc | 7 | NO | NO | Via NEW-06 |
| R33-V-AL | Flutter Accept-Language | VERIFIED / IMPLEMENTED | P7-U3 | `http_client.dart` | YES | P7-U3 tests | Accept-Language | YES | — | NO | This doc | 7 | NO | NO | Via NEW-05 |
| R33-OD-1 | Multi-answer UI | OPEN | Master Plan + audit | §13 U7.4; forensic §18 | NO | N/A | None | NO | Multi-answer yes/no + contract | HD-R33-1 | This doc §7 | 7 | NO until GO | Possibly later UI | NO |
| R33-OD-2 | Internal WSA links | OPEN | Master Plan + audit | §13 U7.4; forensic §18 | NO | N/A | None | NO | Deep-link scheme | HD-R33-2 | This doc §7 | 7 | NO until GO | Possibly later UI | NO |
| R33-OD-3 | Intermediary policy | OPEN (policy) | Master Plan §25 #5 | Master Plan | Behavior = no | Tests | — | Partial | Confirm permanent no | HD-R33-3 | This doc §7 | 7 | NO | NO | Partial |
| R33-OD-4 | Flutter must open URLs | OPEN (governance) / IMPLEMENTED (code) | Master Plan §25 #6; P7-U3 | Flutter | YES | P7-U3 | — | Code YES | Formal close #6 | HD-R33-4 | This doc §7 | 7 | NO | NO | Partial |
| P7-U5 | Home conflicts + R7 UI | DEFERRED | Phase 7 forensic audit | Audit unit P7-U5 | NO | N/A | R7 API exists | NO | Authorize P7-U5 | HD-R33-5 | This doc §8 | 7 (U5) | YES only after GO | YES → P7-U5 | Unit YES |

---

## Appendix A — R33 recovery matrix summary (P7-U4)

| Classification | Count (this recovery) |
|----------------|------------------------|
| VERIFIED existing / implemented presentation facts | Multiple (see §5–6) |
| OPEN product decisions | R33-OD-1, R33-OD-2, R33-OD-3 (policy), R33-OD-4 (formal close) |
| DEFERRED | P7-U5; full Flutter Crop profile UI; non-R33 language/security opens |
| UNRECOVERABLE | Full historical R33 body; `#20`–`#22` text; R33 inventory doc |
| CONFLICT requiring new product invention in P7-U4 | NONE |

---

## Appendix B — Explicit non-goals

- Do not invent multi-answer or internal-link designs here.
- Do not modify P7-U1/U2/U3 code to “complete” R33.
- Do not start P7-U5 in this unit.
- Do not treat stale forensic Flutter/React gaps as current HEAD truth without re-checking `e7643c6…`.
