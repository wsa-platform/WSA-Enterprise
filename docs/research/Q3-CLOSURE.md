# Q3 Closure — Generic Agricultural Entity Resolution

Status: CLOSED / VERIFIED

Canonical question:

"ما كمية الري المناسبة لأشجار الرمان في المناطق الجافة؟"

Verified entity:

pomegranate

Scientific identity:

Punica granatum

Category:

fruit_tree

Entity-dependent:

true

Verified behavior:

- pomegranate / الرمان resolves to the generic agricultural entity architecture
- scientific identity is preserved as Punica granatum
- irrigation quantity questions remain entity-dependent when a crop/entity is named
- Punica-compatible evidence may become DIRECT when all other gates pass
- Triticum / wheat evidence cannot become DIRECT for the pomegranate question
- generic irrigation evidence cannot bypass the entity requirement
- unresolved named agricultural entities preserve their surface form
- true entity-less D7 behavior remains available
- Q4 species identity behavior remains intact
- Q5 semantic behavior remains intact
- Stage 5 sufficiency remains identity-safe
- Post-Synthesis sufficiency gate remains unchanged

## TEST VERIFICATION

Authoritative source: Q3 FINAL TEST CONTRACT REPORT.

ScientificUpstreamRetrievalFixTest:

- 13 passed
- 1 remaining pre-existing WIP failure
- OpenAlex 429 → Crossref fallback test PASS after intentional bounded-variant contract update

Stage 3:

- 40 passed
- 5 pre-existing WIP failures
- none classified as current Q3 regression

Generic Entity Architecture:

- 16 tests passed
- 108 assertions

Q3:

- PASS

Q4:

- PASS

Q5:

- PASS

Q2:

- PASS

D7:

- PASS

Evidence Identity:

- PASS

Directness:

- PASS

Stage 5:

- PASS

Search Query Preservation:

- PASS

No Hardcoding:

- PASS

Focused Regression:

- 160 passed
- 6 pre-existing WIP failures
- 2 Post-Synthesis harness limitations run separately
- 2 AnswerAccuracy dependency skips

Post-Synthesis:

- production behavior PASS by architecture/tests
- test harness limitation remains because AgriculturalResearchResult is final and Mockery cannot replace its methods
- do not change production code to accommodate this harness limitation

## REMAINING PRE-EXISTING WIP

The following are NOT Q3 regressions:

1. fish Egypt aquaculture vs cultivation
2. fruit production intent
3. all_sources_failed / partial_source_failure
4. extra fao_stat in source selection
5. extra fao_stat in internet-first
6. extra fao_stat in library-not-primary
7. Post-Synthesis Mockery final-class limitation

Do not treat these as Q3 follow-up work. Do not change Q2, Q4, Q5, D7, Free Search MCP, Post-Synthesis gate behavior, or ADR-001 implementation to address them.

## Q3 HARDENING

No Q3-specific behavioral branch exists in production code.

No behavioral hardcoding for:

- رمان
- pomegranate
- Punica granatum
- Q3

These occur only as taxonomy/catalog data or tests where applicable.

## LATENCY CONTRACT

- MAX_VARIANTS_PER_PROVIDER is intentionally bounded at 2
- Crossref/OpenAlex retrieval must respect the bound
- the fallback test was corrected to validate the bounded contract
- do not restore the old 5-variant execution requirement

## FINAL DECISION

Q3 is CLOSED and VERIFIED.

No further Q3 production changes are authorized unless a new, independently demonstrated regression is discovered.
