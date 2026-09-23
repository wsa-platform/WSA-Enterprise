# ADR-004 — FAOSTAT as a Core Agricultural Knowledge & Data Provider

- **Status:** Accepted / Implemented
- **Date:** 2026-09-14
- **Scope:** WSA-Enterprise agricultural answer/research architecture
- **Decision Type:** Provider role, retrieval orchestration, evidence architecture
- **Related:** ADR-001 Provider Adapter Architecture; ADR-002 Universal Answer Orchestrator

> **CURRENT RUNTIME:** FAOSTAT **Developer Portal** (`FaoStatDeveloperPortalClient` → `https://faostatservices.fao.org/api/v1`) is the active FAOSTAT target. Portal was proven in Phase 3 (`0ea1c7e`).
> **FENIX** is historical/retired. No active FENIX adapter exists on the current branch. FENIX negative-proof tests and historical documentation remain intentionally preserved. Do not reintroduce FENIX as the live contract.

## 1. Decision

FAOSTAT is designated as a **Core Agricultural Knowledge & Data Provider** for WSA-Enterprise.

Every question submitted to WSA is treated as belonging to the platform's agricultural domain by definition. Therefore, WSA MUST NOT introduce an additional "is this an agricultural question?" gate before considering FAOSTAT.

For every incoming question, FAOSTAT MUST be considered as part of the agricultural evidence-retrieval process. FAOSTAT MUST NOT be the sole source by default. WSA MUST dynamically combine FAOSTAT with scientific literature, official sources, specialized agricultural sources, and other appropriate providers whenever they are needed to answer the question completely and accurately.

This decision intentionally does **not** define a closed list of agricultural subjects. FAOSTAT usage must be extensible across all agricultural fields and across additional FAOSTAT domains as those domains are discovered and technically verified.

## 2. Architectural Principle

The governing rule is:

> **FAOSTAT is always considered for every WSA question, but it is never forced to answer a claim that its actual data does not support.**

The provider-selection architecture MUST therefore distinguish between:

1. **Provider consideration** — FAOSTAT is considered for every WSA question.
2. **Domain discovery** — the system determines which FAOSTAT domain(s) are relevant and available.
3. **Evidence relevance** — returned FAOSTAT observations are evaluated for their actual relationship to the claims being answered.
4. **Evidence fusion** — FAOSTAT is combined with other providers when interpretation, mechanisms, recommendations, disease information, scientific explanation, or other evidence is required.

FAOSTAT MUST NOT be treated as a journal-literature provider.

## 3. No Closed Agricultural Subject List

WSA MUST NOT implement a hard-coded allowlist such as:

- crop production only
- livestock only
- irrigation only
- soil only
- fertilizers only
- plant diseases only
- agricultural economics only
- statistical questions only

Such lists would become an architectural limitation.

Instead, the system MUST use the FAOSTAT Developer Portal's domain discovery and verified domain/code metadata to determine what FAOSTAT can contribute to a particular request.

QCL (`Crops and livestock products`) is the first verified Developer Portal domain and is the initial implementation target, but QCL MUST NOT be treated as the permanent or exclusive FAOSTAT domain.

Additional domains MUST be added through the same discovery → verification → adapter support → contract test process.

## 4. Evidence Fusion

For every WSA question, the orchestrator may combine:

```text
FAOSTAT
   +
Scientific Literature
   +
Official / Institutional Sources
   +
Specialized Agricultural Sources
   +
Other Appropriate Providers
   ↓
Evidence Fusion
   ↓
Claim-Level Validation
   ↓
Final Answer
```

The presence of FAOSTAT MUST NOT suppress other sources when those sources are necessary to answer the question.

Likewise, the presence of scientific literature MUST NOT suppress FAOSTAT when official agricultural data can materially answer part of the question.

## 5. Evidence Classification

FAOSTAT evidence MUST have a distinct evidence type and MUST NOT be forced through journal-paper evidence rules.

Primary classification:

`DIRECT_STATISTICAL_EVIDENCE`

A FAOSTAT observation is direct evidence only for the measured data tuple it actually represents, such as:

- domain
- area
- item
- element
- year
- unit
- value
- flag

FAOSTAT evidence may be supporting evidence for closely related quantitative claims when the relationship is explicit and defensible.

FAOSTAT MUST NOT be classified as `LITERATURE_EVIDENCE`.

FAOSTAT data MUST NOT by itself be used to establish unsupported:

- causal mechanisms
- disease causes
- physiological mechanisms
- species identity
- genetic conclusions
- "best" breed/variety recommendations
- treatment recommendations
- agronomic recommendations that are not measured by the returned data

