# Cross-Source Evidence Contracts — Gate 4

## Separated identities

| Identity | Preservation |
|---|---|
| `source_key` | Passed through Stage-3 → ClaimEvidenceMatcher → EVL → Composer (P0 retained) |
| Record/document ID | Provider identifiers in provenance (`original_identifier`) |
| Scientific entity | QU `scientific_name` / subject entity |
| Claim | Question-claim matrix / composer bindings |
| Evidence | Validation report evidence rows |
| Provenance | Adapter provenance arrays (source_key, retrieved_at, adapter, contract_version) |

## Source-specific contracts

- **GBIF:** taxonomic hierarchy / checklist semantics — not required to look like scholarly abstracts; bare species-name presence alone is insufficient; hierarchy summaries are DIRECT taxonomic evidence.
- **OpenAlex / Crossref:** bibliographic scholarly — DOI ≠ full text; abstracts may be inverted-index reconstructed; not forced into GBIF taxonomy structure.
- **BHL:** heritage bibliographic pages — compatible with TAXONOMIC_IDENTITY when entity matches.
- **DrDuke / AGRIS / Organic Eprints:** local-index row semantics; eligibility gated.
- **FAOSTAT:** statistical observations; consideration ≠ composer acceptance.

## Duplicates / conflicts

- Independent provider count reported in observability (`citation_provider_contribution`).
- Mentha live: status `synthesis_completed_with_partial_conflicts` with 2 independent citation providers — conflicts surfaced, not silently discarded.
- Duplicate OpenAlex/Crossref republishing of same underlying work is not treated as multi-source corroboration of taxonomy hierarchy; GBIF hierarchy remains distinct evidence modality.

## Gate 4: PASS (prior P0 retained + multi-provider citations observed)
