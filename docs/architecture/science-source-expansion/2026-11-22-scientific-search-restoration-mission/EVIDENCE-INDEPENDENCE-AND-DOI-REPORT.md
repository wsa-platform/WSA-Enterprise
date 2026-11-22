# Evidence Independence and DOI Report (ISS-RC-10)

## Purpose
Separate discovery-provider multiplicity from independent scientific document identity.

## Status: VERIFIED (contract + unit)

### Finding
`independent_provider_count` historically counted unique `source_key` values on citations, including multiple providers that discovered the **same DOI** after Stage-3 merge. That metric is valid for Outcome-B *discovery contribution*, but it must not be read as independent document count.

### Correction
Added `EvidenceIndependenceAssessor`:
- `independent_provider_count` — discovery providers (preserved Outcome-B semantics)
- `independent_document_count` — unique DOI / URL / source:id keys
- `same_document_multi_provider_count` — documents with >1 contributing provider
- Invalid DOI does not invent identity
- Missing DOI falls back to URL or `source_key:source_identifier`

Wired into `AnswerComposer::citationProviderContribution()`.

### Tests
`EvidenceIndependenceAssessorTest` + `OutcomeBIndependentCitationProvidersTest` — OK.

### Sufficiency
Evidence sufficiency remains `directCount >= 1` — not inflated by provider multiplicity.