Example: a milk-production observation can directly support a milk-production quantity claim, but cannot by itself prove mastitis prevalence, genetics, physiology, or causation.

## 6. Relevance Rule

A successful FAOSTAT HTTP response is not automatically relevant evidence.

WSA MUST evaluate whether each returned observation supports the specific claim being answered.

The following are distinct states:

```text
Provider available
Provider queried
Data returned
Data relevant
Evidence sufficient
```

These states MUST NOT be collapsed into a single success flag.

An empty FAOSTAT result is a valid `EMPTY_RESULT` state, not a transport failure.

## 7. Developer Portal Contract Baseline

The implementation MUST target the current FAOSTAT Developer Portal contract, not the obsolete FENIX contract.

Verified baseline:

- Host: `https://faostatservices.fao.org/api/v1`
- Authentication: `POST /auth/login` using `application/x-www-form-urlencoded`
- Authenticated requests: `Authorization: Bearer <ACCESS_TOKEN>`
- Access token observed lifetime: `ExpiresIn=3600`
- Refresh token is returned, but no documented refresh endpoint was verified; the initial implementation must use safe re-login rather than an undocumented refresh mechanism.
- Domain discovery: `/{lang}/groupsanddomains`
- QCL verified: `domain_code=QCL`, `domain_name=Crops and livestock products`
- QCL dimensions: area, element, item, year
- Country codes: `/codes/countries/{domain}/`
- Region codes: `/codes/regions/{domain}/`
- Special-group codes: `/codes/specialgroups/{domain}/`
- Item codes: `/codes/items/{domain}/`
- Element codes: `/codes/elements/{domain}/`
- Year codes: `/codes/years/{domain}/`
- Data request: `/{lang}/data/{domain}?area=&item=&element=&year=`
- JSON is the preferred runtime transport.
- CSV is supported and is appropriate for bulk/offline use.
- `/codes/areas/{domain}/` is currently broken in verified QCL behavior and MUST NOT be used for area resolution.
- `/data` pagination is not documented or observed and MUST NOT be invented.

## 8. Live Schema Rule

Where the live API response conflicts with an OpenAPI field spelling, the adapter MUST normalize from the verified live response contract while retaining raw data when useful for auditability.

Verified live observation fields include:

- `Domain Code`
- `Domain`
- `Area Code`
- `Area`
- `Element Code`
- `Element`
- `Item Code`
- `Item`
- `Year Code`
- `Year`
- `Unit`
- `Value`
- `Flag`
- `Flag Description`
- `Note`

The adapter SHOULD normalize these into an internal stable DTO while preserving the raw observation.

## 9. Element Dual-Code Rule

The Developer Portal currently exhibits verified QCL behavior in which the query element code and returned observation element code can differ.

Examples verified live:

- query `2510` → response `5510` (`Production`)
- query `2413` → response `5412` (`Yield`)
- query `2312` → response `5312` (`Area harvested`)

WSA MUST:

- resolve request element codes from the live element code list
- retain `query_element_code`
- retain `response_element_code`
- NEVER implement an arithmetic `+3000` mapping formula
- NEVER query using a response code unless that response code is independently present in the live query code list

This behavior is verified for tested QCL measures but is not yet established as a universal FAOSTAT rule across all domains.

## 9.1 Phase 3-A Measure Resolution & Pipeline Outcomes

Phase 3-A general remediation requires:

- Resolve QCL **query** element codes from structured statistical intent (`production_quantity` → `2510`, `yield` → `2413`, `area_harvested` → `2312`).
- Never default every quantitative need to Production Quantity (`2510`).
- When multiple measures are explicitly requested and area/item/year are complete, **decompose** into one canonical portal query per measure (`DECOMPOSED_MEASURES`). Preserve all measures; never silently pick one.
- When multiple measures are requested but dimensions are incomplete, leave the element unresolved (`AMBIGUOUS_MEASURES`) rather than guessing.
- When an explicit element code contradicts an explicit structured measure surface, return `MEASURE_CONFLICT` (no silent certainty).
- Treat query/response dual codes as compatible only via the verified pairs above — never by `+3000` arithmetic and never by collapsing codes.
- Map WSA crop taxonomy identity → FAOSTAT item codes through an explicit label/slug map; never treat a numeric taxonomy ID as a FAOSTAT item code.
- QCL label→code resolution during search uses the versioned verified local map only (`resources/faostat/qcl_verified_dimension_map.php`). Unknown labels stay unresolved (`INCOMPLETE_FILTERS`) and must not probe the live portal codes API. Expand the map only via offline-reviewed imports.
- Canonicalize equivalent FAOSTAT provider queries by `domain|area|item|element|year` so NL variants do not re-execute the same portal request.
- Record internal pipeline stages under `planSummary.faostat_pipeline_outcome` and `planSummary.result_pipeline.stages` without changing the public result envelope. The historical field `deduplicatedResults` remains the post-rank filtered survivor list.
- Provider registration Model B: `AgriculturalProviderRegistry` (capabilities) and `ScientificSourceAdapterRegistry` (runtime adapters) share one canonical identity `fao_stat` and one activation flag.

