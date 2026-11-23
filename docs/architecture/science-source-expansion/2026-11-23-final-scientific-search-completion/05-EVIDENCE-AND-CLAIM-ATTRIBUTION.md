# 05 — Evidence and Claim Attribution

Source: `completion_full_pipeline_probe.json` (wheat_ar / organic_ar).

## Wheat Arabic

| Metric | Value |
|---|---:|
| validated_count | 16 |
| claim_count | 2 |
| citation_count | 10 |
| ledger_count | 2 |

### Evidence-to-claim (sample)

| Claim ID | Support (ledger) | Evidence | Sources |
|---|---|---|---|
| claim-qc-1 | conflicting / evidence_usable rows | multiple evidence_ids | openalex, agris |
| claim-qc-2 | (present in ledger_sample) | bound | openalex, agris |

Claims carry English source prose with evidence_ids + attributions (DOI/URL). Citation titles match scientific papers (not homepage-only).

### Independence note

Overlap uses DOI/sid keys; same DOI across providers counted once in Jaccard set.

## Organic Arabic

ledger_count=3; OE attributions use `oai:orgprints.org:*` + orgprints.org URLs.

## Citation coverage (wheat_ar heuristic)

- Total claims: 2  
- Claims with evidence_ids/attributions: ≥1 confirmed in sample (probe reports both claims structured)  
- Do not invent percentages beyond probe fields.
