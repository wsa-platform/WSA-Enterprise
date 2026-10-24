<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 15522). Message timestamp as recorded in the conversation: Thursday, Oct 15, 2026, 12:10 AM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 15523; it is archived once. -->

---
Continue autonomously with R3 — Source Adapter Contract v2.
Decisions:
1. **B-1 Projection revision:** Keep it OPEN and UNAPPROVED. Investigate the exact changes made before R1, identify the two added test methods, verify their architectural rationale and compatibility, and document the evidence in ADR-023. Do not revert, accept, or modify those changes without sufficient evidence. You may continue R3 independently if there is no dependency.
2. **Live probes:** Not authorized yet. Do not perform network access, live source probes, or source activation. First complete and verify the adapter contract, typed error categories, retry policy, provenance/licensing requirements, and conformance tests.
For R3, continue from the actual repository state and ADR-023 §8.35. Implement the versioned adapter contract v2 incrementally, preserve v1 compatibility during migration, and ensure that only expected source failures are converted into failed source outcomes. Unexpected application errors, programming defects, and test-framework exceptions must propagate correctly in both sequential and concurrent execution paths.
Run only the previously authorized isolated Docker test protocol. Preserve all existing work, do not stage or commit, do not push, do not delete files, and do not access the network or database. Append the actual results to ADR-023 without changing historical content.
Do not stop for routine engineering decisions. After R3 passes its acceptance criteria, proceed to the next safe, authorized increment. Ask only when a genuine owner decision or additional authorization is required.