## 10. Language Contract

This ADR MUST NOT change the established WSA language contract:

- Platform Language = Answer Language
- Question Language = Independent
- Retrieval Language = Independent
- Source Language = Original
- Source Metadata = Original
- Original URL = Unchanged

FAOSTAT retrieval language is an API/provider concern and MUST NOT leak the UI language into unrelated scholarly retrieval.

The currently verified OpenAPI language enum is `en|fr|es`; `ar` and `tr` are not assumed until verified.

The final WSA answer MUST remain in the platform language.

## 11. Provider Selection Rule

The old behavior in which Stage 3 appended `fao_stat` to every internet-first query is NOT acceptable for the final architecture if it causes FAOSTAT to be treated as an undifferentiated co-source.

The correct behavior is:

```text
Every WSA question
        ↓
FAOSTAT is considered
        ↓
Discover/select relevant FAOSTAT domain(s)
        ↓
Retrieve only relevant data
        ↓
Validate relevance at claim level
        ↓
Fuse with other required evidence
```

This is different from blindly attaching arbitrary FAOSTAT results to every answer.

## 12. Domain Expansion

QCL is the first verified implementation target.

Future FAOSTAT domains, including but not limited to trade, prices, food balance, inputs, and other agricultural datasets, MUST NOT be assumed to work merely because they appear in `groupsanddomains`.

Each domain MUST pass:

1. authenticated discovery
2. dimensions verification
3. code verification
4. data-query verification
5. response normalization verification
6. provenance verification
7. contract tests
8. evidence-relevance tests

Only then may the domain be enabled for production use.

## 13. Adapter Architecture

Do NOT patch the existing FENIX adapter into a hybrid implementation.

Create a new FAOSTAT Developer Portal client/adapter behind the existing provider abstraction and preserve the `fao_stat` provider key where practical.

Recommended separation:

```text
FaoStatDeveloperPortalClient
    ├── Authentication
    ├── Token Cache
    ├── Domain Discovery
    ├── Dimensions
    ├── Code Discovery
    └── Data Retrieval

FaoStatDeveloperPortalAdapter
    ├── Query Interpretation
    ├── Domain Selection
    ├── Code Resolution
    ├── Normalization
    ├── Evidence Classification
    └── Provenance

Existing WSA Evidence Pipeline
```

**HISTORICAL migration constraint:** keep the old FENIX adapter isolated and disabled until the Developer Portal implementation is proven, and do not present FENIX as compatible with the Portal.

**CURRENT:** Portal is proven. FENIX is historical/retired. No active FENIX adapter exists on the current branch. Negative-proof tests that reject `fenixservices.fao.org` remain intentionally preserved.

## 14. Security

FAOSTAT credentials MUST never be committed to Git.

Access and refresh tokens MUST never be logged or exposed in user-facing output.

Authentication data MUST use environment variables or an approved secret store.

The client MUST use HTTPS and an explicit official-host allowlist.

## 15. Resilience Policy

The following are WSA client policies, not claims about official FAOSTAT limits:

- cache AccessToken in memory only
- re-login before token expiry
- cache code tables conservatively
- use narrow data queries whenever possible
- use a finite client-side result cap
- use a 15–30 second timeout range according to runtime requirements
- retry at most once for transient 5xx/network failures with backoff
- do not retry 400
- on 401, re-authenticate once and retry the protected request once
- do not retry an empty 200 response
- if 429 is observed, honor `Retry-After` when present and back off conservatively

No official FAOSTAT rate limit is assumed until verified.

## 16. Provenance

FAOSTAT observations do not currently provide an observation-specific permalink in the verified live response.

WSA MUST NOT invent a permalink.

Recommended provenance consists of:

- source = FAOSTAT
- provider = fao_stat
- official host
- domain/domain_code
- area/area_code
- item/item_code
- element/element_code
- query_element_code
- response_element_code
- year/year_code
- unit
- value
- flag
- flag description
- reconstructed query URL without secrets
- retrieved_at
- datasource when available

User-facing citations should remain concise; the full structured observation belongs in evidence/audit metadata.

