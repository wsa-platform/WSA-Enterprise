# 06 — Scientific Answer Quality

## Before fix (probe)

Arabic irrigation `answer_length≈136` — chrome only (“تشير الأدلة…”) with **no listed facts**.

## After fix (probe + browser)

- Heading: `### الاحتياج المائي`
- Arabic framing + **مقتطف من المصدر** excerpts
- `answer_length≈261+` with facts listed
- Live React (`127.0.0.1:8079`): shows **الإجابة** with same structure; sources listed with DOI/document URLs

## Criteria

| Criterion | Status |
|---|---|
| Question Understanding | PASS (sense parity) |
| Scientific Accuracy | PARTIALLY_VERIFIED — excerpts attributed; deep synthesis still extractive |
| Completeness | PARTIALLY_VERIFIED — facts present; not full multi-study narrative |
| Scientific Synthesis | PARTIALLY_VERIFIED — improved vs chrome-only |
| Claim Attribution | PASS (ledger populated) |
| Citation Coverage | PARTIALLY_VERIFIED |
| Contradiction Handling | PARTIALLY_VERIFIED (status/conflicts in pipeline; limited in main body) |
| Language Consistency | PASS (Arabic chrome + labeled EN) |
| Foreign Excerpts | PASS |
| Uncertainty | PARTIALLY_VERIFIED |
