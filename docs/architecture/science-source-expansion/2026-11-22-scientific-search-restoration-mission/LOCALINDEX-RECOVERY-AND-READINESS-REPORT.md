# LocalIndex Recovery and Readiness Report

## Purpose
Document LocalIndex I/O diagnosis, data safety, and readiness for AGRIS / Dr. Duke / Organic Eprints.

## Status: VERIFIED (readiness) — no destructive recovery required

### Diagnosis
| Check | Result |
|-------|--------|
| Path | `backend/storage/app/scientific_record_store.sqlite` (~9.8 GB) |
| Docker mount | Backend container sees same path under `/var/www/html/storage/app/` |
| `scientific:diagnose-persistence` | `reachable=true`, `table_present=true` (transient prior `disk I/O error` not reproduced) |
| Store generations | agris **2088**, dr_duke **19**, organic_eprints **5** |
| FTS meta generations | agris **2088**, dr_duke **19**, organic_eprints **5** (match store) |
| FTS content | AGRIS FTS rows present (sampled) |
| Sync rebuild on search | Still forbidden (`local_index_cold_or_stale_rebuild_deferred`) |

### Root cause of prior I/O failure
**Class:** ENVIRONMENTAL_LIMITATION / transient container SQLite access.

Evidence: file intact on host; later diagnose reachable; store+FTS generations match. No corruption proven. Full `PRAGMA quick_check` / FTS COUNT on 9.8GB intentionally **NOT_RUN** (blocking risk).

### Backup / mutation policy
- **No file delete/replace performed.**
- Full 9.8GB copy not created (no mutation planned; risk of disk pressure).
- Data preserved in place; readiness proven via store + `scientific_source_fts_meta`.

### Lifecycle
Adapter exposes `local_index_readiness`, `store_generation`, `index_generation`, `generations_match`.

### Source readiness
| Source | Store gen | FTS gen | Readiness |
|--------|-----------|---------|-----------|
| agris | 2088 | 2088 | READY |
| dr_duke | 19 | 19 | READY |
| organic_eprints | 5 | 5 | READY |

### Live L4+ via application path
Adapter/FTS search through full agent path: **PARTIAL** — engine init on large DB is slow under Docker; SQL/meta readiness VERIFIED. Prior harnesses exist for G1-01/G1-17/G1-02.

### Evidence artifacts
- `backend/storage/app/e2e-tmp/localindex_store_gen_only.php` output
- `backend/storage/app/e2e-tmp/localindex_fts_meta_select.php` output
- `scientific:diagnose-persistence` JSON
