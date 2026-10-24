<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16399). Message timestamp as recorded in the conversation: Friday, Oct 16, 2026, 2:24 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16400; it is archived once. -->

---
# WSA-Enterprise — FINAL R8 CLOSURE MANDATE
## Historical 62-Entry Register Reconciliation, Canonical Registry Completion, and Legacy Register Deletion
### 1. Your role and authority
Act as the **Senior Project Engineer, Software Architect, Repository Engineer, Scientific-Source Identity Specialist, and Final Closure Owner** for the WSA-Enterprise project.
You are responsible for carrying this work through repository inspection, evidence-based identity resolution, implementation, testing, documentation, and final closure.
Do not stop after producing an analysis or proposed plan. Execute all authorized work that can be completed safely in the actual repository. Report only verified results.
Repository:
- GitHub organization: `wsa-platform`
- Repository: `WSA-Enterprise`
- Expected branch: `phase-18-m18-ai-marketing-communications`
- Last reported HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`
- Previously reported local path: `G:\WSA-Enterprise`
Verify the real branch, HEAD, working tree, and files before relying on these values.
### 2. Final business and architectural objective
The historical register:
`SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`
must no longer remain as a competing operational or authoritative source register.
The final desired state is:
1. All 62 historical entries have a documented and defensible final disposition.
2. Every historical entry that represents an existing canonical source is linked to the correct canonical identity.
3. Every genuinely distinct scientific source is handled through the approved canonical membership architecture; it must not be falsely merged or silently discarded.
4. Historical identifiers and required provenance are retained in the authoritative registry or its approved provenance mechanism.
5. The approved CURRENT membership registry remains the single operational source of truth.
6. No source is activated merely because its identity has been resolved.
7. The old 62-entry register is deleted from the repository after all required information has been safely transferred and all deletion prerequisites have passed.
8. No competing legacy registry, stale runtime dependency, or obsolete reference remains.
9. All permitted relevant tests pass, or every inability to test is explicitly reported.
10. The work is fully documented, with no Git commit or push.
**The goal is not to preserve two parallel registries indefinitely. The goal is one authoritative operational registry, with traceable provenance and no loss of valid scientific-source identities.**
### 3. Resume from the latest R8 state
Do not repeat the entire earlier forensic audit without a concrete reason. Start from the actual current repository state and the latest R8 engineering artifacts.
The previous Cursor report claimed:
- Stage A independent verification completed.
- The 62 historical entries were classified as:
  - 60 `LINKED_TO_CANONICAL_CURRENT`
  - 1 `CONFLICT_REQUIRES_REVIEW` (PM-02)
  - 1 `HISTORICAL_ONLY_DISTINCT` (legacy #20, Wiley)
- PM-08, PM-22, and PM-26 were resolved as `SAME_SOURCE_CONFIRMED`.
- The legacy #20 Wiley source was considered distinct but did not yet have a CURRENT membership.
- The canonical identity registry on disk was still v2 with 45 historical links.
- Implementation for 60 links reportedly existed in code, but Registry/Manifest v3 export was blocked.
- Docker was unavailable, and PHPUnit was not rerun in that session.
- The legacy register had not been deleted.
- ADR-023 §8.44 and R8 final accounting artifacts were reportedly updated.
- The latest reported HEAD remained `f2025315d8990f52f3c658c968a3ec8252ac18b0`.
These are reported claims, not guaranteed current facts. Verify them.
Expected report and artifact locations include:
- `docs/architecture/ADR-023-scientific-source-expansion.md`
- `docs/architecture/science-source-expansion/2026-10-16-R8-FINAL-RESOLUTION-ENGINEERING-REPORT.md`
- `docs/architecture/science-source-expansion/2026-10-16-R8-LEGACY-62-FINAL-ACCOUNTING.v2.md`
- the associated accounting JSON
- Stage A independent-verification report
- the R8 evidence-review artifacts
- `R8FinalIdentityLinks` and its associated tests
- the canonical membership register, registry, and manifest artifacts
- `SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`
Locate the real files and use the actual repository schema and naming conventions.
### 4. Owner direction: AgroSpace PM-02 means Belgrade
The owner has explicitly clarified that the intended historical source for PM-02 is:
**AgroSpace — Belgrade.**
This clarification is binding for the investigation. Do not substitute the Chilean `agrospace.cl` source for the intended Belgrade source merely because their names are similar.
Investigate the exact historical entry `#3` and proposed CURRENT identity `G1-04`.
Determine whether `G1-04` represents the Belgrade AgroSpace source, a different source, or an incorrectly described entry.
Use authoritative identity evidence and the project's existing identity rules. If `G1-04` is the Belgrade source, link the historical identifier to it and preserve provenance. If it is not, correct the identity mapping through the approved architecture and handle the Belgrade source as a distinct canonical source when justified.
Do not declare PM-02 unresolved merely because the Chilean website differs from the Belgrade source. First investigate the owner's explicitly identified source.
Do not fabricate an official identifier, publisher, URL, or identity relationship.
### 5. Resolve the remaining membership issue: legacy #20 — Wiley
Investigate historical entry `#20`, reported as Wiley with ISSN `2693-8847`.
Determine what exact journal, publication, platform, or other scientific resource this entry represents. Do not assume that a publisher name alone identifies a scientific source.
Compare its identity against the existing 109 CURRENT entries and their metadata.
Then:
- If it represents an existing CURRENT source, link it to the correct canonical identity.
- If it is a genuinely distinct scientific source and is eligible under the approved architecture, implement the required canonical membership using the project's existing schema, preserving the historical identifier and provenance.
- If membership requires an architectural or owner decision that has not been authorized, document the exact issue and complete all unrelated authorized work. Do not invent an approval.
Do not delete the historical #20 information or force it into an unrelated source just to achieve a count of 62 linked entries.
### 6. Complete and verify all 62 historical entries
Review the final accounting for all 62 entries.
For every entry, verify:
- unique historical identifier;
- correct historical name and identity;
- evidence-supported disposition;
- correct canonical target, when applicable;
- preservation of source provenance and identifiers;
- absence of duplicate or conflicting canonical mappings;
- absence of silent omissions;
- consistency between the accounting report, registry, manifest, code, tests, and ADR-023.
Every entry must be accounted for exactly once.
Permitted final dispositions include:
- linked to a confirmed existing canonical source;
- represented as a confirmed distinct canonical source through the approved membership process;
- explicitly unresolved only when genuine identity ambiguity or a required decision remains.
The goal is to resolve all entries, but scientific identity must not be guessed. An unresolved entry is a blocker to destructive deletion only when deleting the legacy register would lose information, prevent auditability, or violate an approved architectural decision.
Do not confuse “no match identified” with proof that a source is independent.
### 7. Complete canonical Registry and Manifest export
The previous report stated that the code was ready for 60 links while the on-disk canonical identity registry still contained only 45.
Resolve this discrepancy.
1. Inspect the actual registry schema and export workflow.
2. Implement or repair the Registry/Manifest v3 generation using the approved canonical identity model.
3. Ensure all confirmed historical-to-canonical links are represented in the generated artifacts.
4. Ensure the artifacts agree on counts, identifiers, canonical targets, provenance, and versions.
5. Ensure the CURRENT membership register and canonical identity registry retain their intended separate responsibilities.
6. Do not modify source activation state.
7. Add or update tests for identity resolution, historical identifier preservation, canonical membership, consistency, and regression prevention.
8. Validate generated output against the actual schema and repository conventions.
Do not claim the 60 links are operationally complete until the generated registry and manifest actually contain them and validation confirms consistency.
### 8. Delete the historical register after safe migration
The owner explicitly wants the old 62-entry register deleted after reconciliation so that it cannot create future duplication or conflicting authority.
Treat deletion as a required final deliverable, not as an indefinitely deferred recommendation.
Before deletion:
1. Confirm all 62 entries have been accounted for.
2. Confirm every confirmed identity link is present in the authoritative registry or approved provenance store.
3. Confirm all distinct sources have been handled correctly.
4. Confirm required historical identifiers and provenance will survive deletion.
5. Identify all code, tests, documentation, scripts, fixtures, and tools that reference the old register.
6. Confirm no operational consumer depends on the old register.
7. Update those consumers to use the canonical authority where appropriate.
8. Confirm ADR-023 and the applicable approved decisions permit retirement.
9. Resolve or explicitly handle any retention obligation, including the previously reported D-09 requirement.
10. Run the permitted integrity checks and tests.
If all prerequisites pass, delete:
`SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`
Then remove or update obsolete references so the deleted file is not required by any build, test, script, or runtime workflow.
Preserve the final accounting, evidence references, and necessary audit provenance in the approved location. The objective is to delete the competing legacy register, not to erase the scientific history or provenance.
**Do not retain the old register indefinitely merely because Docker is unavailable.** First determine which checks can be performed without Docker, execute those checks, and identify the exact checks that genuinely require Docker. If a mandatory test or an approved retention rule remains a real blocker, do not falsely report closure or perform an unsafe deletion. Continue all other authorized work and provide a precise, actionable blocker report.
### 9. Engineering execution requirements
Act as the project engineer responsible for completion:
- Inspect before modifying.
- Establish a baseline of the Git state and existing user changes.
- Execute evidence-backed identity resolution.
- Implement the required changes in the actual repository.
- Update the canonical artifacts and ADR documentation.
- Add or adjust regression tests.
- Run all relevant permitted tests available in the environment.
- Validate counts, schema, identifiers, references, and provenance.
- Search the repository for stale references after retirement.
- Verify the final Git diff and status.
- Produce one comprehensive final engineering report in Arabic.
Do not respond with a plan alone. Do not ask the owner to repeat information already provided.
Do not restart completed stages without a specific evidence-based reason.
### 10. Safety, Git, and scope restrictions
The following restrictions are mandatory:
- Do NOT run `git add`, `git commit`, `git push`, `git pull`, `git reset`, `git rebase`, or switch branches.
- Do not stage changes.
- Do not overwrite, revert, or discard existing user changes.
- Do not delete unrelated files or data.
- Do not deploy or execute production migrations.
- Do not connect to production systems.
- Do not activate scientific sources.
- Do not expose credentials, tokens, or secrets.
- Do not install dependencies or alter lockfiles without separate authorization.
- Do not expand into Phase 2 or Phase 3 or change unrelated scientific-search architecture.
- Docker may only be used for isolated local testing where available; never connect to production or expose unnecessary ports.
- Respect the actual environment's PHP and test-runner constraints. Report test failures and unavailable infrastructure honestly.
### 11. Final acceptance criteria
Declare R8 FINAL-CLOSED only when all applicable conditions are verified:
- All 62 historical entries are accounted for.
- PM-02 has been investigated using the owner's Belgrade clarification.
- PM-08, PM-22, and PM-26 have their evidence-backed outcomes preserved.
- Legacy #20 has a defensible canonical disposition.
- No historical source has been silently lost or incorrectly merged.
- All confirmed historical links are present in the canonical identity registry.
- Registry and Manifest versions are generated and internally consistent.
- The approved CURRENT membership registry is the single operational authority.
- Historical identifiers and required provenance remain traceable.
- No source activation state has changed.
- The old 62-entry register has been deleted, if and only if all deletion prerequisites have passed.
- All relevant executable tests pass, or any mandatory blocker is explicitly identified and R8 is honestly reported as not yet closed.
- Repository-wide references and the final diff have been reviewed.
- No commit or push has occurred.
### 12. Final report required
Write one comprehensive report in Arabic containing:
1. Verified initial and final branch, HEAD, and Git status.
2. All 62 entries and their final dispositions, in a report or artifact referenced by the report.
3. The evidence-based outcome for PM-02, PM-08, PM-22, PM-26, and legacy #20.
4. Final counts for linked entries, confirmed distinct sources, and any unresolved entries.
5. The canonical membership register, identity registry, manifest, and provenance mechanism used.
6. Exact files created, modified, and deleted.
7. Test commands, actual exit codes, test counts, and assertion counts when available.
8. Confirmation that source activation state was preserved.
9. Confirmation that the old register was deleted, or the exact mandatory blocker preventing deletion.
10. Any remaining decision requiring owner authorization.
Separate verified facts from assumptions and unresolved matters.
Do not claim full closure if a required acceptance criterion is unmet.
### 13. Begin execution now
Start by inspecting the real repository and verifying the latest R8 state. Continue from the existing implementation rather than repeating the completed forensic work.
Resolve PM-02 using the explicit Belgrade clarification, resolve the Wiley membership issue, complete the canonical Registry/Manifest export, validate all 62 entries, update the architecture and tests, and retire the old register once all safety and architectural prerequisites pass.
Your mission is to deliver one reliable operational registry, eliminate the old competing 62-entry register safely, preserve necessary provenance, and finish the authorized work without committing or pushing.
