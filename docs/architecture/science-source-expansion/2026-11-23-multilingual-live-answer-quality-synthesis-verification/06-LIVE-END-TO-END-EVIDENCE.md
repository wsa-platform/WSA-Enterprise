# 06 — Live End-to-End Evidence

## Method

Script: `backend/storage/app/e2e-tmp/_live_answer_quality_probe.php`  
Artifact: `live_answer_quality_probe.json` (copied into this mission folder)  
Path: Question → Understanding → Retrieval → Evidence → Claims → Attribution → Conflict/Context → Synthesis → AnswerComposer (agent `conductResearch`)

**Evidence class:** Live agent path (not Mock for compose). Not browser Live UI for full answer.

## Per-case log (condensed)

### EN_DEFICIT_IRRIGATION
- Sense: crop_water_requirement; intent: irrigation
- Findings: water deficit timing / deficit irrigation yield reduction (empirical)
- Claims: 2; ledger: 2; citations: 13
- Synthesis packet: yes; genuine_conflicts: 0; contextual_differences: 5
- Elapsed: ~18.1s; confidence: 0.783

### AR_DEFICIT_IRRIGATION
- Same sense/intent; answer_language: ar
- Findings include EN source excerpts + one methods chrome sentence
- Synthesis notes in Arabic; genuine_conflicts: 2
- Elapsed: ~8.8s; confidence: 0.673

### TR_CWR / FR_CWR
- Answers generated with localized wrappers; findings = irrigation treatment lists (EN)
- Packet present; completeness weak for “water requirement” quantification
- Elapsed: ~9s each

### EN_INSUFFICIENT_STYLE (Martian)
- Status: `insufficient_evidence` (domain gate)
- No scientific_generated overclaim

### AR_DISEASE
- Status: `insufficient_evidence` — honest empty findings

## API

Prior capture files present: `api_en_deficit_request.json` / `api_en_deficit_response.json` (large). Live probe used agent path primarily for quality metrics.

## Distinction

| Layer | Proven |
|---|---|
| PHPUnit | Quality gates + synthesis contracts |
| Live agent compose | Probe v2 cases above |
| Live HTTP API envelope | Artifact files present; not re-asserted as sole gate |
| Live UI render of answer | Not completed (React click blocked) |
