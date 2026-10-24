<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16373). Message timestamp as recorded in the conversation: Friday, Oct 16, 2026, 1:57 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16374; it is archived once. -->

---
# WSA-Enterprise — R8 Final Historical Source Resolution, Canonicalization & Legacy Register Retirement
## 1. Role and execution mandate
Act as a senior software architect, scientific-source identity specialist, data-integrity engineer, and repository-level implementation engineer.
Repository: `wsa-platform/WSA-Enterprise`
Branch expected from the last reported state: `phase-18-m18-ai-marketing-communications`
Current reported HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`
Local path previously reported: `G:\WSA-Enterprise`
These Git details are historical claims and MUST be verified from the actual repository before execution.
The objective is to finish the R8 historical-source reconciliation for all 62 legacy entries, establish one authoritative operational source registry, and then retire and delete the old 62-entry register only after every required identity, provenance, reference, and integrity condition has passed.
This is an execution task, not a request for a plan alone. Inspect the actual repository, execute authorized work, run permitted tests, and report actual results. Do not invent files, test results, approvals, identities, or successful operations.
## 2. Resume from the exact saved stopping point
The latest reported state is that R8 consolidation has already been implemented, but an independent post-consolidation verification has NOT yet been completed.
Reported R8 outputs include:
- `docs/architecture/ADR-023-109-MEMBERSHIP-REGISTER.v1.json`
- `docs/architecture/science-source-expansion/2026-10-16-R8-POSSIBLE-MATCH-EVIDENCE-REVIEW.v1.json`
- `docs/architecture/science-source-expansion/2026-10-16-R8-AUTHORIZED-CONSOLIDATION-ENGINEERING-REPORT.md`
- `docs/architecture/science-source-expansion/2026-10-16-R8-LEGACY-62-FINAL-ACCOUNTING.v1.json`
- `docs/architecture/science-source-expansion/2026-10-16-R8-LEGACY-62-FINAL-ACCOUNTING.md`
- R8 manifest and registry artifacts reported as `MANIFEST.v2.json` and `REGISTRY.v2.json`
- `docs/architecture/ADR-023-scientific-source-expansion.md`, with §8.43 reportedly appended
- `Membership/Consolidation/*` and `SourceRegistryConsolidationTest.php`
These are expected locations only. Locate and verify the actual files and their contents. Do not assume names, versions, hashes, or paths are correct merely because they were previously reported.
The previous report claimed:
- 62 historical entries accounted for as 45 linked, 3 unresolved possible matches, 1 conflict, and 13 with no identified match.
- 45 historical-to-current identity links were implemented.
- PM-27 (`legacy #35 APIS`) was explicitly approved by the owner and linked to `G3-01`.
- Four cases remained unresolved:
  - PM-02: legacy #3 ↔ G1-04, AgroSpace — conflicting evidence.
  - PM-08: legacy #12 ↔ G2-03, European Poultry Science — owner decision required.
  - PM-22: legacy #29 ↔ G2-20, Aquaculture Journal — insufficient evidence.
  - PM-26: legacy #33 ↔ G2-24, IDEAL Project — owner decision required.
- The CURRENT membership registry contained 109 entries, reportedly all INACTIVE.
- The old 62-entry register and the 109-entry CURRENT register had previously been preserved separately.
- A test reportedly passed: `SourceRegistryConsolidationTest.php`, 16 tests / 256 assertions.
Treat all of these as claims to verify, not as independently established facts.
## 3. Non-negotiable architecture and scientific identity rules
1. Scientific-source identity MUST be established using authoritative evidence, not name similarity alone.
2. Preserve the distinction between a source's identity, its historical name, its URL, its provider/platform, its dataset or archive, and its access method.
3. Preserve existing canonical CURRENT identities when evidence establishes that the historical entry refers to the same source.
4. Do not force an unresolved or distinct source into an unrelated CURRENT identity merely to achieve a complete mapping.
5. If a historical entry is proven to be a distinct valid scientific source that is absent from the CURRENT registry, propose and implement a properly reviewed canonical membership entry only when permitted by the existing architecture and supported by evidence. Do not silently change the approved membership count or fabricate a source.
6. Maintain traceable historical identifiers and provenance in the authoritative registry or its approved provenance mechanism.
7. `NO_MATCH_IDENTIFIED` means no match was identified; it does NOT prove that the entry is an independent source.
8. A URL by itself is not sufficient proof of source identity.
9. Keep activation and identity resolution separate. Identity reconciliation MUST NOT activate any source.
10. Do not change the scientific-query contract, CSQ semantics, source adapters, query compilation, or unrelated search architecture as part of this task.
11. Follow ADR-023 and all applicable approved ADRs. If this task conflicts with an approved decision, document the conflict and stop before the conflicting action.
## 4. Stage A — Read-only independent verification
Begin with read-only inspection. Do not modify any file during this stage.
A. Verify Git branch, HEAD, working-tree state, staged changes, untracked files, and any existing user changes. Record a baseline. Never overwrite, revert, stage, or discard user changes.
B. Read the actual ADR-023 sections relevant to:
- the approved 109-member CURRENT register;
- historical-source reconciliation;
- R8 authorization and consolidation;
- §8.43 and any subsequent sections;
- source identity, provenance, registry membership, activation, and retirement.
C. Inspect the actual R8 manifest, registry, historical accounting, evidence review, engineering report, consolidation implementation, and tests.
D. Independently verify all of the following:
- Exactly 62 unique legacy entries are accounted for, with no omissions or duplicate accounting.
- The accounting totals are internally consistent.
- Every one of the 45 reported historical links resolves to the correct canonical identity.
- PM-27's explicitly approved mapping is preserved.
- PM-02, PM-08, PM-22, and PM-26 have not been silently merged or otherwise altered without supporting evidence and authorization.
- The 13 `NO_MATCH_IDENTIFIED` entries have not been treated as proven independent sources or silently discarded.
- The 109 CURRENT memberships and their identity fields remain consistent with the approved registry.
- Every source remains in its existing activation state; do not activate any source.
- All registry, manifest, accounting, provenance, and ADR references are consistent.
- The hashes and versions reported by the previous engineering report match the actual files, where the report provides enough information to verify them.
- No runtime consumer, test, script, documentation reference, migration, or tool still relies on the legacy 62-entry register in an operational or authoritative capacity.
Use repository searches and tests as needed, but remain read-only during Stage A.
Deliver a concise but comprehensive Stage A findings report before proceeding to any destructive action.
## 5. Stage B — Resolve the four outstanding identity cases
Investigate each case individually using primary or authoritative evidence, including official publisher/provider information, persistent identifiers, authoritative catalogs, archived records when appropriate, and reliable historical metadata.
Cases:
- PM-02: `#3 ↔ G1-04`, AgroSpace.
- PM-08: `#12 ↔ G2-03`, European Poultry Science.
- PM-22: `#29 ↔ G2-20`, Aquaculture Journal.
- PM-26: `#33 ↔ G2-24`, IDEAL Project.
For each case:
1. Establish what the legacy entry actually represented at the relevant time.
2. Establish what the proposed CURRENT entry represents.
3. Compare official identity evidence, provider/publisher, scope, identifiers, history, and any archive-versus-live distinction.
4. Record evidence sources, access dates, and limitations.
5. Assign one explicit outcome:
   - `SAME_SOURCE_CONFIRMED`
   - `DISTINCT_SOURCE_CONFIRMED`
   - `UNRESOLVED_REQUIRES_OWNER_DECISION`
Do not use an outcome unless the evidence supports it.
The user authorizes the investigation and requests completion, but this does not authorize fabrication or an evidence-free identity decision. For any case that remains genuinely ambiguous, do not invent a resolution. Record the exact decision needed and explain why evidence cannot resolve it.
Do not delete the legacy register during this stage.
## 6. Stage C — Resolve all remaining legacy entries
Review every one of the 62 entries, not only the four named cases.
For each entry, create a complete accounting record with at least:
- legacy identifier;
- historical display name;
- evidence-backed identity description;
- canonical CURRENT identifier if a same-source relationship is established;
- disposition;
- evidence references;
- confidence or evidence-strength assessment using the existing project conventions;
- reviewer/decision provenance;
- explanation for unresolved cases or newly identified distinct sources.
Use the project's existing schema and naming conventions wherever possible. Do not invent a parallel schema if the current approved schema can represent the required information.
Every entry must finish in one of these defensible states:
1. Linked to a confirmed canonical CURRENT source.
2. Confirmed distinct source represented by an appropriately approved canonical membership.
3. Explicitly retained as unresolved with a documented reason and required decision.
No entry may be silently dropped.
Do not interpret the user's desire to finish all 62 entries as permission to guess source identities. If a final identity cannot be established from evidence, make the remaining blocker explicit.
## 7. Stage D — Establish a single authoritative operational registry
Before retiring the old register:
1. Determine which file or registry is the single authoritative operational source of truth according to the existing architecture.
2. Ensure all confirmed historical identifiers and provenance needed for future traceability are represented in that authority or in its explicitly approved provenance mechanism.
3. Ensure confirmed distinct sources are not lost.
4. Update only the necessary registry artifacts, reconciliation artifacts, ADR documentation, tests, and direct consumers.
5. Keep the approved CURRENT identities stable unless an evidence-backed and authorized membership change is necessary.
6. Do not silently force the membership count to remain 109 if the evidence establishes a genuinely distinct missing source. Document and obtain the required authorization for any membership change.
7. Preserve activation status; identity resolution does not authorize source activation.
8. Ensure runtime consumers do not read the historical register as a competing registry.
9. Add or update regression tests covering the final accounting, identity links, provenance, unresolved-case handling, and absence of operational dependencies on the retired register.
Run only tests that are relevant and permitted by the repository's established constraints. Report the actual command, exit status, test count, and assertion count when available. Do not claim a test passed unless it actually ran and passed.
## 8. Stage E — Retire and delete the 62-entry legacy register
The user explicitly wants the old 62-entry register deleted after reconciliation so it cannot become a competing source of truth in the future.
This destructive step is authorized ONLY after all of the following conditions are met:
- All 62 entries have been accounted for.
- No source identity or required provenance will be lost.
- All confirmed identities and historical identifiers are preserved in the authoritative registry or approved provenance store.
- Every confirmed distinct source has been handled correctly.
- There are no unresolved identity decisions that would make deletion lose necessary source information.
- No operational consumer depends on the old register.
- The final accounting and integrity tests pass.
- The replacement authority and audit trail are complete and consistent.
- The exact target file and all references to it have been identified.
When all conditions pass:
1. Remove the legacy 62-entry register from the repository.
2. Update direct references and documentation that incorrectly present it as an active or authoritative registry.
3. Preserve the final reconciliation/accounting report, evidence references, historical identifiers, and required audit provenance in the approved location.
4. Ensure the old register is not reintroduced as a competing operational source in scripts, tests, documentation, fixtures, or build steps.
5. Verify repository-wide references and run the permitted relevant tests again.
6. Confirm the approved CURRENT registry remains authoritative and that no source was accidentally activated or deleted.
Do not delete the only copy of evidence or provenance. The user wants the legacy REGISTER removed, not the scientific history erased.
If any prerequisite fails, STOP before deleting the register. Report the exact blocker and the safest next action. Do not ask for confirmation merely to avoid doing authorized non-destructive work; ask only when a genuine owner decision or architectural approval is required.
## 9. Git and operational safety constraints
- Do NOT run `git add`, `git commit`, `git push`, `git pull`, `git reset`, `git rebase`, or switch branches.
- Do not stage changes.
- Do not overwrite, revert, or discard existing user changes.
- Do not delete unrelated files or data.
- Do not run production migrations, connect to production systems, deploy, or activate sources.
- Do not expose secrets or credentials.
- Do not install dependencies or change lockfiles without separate authorization.
- Do not change unrelated application behavior or expand into Phase 2/Phase 3.
- Docker may be used only for an isolated local test environment where needed; no production connectivity and no unnecessary published ports.
- Respect the repository's documented PHP/test execution constraints. If a test cannot run in the available environment, report that limitation honestly.
## 10. Required final report
Produce one comprehensive report in Arabic, with a concise executive summary and evidence-based details.
Include:
1. Verified initial Git state and baseline.
2. Independent verification results for the existing R8 consolidation.
3. A table covering all 62 legacy entries and their final dispositions, or a referenced artifact containing that complete table.
4. Separate findings for PM-02, PM-08, PM-22, and PM-26.
5. The exact final canonical registry and provenance mechanism.
6. The number of confirmed links, confirmed distinct sources, and unresolved entries.
7. Whether the legacy 62-entry register was safely deleted. If not, explain the exact blocking condition.
8. Exact files added, modified, or deleted.
9. Tests actually run, commands, exit statuses, and results.
10. Git status after execution, including confirmation that no commit or push occurred.
11. Any remaining owner decisions or limitations.
Distinguish clearly between:
- verified facts;
- conclusions inferred from evidence;
- unresolved questions;
- actions completed;
- actions not performed.
Do not report success for an incomplete stage.
## 11. Start now
Start with Stage A, the independent read-only verification. Inspect the actual repository and report what is verified before modifying files.
Then continue through the authorized stages in order. Do not repeat the previous forensic audit unless the repository state or evidence reveals a concrete reason.
The final goal is one authoritative operational registry, complete and defensible reconciliation of the historical 62 entries, preservation of required provenance, and safe retirement and deletion of the old register without losing sources or changing activation status.
Stop before any Git commit or push.
