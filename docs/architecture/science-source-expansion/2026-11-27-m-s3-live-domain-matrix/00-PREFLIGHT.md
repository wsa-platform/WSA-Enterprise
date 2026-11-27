# Pre-Flight — M-S3-LIVE-DOMAIN-MATRIX-AR-EN-FR-TR

**Date:** 2026-11-27  
**Mission ID:** `M-S3-LIVE-DOMAIN-MATRIX-AR-EN-FR-TR`  
**Starting branch:** `phase-18-m18-ai-marketing-communications`  
**Starting HEAD:** `3f63b28b70999c5cc46adf18ebd3695e3243e8f3`

## Pre-Flight Gate Table

| Check | Required | Evidence | Status |
|---|---|---|---|
| Git State | Branch, HEAD, WIP protected | HEAD `3f63b28`; CapVer/CSQ/unrelated WIP left unstaged | PASS |
| Source Availability | LocalIndex + REST sample | OSMIA LocalIndex READY (80 records); OpenAlex/Crossref operational; AGRIS/OE/DrDuke gen=0/unavailable | PARTIAL |
| LocalIndex | Persistence path operational | `scientific_record_store.v2.sqlite`; OSMIA searchable; AGRIS/OE/DrDuke not ready | PARTIAL |
| AnswerComposer | Component + targeted test | Compose path live; Directness/Osmia unit baselines | PASS |
| Baseline Tests | Relevant contract suite | OsmiaG305 + LatinTaxon + KnowledgeType + Directness assessor | PASS |
| Data Safety | No secrets; WIP protected | Unrelated WIP never staged for this mission | PASS |

## Domain selection (§3.4)

| Family | Domain | Rationale |
|---|---|---|
| A Core production | Water and Irrigation | Distinct requirement / WUE-style evidence |
| B Plant health | Plant Diseases and Pests | Mandatory specialized pathogen evidence |
| C Structured biology | Biodiversity and Pollinators | Faunistic / OSMIA + scholarly host-association evidence |

## Baseline classification

**`BASELINE_READY_WITH_LIMITATIONS`**

- REST/API scholarly path operational (OpenAlex, Crossref; Semantic Scholar intermittent).
- LocalIndex operational for OSMIA only.
- AGRIS / Organic Eprints / Dr. Duke LocalIndex paths unavailable (generation 0 or not built) → recorded as partial source coverage, not software defects for this mission.
