# 08 — Performance Results

From substance parity live probe (agent path), elapsed_ms samples:

| Case | elapsed_ms (post-fix run) |
|---|---|
| EN_CWR | ~29k (prior) / variable |
| AR_CWR | ~21k |
| TR_CWR | ~8k |
| FR_CWR | ~6k |
| EN_DEFICIT | ~6k |
| AR_DEFICIT | ~5k |
| React Live UI E2E | ~27–40s wall including retrieval |

No performance optimization that reduces evidence quality. Memory profiling: NOT_RUN.
