<!-- D-08 supplemental archive record (supplement 03). Archived 2026-10-24 by the executor under D-08. Group T — owner prompts in transcript b258b225 after line 14909. -->
<!-- Source: Cursor conversation transcript, owner message (transcript line 16420). Message timestamp as recorded in the conversation: Friday, Oct 16, 2026, 7:04 PM (UTC+3). -->
<!-- The text below the separator is verbatim except values replaced by [REDACTED]. Redactions in this file: 0. The identical prompt was also recorded at transcript line(s) 16421, 16428; it is archived once. -->

---
# WSA-Enterprise — Engineering Execution Mandate
## Implement, Integrate, Test, and Activate the 17 Eligible Scientific Sources
### 1. Role: Senior Software Engineer and Project Execution Owner
Act as the senior software engineer, lead project engineer, software architect, integration engineer, and test engineer responsible for completing the current scientific-source expansion milestone in the actual WSA-Enterprise repository.
Repository: `wsa-platform/WSA-Enterprise`
Expected branch: `phase-18-m18-ai-marketing-communications`
Previously reported HEAD: `f2025315d8990f52f3c658c968a3ec8252ac18b0`
Previously reported local path: `G:\WSA-Enterprise`
Verify the actual repository, branch, HEAD, working tree, and current files before relying on these values.
**This is an implementation and completion mandate, not a planning-only exercise.** Inspect the code, make the required changes, finish the source integrations, implement the necessary contracts and adapters, execute permitted tests, correct defects, and document the actual result.
Use broad engineering discretion to complete work within the scope below. Do not stop at a report when an authorized implementation can be completed safely.
### 2. Explicit authorization
The project owner authorizes you to:
1. Complete the design and implementation required for the current source-expansion milestone.
2. Implement and integrate the 17 sources already identified as eligible for the next gate.
3. Activate those sources in the application when their identity, authorization, license conditions, endpoint behavior, and required technical checks have been verified.
4. Modify the necessary source registry, capability contracts, adapters, query-building logic, orchestration, normalization, configuration, documentation, and tests.
5. Add or update regression tests and execute all permitted relevant tests.
6. Fix defects discovered in the authorized scope.
7. Update the current architecture and engineering documentation to reflect the implementation actually completed.
8. Complete the necessary integration work so that eligible activated sources can participate in the existing scientific-search workflow.
9. Use engineering judgment to resolve ordinary implementation details without repeatedly asking the owner for routine approvals.
Do not interpret this authorization as permission to activate a source whose terms prohibit the intended access or storage, bypass authentication, ignore a provider's restrictions, or fabricate evidence of permission.
A source may be activated only after the actual conditions applicable to that source have been verified. If one source is blocked by a missing credential, unresolved license, unapproved endpoint, or external permission, continue with the other eligible sources and report the specific blocker for that source.
### 3. Establish the actual post-R8 baseline
Use the actual repository state after R8, not old reports as a substitute for inspection.
Previously reported baseline:
- Current membership register: `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json`
- Current membership count: 110
- Canonical identity registry: Registry v3
- Historical provenance snapshot: `ADR-023-LEGACY-62-PROVENANCE-SNAPSHOT.v1.json`
- Wiley canonical seat: `G2-26`
- Historical register `SCIENCE-SOURCE-EXPANSION-62-REGISTER.md`: reported deleted
- Previously reported source activation state: all INACTIVE before this new authorization
- No commit or push was performed at the previous stopping point
Verify the real filenames, membership counts, registry contents, activation states, and dependencies. The current membership registry and canonical identity registry have different responsibilities; preserve that separation.
Do not recreate the deleted historical register. Preserve the approved provenance mechanism.
Before changing files, record the Git state and identify existing user modifications. Never overwrite or discard unrelated user changes.
### 4. Identify the exact 17 eligible seats
Retrieve the authoritative list of the 17 seats from the relevant approved report and current registry.
Do not guess their identifiers, names, or endpoints. Do not substitute an arbitrary set of 17 sources.
Create a working inventory with:
- canonical seat ID;
- canonical source name;
- provider or publisher;
- official source identity evidence;
- approved access method;
- endpoint;
- license and usage conditions;
- existing adapter and implementation status;
- missing implementation work;
- verification status;
- intended activation status;
- final test evidence.
If the reported list cannot be located, inspect the architecture and repository records to establish the authoritative list. If multiple conflicting lists exist, resolve the discrepancy from the approved records rather than selecting one silently.
### 5. Complete the software design
Inspect the actual implementation and complete the missing design and contracts needed for these 17 sources.
Relevant components may include:
- `ScientificSearchQueryBuilder`
- `AgriculturalEntityCatalog`
- `ScientificQueryCompiler`
- `MultiSourceScientificSearchOrchestrator`
- source registry and canonical identity registry
- capability contracts and projection constraints
- source adapters
- `ScientificResultNormalizer`
- source-specific query builders
- persistence and caching components where already part of the approved design
- configuration and feature flags
- test fixtures and integration tests
Inspect actual class names, namespaces, interfaces, and dependencies before editing.
Preserve these architectural principles:
1. The Canonical Scientific Question (CSQ) remains the semantic authority.
2. Do not reduce the scientific question to generic keywords alone.
3. Each source adapter must translate the scientific intent into a source-appropriate query.
4. Capabilities must accurately describe the operations actually supported by the source.
5. Source identity, access capability, license, endpoint readiness, and activation are distinct concepts.
6. Results must preserve source identity, provenance, metadata, and evidence relationships.
7. Confidence and evidence-sufficiency rules must remain consistent with the approved synthesis and final-answer policies.
8. Unsupported operations must be explicitly represented rather than silently treated as supported.
9. Failure of one source must not incorrectly cause all other sources to fail.
10. Avoid unnecessary redesign of components that already satisfy the approved architecture.
Do not introduce a new parallel registry, competing activation mechanism, or duplicate query architecture.
### 6. Implement and integrate the 17 sources
For each eligible source:
1. Verify its canonical identity.
2. Verify its approved access method and applicable license conditions.
3. Implement the required adapter or complete the existing adapter.
4. Implement source-specific query translation where needed.
5. Map the source's supported capabilities accurately.
6. Integrate the adapter into the approved registry and orchestrator.
7. Implement pagination, timeouts, retries, rate limiting, and error handling where appropriate and supported.
8. Normalize returned results without losing provenance or scientific identity.
9. Prevent duplicate records from being misrepresented as independent evidence.
10. Add unit tests and integration tests.
11. Execute a minimal authorized endpoint verification.
12. Record the actual response behavior and any provider-imposed limitations.
13. Activate the source through the existing approved activation mechanism once its required checks pass.
14. Verify that the source is actually eligible for orchestration and that its results can reach the established synthesis path.
Do not mark a source ACTIVE merely because its adapter class exists or because an endpoint returned HTTP 200.
Activation requires evidence that the integration is functional and the access is permitted.
For sources that require credentials, use the repository's approved secret-management or environment-configuration mechanism. Never hardcode secrets, print credentials, or place secrets in tracked files.
For a blocked source, complete all work that does not depend on the missing prerequisite, keep it inactive, and record the exact blocker.
### 7. Query quality and scientific correctness
The objective is not simply to make 17 endpoints respond. The sources must contribute useful scientific evidence to the existing research workflow.
For each source, verify:
- the scientific intent is preserved in query translation;
- relevant entity identifiers and synonyms are handled appropriately;
- the query uses the source's actual search semantics;
- the result metadata is mapped correctly;
- source identity and publication metadata are preserved;
- source relevance and evidence relationships are not fabricated;
- unsupported query facets are not silently claimed to be enforced;
- errors and empty results are distinguished;
- the system can report when a source was unavailable or not queried.
Do not invent search results, publications, licenses, identifiers, endpoint behavior, or relevance scores.
### 8. Resolve implementation blockers within scope
Investigate the previously reported issues involving:
- the 16 ambiguous source identities;
- license and usage restrictions;
- the RSS/Atom decision;
- source-specific capabilities;
- registry and manifest consistency;
- the current scientific-query and entity-catalog design.
Use existing approved decisions first.
Resolve straightforward issues using authoritative evidence and established project rules. If an issue requires a genuine owner or legal decision, document it precisely and continue with all unaffected sources.
Do not make up an architectural approval or a license grant. Do not let an unrelated unresolved case block implementation of a source whose requirements are independently satisfied.
RSS/Atom may be implemented only where the source supports it and the project policy permits its use. Do not make RSS/Atom a universal substitute for source-specific scientific search.
### 9. Build a repeatable evaluation baseline
Before declaring the 17-source milestone complete, run the existing scientific-search evaluation suite if one exists.
If it does not exist or is inadequate, implement the minimum repeatable evaluation harness necessary for this milestone.
Measure, where applicable:
- source query-building time;
- endpoint latency;
- orchestration latency;
- normalization time;
- end-to-end search latency;
- source availability and failure rate;
- duplicate-result rate;
- source identity correctness;
- relevance and retrieval quality against a documented reference set.
Use established project metrics and acceptance thresholds where available.
Do not invent evaluation outcomes. Clearly separate live endpoint measurements from mocked or offline tests.
Avoid external load testing. Keep endpoint verification bounded and respectful of provider limits.
### 10. Testing and engineering quality
Execute relevant tests in layers:
1. Static validation and formatting checks available in the repository.
2. Source-specific adapter unit tests.
3. Query-building and capability-contract tests.
4. Registry and activation tests.
5. Normalization and provenance tests.
6. Orchestration and failure-isolation tests.
7. Scientific-search integration tests.
8. Existing regression suites relevant to the changed components.
9. Authorized endpoint verification.
10. Final consistency checks on activation states and canonical identities.
Run the tests actually supported by the environment. If Docker, PHP, or another required service is unavailable, investigate whether an existing supported alternative is available. Do not install dependencies or alter lockfiles without authorization.
Never claim a test passed unless it was executed and passed. Record command, exit status, test count, assertions, and environmental limitations where available.
Fix failures caused by your changes. Distinguish those from unrelated pre-existing failures.
### 11. Activation and runtime safety
The owner explicitly authorizes activation of the 17 eligible sources, but only after source-specific prerequisites are satisfied.
Use the existing activation mechanism and configuration conventions.
Requirements:
- Activate only the 17 identified eligible seats that pass all required checks.
- Keep any blocked source inactive.
- Do not activate any other source as a side effect.
- Do not activate a source solely because its endpoint is public.
- Do not bypass authentication, licenses, provider rate limits, or access controls.
- Do not expose internal or production credentials.
- Do not perform production deployment, production database migrations, or changes to live infrastructure.
- Verify the final activation state from the actual registry/configuration after the changes.
If the project requires deployment for an activation change to take effect, implement and test the code and configuration locally, document the deployment requirement, and do not deploy to production without separate authorization.
### 12. Git and scope restrictions
Broad implementation authority applies within this source-expansion milestone, subject to these mandatory restrictions:
- Do NOT run `git add`, `git commit`, `git push`, `git pull`, `git reset`, `git rebase`, or switch branches.
- Do not stage changes.
- Do not overwrite, revert, or discard existing user modifications.
- Do not delete unrelated files or data.
- Do not run production migrations or connect to production systems.
- Do not install dependencies or change lockfiles without authorization.
- Do not expand the work into unrelated project phases.
- Do not silently modify approved architecture decisions.
- Keep all changes reviewable in the working tree.
### 13. Required final deliverables
Produce one comprehensive engineering report in Arabic, backed by actual repository state, including:
1. Initial and final branch, HEAD, and Git status.
2. The exact 17 seats and their implementation outcomes.
3. Files created and modified.
4. Adapters and source-specific query implementations completed.
5. Registry and capability-contract changes.
6. Activation status of each of the 17 sources.
7. Evidence of permitted endpoint tests.
8. Licenses and access limitations.
9. Tests executed, commands, exit statuses, and results.
10. Retrieval-quality and latency baseline.
11. Any blocked sources and the exact missing prerequisite.
12. Any remaining owner decisions.
13. Confirmation that no commit or push occurred.
Distinguish implemented code, passing tests, live endpoint verification, and actual runtime activation. These are separate completion claims.
### 14. Start execution now
Inspect the current repository and the authoritative list of 17 eligible seats.
Then implement the source-specific integration, close the necessary software-design and code gaps, execute the permitted tests, and activate each eligible source that passes its required checks.
Do not return only a plan. Execute the authorized engineering work and report the verified outcome.
The target is a functional, tested, maintainable integration of the 17 eligible scientific sources into the existing WSA-Enterprise research workflow, with activation performed only where the actual prerequisites have been satisfied.
Do not commit or push.