## 17. Testing Requirements

Before production enablement, tests MUST cover:

### Unit

- authentication
- token expiry
- domain resolution
- dimension resolution
- item resolution
- area resolution
- element dual-code handling
- year resolution
- response normalization
- empty-result handling
- error mapping
- provenance
- evidence classification

### Integration / Contract

- login
- groups/domains
- QCL discovery
- metadata
- dimensions
- items
- elements
- countries
- regions
- specialgroups
- years
- live data
- JSON
- CSV
- invalid filters
- 401 handling
- 500 handling

### End-to-End

```text
WSA Question
→ Query Understanding
→ FAOSTAT consideration
→ Domain/Code Resolution
→ FAOSTAT Retrieval
→ Normalization
→ Claim/Evidence Validation
→ Evidence Fusion
→ Answer Composition
```

The E2E suite MUST include questions where:

- FAOSTAT provides the direct quantitative answer
- FAOSTAT provides useful supporting data
- FAOSTAT has no relevant data and other sources must answer
- FAOSTAT data exists but must not be used for an unrelated claim
- FAOSTAT and scientific literature must be combined

## 18. Migration Strategy

Migration MUST be staged and reversible:

1. Freeze the existing state.
2. Add the Developer Portal HTTP client.
3. Add secure login/token handling.
4. Add domain discovery.
5. Add dimension/code discovery.
6. Add QCL data retrieval.
7. Add live-field normalization.
8. Add structured statistical evidence.
9. Add provenance.
10. Replace always-on FAOSTAT selection with the new provider-consideration model.
11. Add contract and E2E tests.
12. Enable the new provider behind a feature flag only after tests pass.
13. Keep FENIX isolated until the portal implementation is proven. **(HISTORICAL step — Portal is now proven; FENIX is retired. No active FENIX adapter on this branch.)**
14. Remove obsolete FENIX behavior only in a later explicitly approved migration stage. **(Do not delete FENIX negative-proof tests or historical documentation in Phase 9 Wave 1.)**

## 19. Rollback

The Developer Portal provider MUST be independently disableable.

Rollback MUST NOT require restoring or altering protected scientific WIP.

Disabling the feature flag must stop FAOSTAT calls without affecting OpenAlex, Crossref, Semantic Scholar, Open-Meteo, or other provider paths.

FENIX MUST NOT be silently re-enabled as if it were the Developer Portal implementation.

## 20. Consequences

### Positive

- FAOSTAT becomes a first-class agricultural data source across the complete WSA question space.
- No artificial subject whitelist limits future agricultural coverage.
- Quantitative agricultural facts can be grounded in official FAO data.
- Scientific and other sources remain available for questions FAOSTAT cannot answer directly.
- Claim-level evidence validation prevents irrelevant FAOSTAT data from contaminating answers.
- The architecture can expand as additional FAOSTAT domains are verified.

### Negative / Trade-offs

- Every question may incur additional FAOSTAT discovery/retrieval work.
- Provider selection and evidence fusion become more sophisticated.
- Domain/code resolution requires caching and careful error handling.
- Additional FAOSTAT domains require independent verification before enablement.
- Token lifecycle and upstream instability must be handled safely.

## 21. Non-Goals

This ADR does NOT:

- claim that FAOSTAT alone can answer every agricultural question
- claim that every FAOSTAT domain is already verified
- change the established language contract
- change OpenAlex/Crossref/Semantic Scholar roles
- authorize deletion of the FENIX adapter
- authorize repository-wide refactoring
- authorize implementation by itself

## 22. Acceptance Criteria

This decision is considered correctly implemented only when:

1. FAOSTAT is considered for every WSA question without an agricultural-topic gate.
2. FAOSTAT is not the only provider by default.
3. The system can dynamically combine FAOSTAT with other evidence sources.
4. FAOSTAT evidence has its own evidence classification.
5. Irrelevant FAOSTAT observations cannot be promoted as direct evidence for unrelated claims.
6. QCL works through the current Developer Portal contract.
7. Additional domains can be added without redesigning the provider architecture.
8. The established language contract remains unchanged.
9. No FENIX assumptions remain in the active Developer Portal path.
10. Credentials and tokens are never committed or logged.
11. All required contract and E2E tests pass before production enablement.

## 23. Final Architectural Statement

**WSA is an agricultural platform by definition. Every question is therefore processed through the agricultural knowledge architecture, and FAOSTAT is a core provider considered for every question. FAOSTAT is not the sole source and is never forced to support claims beyond its actual data. The final answer is produced by claim-level evidence fusion across FAOSTAT and all other appropriate knowledge providers.**
