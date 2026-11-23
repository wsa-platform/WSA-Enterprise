# SCIENTIFIC_SEARCH_REMEDIATION_REPORT

## Purpose

Implement and verify the eight approved audit issues (Arabic scientific search P0 + EVD/SRC/TEST) without weakening evidence rules or violating ADR-022/023.

## Executive Summary

Remediation is **implemented and focused-tested**. Arabic wheat irrigation now shares `crop_water_requirement` with English; executable variants contain no Arabic script; Organic Eprints is eligible for Arabic organic questions; AGRIS LocalIndex returns hits for Arabic wheat after specializer English compilation; R2 language tests updated; evidence status aliases canonicalized; per-source language capability catalog added.

## Baseline Snapshot

- Pre-flight: `00-PRE-FLIGHT.md` — `BASELINE_READY_WITH_LIMITATIONS`
- HEAD at start: `d655c40`
- Branch: `phase-18-m18-ai-marketing-communications` (dirty WIP preserved)

## Status Summary

| Issue | Status | Root Cause | Evidence |
|---|---|---|---|
| ISS-AR-003 | VERIFIED | Productivity outranked irrigation; sense missed explicit irrigation helper | RemediationTest + pre/post QUS |
| ISS-AR-001 | VERIFIED | Arabic surfaces in variants + AGRIS entity=`القمح` | joinTerms + AGRIS specializer; post AGRIS probe |
| ISS-FTS-001 | VERIFIED | AND matching + Arabic tokens | Same as AR-001; AR AGRIS raw=16 |
| ISS-AR-006 | VERIFIED | compileTerm/specExecutable surface passthrough | Unit compileTerm test |
| ISS-AR-002 | VERIFIED | English-only OE cues | OE eligible AR=1 |
| ISS-AR-004 | VERIFIED | Missing organic/soil topic factors | Factors/topics in remediation test |
| ISS-AR-005 | TESTED | Unmarked EN snippets in AR answers | LanguageContract excerpt label |
| ISS-EVD-002 | TESTED | Status string drift | EvidencePipelineStatus + canonicalize |
| ISS-SRC-002 | TESTED | No language capability axis | Catalog covers 10 keys |
| ISS-TEST-001 | VERIFIED | Platform-locale answer_language tests | LanguageContract R2 rewrite |

## Implementation Details

See `02-ENGINEERING-DESIGN.md`, `03-IMPLEMENTATION-REGISTER.md`.

## Test Results

See `04-TEST-EVIDENCE.md` — 12/12 focused PASS.

## Arabic / English Comparison

See `mission_post_agris_probe.json` and `mission_preflight_qus.json`.

## Source Capability Verification

See `06-SOURCE-LANGUAGE-CAPABILITIES.md`.

## Answer Quality Assessment

Arabic answers keep Arabic chrome; English evidence excerpts are labeled (`مقتطف من المصدر`). Full narrative scientific synthesis quality remains extractive Composer architecture (not rewritten this round).

## Git and Release Evidence

See `07-GIT-RELEASE-VERIFICATION.md` (filled after push).

## Remaining Issues

- TR/FR live retrieval: NOT_RUN
- Broader suite UpstreamRetrievalFixTest failures: UNDETERMINED vs WIP
- Full end-to-end Composer scientific depth: PARTIALLY_VERIFIED

## Final Acceptance Status

| Gate | Status |
|---|---|
| G1 Semantic integrity | VERIFIED (wheat AR/EN sense) |
| G2 Query integrity | VERIFIED (no Arabic executable variants) |
| G3 Source capability/eligibility | VERIFIED (OE AR + language catalog) |
| G4 Retrieval quality | PARTIALLY_VERIFIED (AGRIS live AR/EN; not full scholarly matrix) |
| G5 Evidence integrity | NOT weakened; live validation path not fully re-scored |
| G6 Answer quality | PARTIALLY_VERIFIED (language labeling) |
| G7 Status contract | TESTED |
| G8 Regression safety | PARTIALLY_VERIFIED (focused green) |
| G9 Repo integrity | WIP preserved; mission files isolated |
| G10 Release integrity | Pending commit/push verification |
