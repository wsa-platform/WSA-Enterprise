# Rev 6.5 — Browser E2E (Stable Research Pagination)

Isolated Playwright package (does **not** alter production frontend dependencies).

## Prerequisites

- Docker stack healthy (`backend`, `frontend`, `nginx` on `http://127.0.0.1:8079`)
- Host Node.js 20+
- Microsoft Edge or Chrome installed (default channel: `msedge`)

## Install

```bash
npm install
```

## Run

```bash
npm test
```

Optional:

```bash
set WSA_E2E_CHANNEL=chrome
set WSA_E2E_BASE_URL=http://127.0.0.1:8079
npm test
```

## What it verifies

1. **Stage A** — real UI scientific search → one `POST .../research-agent/query` → `result_set_id` → UI display
2. **Stage B** — controlled 21-answer StableAnswerResultSet (seeded via `backend/scripts/rev65_seed_controlled_result_set.php`) → pages 1/2/3 via real `GET .../research-agent/results` without another scientific Query

Evidence is written to `docs/architecture/science-source-expansion/rev65-browser-evidence/`.
