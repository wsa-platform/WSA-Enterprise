# 05 — React Live UI Evidence

## Method

Playwright CLI (`frontend/e2e-rev65/tests/live-scientific-answer-ui.spec.ts`) against `http://127.0.0.1:8079` — **no API mock**.

Flow: Open home → fill AR deficit query → Submit → Live API → Render.

## Results

| Check | Result |
|---|---|
| API `/public/research-agent/query` HTTP 200 | PASS |
| `home-research-primary-answer` visible | PASS |
| Answer language Arabic chrome | PASS |
| Deficit substance in primary card | PASS (post frontend rebuild) |
| Citations visible | PASS |
| Claim attribution ledger visible | PASS |
| Wastewater-only primary (pre-fix) | FAIL observed → fixed by promoteComposedPrimaryAnswer |

Artifact: `react_live_ui_evidence.json`

**Classification:** `LIVE_UI_VERIFIED` for Arabic deficit irrigation homepage path.
