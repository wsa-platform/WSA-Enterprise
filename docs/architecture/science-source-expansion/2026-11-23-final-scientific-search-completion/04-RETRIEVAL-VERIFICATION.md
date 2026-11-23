# 04 — Retrieval Verification

Artifact: `completion_full_pipeline_probe.json`

## Classification

**`HYBRID_MULTILINGUAL_RETRIEVAL` leaning CROSS_LINGUAL:** Arabic question → English executable variants (ADR-022); high document overlap.

## AR/EN wheat (agris+openalex)

| Metric | EN | AR |
|---|---:|---:|
| sense | crop_water_requirement | crop_water_requirement |
| raw / survivors | 16 / 16 | 16 / 16 |
| validated | 16 | 16 |
| Unique stable keys | 16 | 16 |
| Overlap | **15** | |
| EN-only / AR-only | 1 / 1 | |
| Jaccard | **0.8824** | |

## Organic Eprints (Arabic organic soil)

- Eligible: true  
- Successful: organic_eprints + openalex  
- validated: 10  
- Sample OE IDs: `oai:orgprints.org:*`

## Sense generality smoke

| Case | Sense |
|---|---|
| irrigation_ar | crop_water_requirement |
| water_req_en | crop_water_requirement |
| growth_ar | general_knowledge |
| soil_fertility_ar | plant_growth |
| disease_ar | disease |
| salinity_en | salinity_physiology |
| incidental_water_en | varieties |
