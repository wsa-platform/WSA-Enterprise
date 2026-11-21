# Taxonomy and GBIF Verification — Gate 5

## Routing

Mentha QU: `question_type=species`, `required_evidence_type=species_list_or_taxonomy`, `scientific_name=Mentha piperita`, topics include taxonomy/phylogeny/binomial nomenclature.

## Answerability

- Bare “species” topic alone does not make oil/phenol papers DIRECT taxonomic evidence — regression tests PASS.
- GBIF hierarchy summary remains DIRECT taxonomic evidence — regression PASS.

## Multi-source taxonomy

| Source | Role on Mentha |
|---|---|
| GBIF | Structured taxonomy — 10 citations |
| OpenAlex | Scholarly taxon literature — 6 citations |
| Crossref | Attempted + successful (retrieval); citations deferred to stronger bindings |
| BHL | Attempted + successful (heritage lit); early-stop protected |
| AGRIS / DrDuke | Selected; early-stopped after adequacy |

GBIF is **not** exclusive by design or (post-fix) by Universal-path practice.

## Hierarchy

Verified ranks come from GBIF enrichment abstracts; missing ranks are not invented. Cross-source conflicts surface as `synthesis_completed_with_partial_conflicts`.

## Remaining limitation

Composer section heading still reads `### Plant family members` for some Mentha answers (intent/presentation label), while evidence itself is multi-source taxonomic. Heading polish is a residual UX issue, not exclusive-source retrieval.
