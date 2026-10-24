<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — executor final message for an owner prompt after line 14909. -->
<!-- Source: Cursor conversation transcript, executor final message for this prompt (transcript line 15463). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. Selection rule: last executor message of at least 300 characters after the last copy of the prompt and before the next distinct owner prompt or system notification. -->

---
I implemented R1 and verified it with isolated Docker test runs. It introduces no regressions in the Unit or Feature suites, and §8.34 is appended to ADR-023. Nothing was committed, staged, deleted, or sent over the network, and the legacy 62-entry register is kept as you decided. P1-EXIT-06 is not closed: R1 is only its first increment.

## What R1 changed

- **One source list:** a new typed catalog under `Search/Registry/` now holds the five existing Stage 3 sources (OpenAlex, Crossref, Semantic Scholar, Consensus, FAOSTAT). The registry, selector, orchestrator, identity bridge and Path-family mapping all read from it instead of six separate hard-coded lists.
  - Registering a 109-register ID (`adr_id`) is rejected until an Onboarding Contract exists.
  - The old constants are kept as deprecated copies, and drift tests check that they still match the catalog.
- **Adapter contract version:** `ScientificSourceAdapterInterface` now declares `scientific_source_adapter.v1`. No methods changed.
- **Failure isolation:** if an adapter throws on the sequential path, it now becomes one failed outcome instead of stopping the search for every other provider. Only the exception class is logged, not the message. The concurrent path already worked this way.
- **Tests:** the 33 new test cases all pass.

## Test results

The baseline ran on a copy of `backend/` taken before the changes, and each test was compared individually.

| Suite | Baseline | After R1 |
|---|---|---|
| Unit | 1,243 tests; 13 failures, 16 errors | 1,276 tests; same 13 failures and 16 errors; 0 regressions |
| Feature | 950 tests; 84 failures, 12 skipped | Identical, including the failure messages |

- **Already-failing tests:** the 29 Unit cases and 84 Feature cases fail the same way before and after R1. I did not investigate them.
- **One failure message changed:** `FaoStatPhase3FixTest::test_openalex_agri_domain_does_not_reach_faostat_and_qcl_is_used` fails for the same reason as before: FAOSTAT returns element `2413` instead of `2510`. Because the new isolation catches the assertion inside the HTTP fake, the test now reports a later assertion instead.
- **B-1 contract tests:** these were recorded as NOT RUN in §8.33, but they ran as part of the Unit suite. `ProjectionQueryConstraintsTest` passed all 25 cases, although §8.33 states 23; I did not look into the difference.

## Behavior differences recorded in the ADR

- **Config parsing:** the orchestrator now reads enablement flags the same way the selector does. Production config produces real booleans, so nothing changes there. Only a runtime override with a string such as `'false'` is now treated as disabled.
- **Test signals:** the failure isolation also catches test-framework exceptions on the sequential path, such as an assertion inside an `Http::fake` callback or a blocked stray request. Those now become a failed outcome instead of failing the test immediately. No test went from failing to passing because of this. Whether to re-throw these exceptions is recorded as an open question for you.

## Safety checks

- `git status` matches the pre-work state except for the 19 R1 files.
- The other 1,204 backend files are byte-identical to the snapshot.
- The hashes of `composer.lock`, `phpunit.xml` and `.env` are unchanged.
- ADR-023 was only appended to; its earlier content still hashes to `d3771aa0…`.

## Deliverables 1–17

| Deliverable | Status |
|---|---|
| 1–3: forensic baseline, execution-path map, gap matrix | Done (§8.34.2). GAP-SEM and GAP-ISO are only partly evidenced; GAP-EVID was not re-verified |
| 4–5: legacy-62 dependency matrix, deletion manifest | Done; manifest empty (§8.34.3) |
| 6: registry | Done for the five Stage 3 sources; not for the 109 seats |
| 7: adapter contract | v1 named only; a v2 with error categories and retry information is not done |
| 8: capability | Not done; capability is still not enforced during source selection |
| 9: query contract | Kept as is per your D-04 decision; handoff goes through the Builder and B-2 |
| 10: provenance and error contract | Error handling partly addressed; licence and provenance fields not done |
| 11: 109 matrix | Register unchanged; integration of all 109 sources NOT EXECUTED; none bound |
| 12: migration plan | Remaining work is listed in the §8.34.8 status matrix |
| 13–16: conformance tests, regression results, diff summary, ADR update | Done (§8.34.5, §8.34.6) |
| 17: final report | This summary |

The next increments each need their own Gate A and Gate B: capability enforcement in selection, adapter contract v2 with error categories and retry information, provenance and licence fields, reusable adapters for source families such as OAI-PMH and RSS, and registering sources from the 109 register after Onboarding Contracts are accepted. Phase 1 remains blocked and Phase 2 has not started.

The ADR status line was already modified before, so it's fine that it falls outside the 19 diff paths. Now I can put together the final report, mapping deliverables 1 through 17 concisely while flagging any incomplete items.
