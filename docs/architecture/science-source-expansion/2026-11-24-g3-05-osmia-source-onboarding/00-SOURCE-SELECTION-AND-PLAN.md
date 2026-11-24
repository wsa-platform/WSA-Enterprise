# 00 — Source Selection and Engineering Plan

**Date:** 2026-11-24  
**Starting HEAD:** `879df1fa6b8516b5cc08a5ca41b772f8825623aa`

## Selection among 110 seats

| Rank | Seat | Why considered | Gate result |
|---|---|---|---|
| Tried first | G2-17 REMVT | Strong livestock OA + OAI live | **BLOCKED_LICENSE** — OAI records carry `CC BY-NC-SA 4.0` despite About-page CC BY claim |
| Rejected prior | G2-15 Andalasian | Livestock OAI | BLOCKED_LICENSE (NC-SA) |
| **Selected** | **G3-05 OSMIA** | CC BY 4.0 verified (journal + Crossref work licenses); NextGate scaffold; free Crossref Works API; agri entomology / pollinators | Access + license PASS |

Already ACTIVE (not candidates): `agris`, `organic_eprints`, `dr_duke`, `gbif`, `bhl`, Stage-3 `openalex`/`crossref`/`semantic_scholar`.

## 6.1 Source study (G3-05)

| Field | Value |
|---|---|
| ADR ID | G3-05 |
| Name | OSMIA |
| Key | `osmia` |
| Scope | Peer-reviewed diamond OA journal on Hymenoptera / wild bees / pollinators |
| Access | No native OAI/API; Crossref Works `ISSN 2727-3806` (free, no key) |
| License | **CC BY 4.0** (about page + per-work Crossref license URL; NC filtered out) |
| Auth | None |
| Volume | ~125 Crossref works (bounded harvest 80 retained this run) |
| Reuse | `BulkDatasetSourceProfile`, `LocalIndexIngestionService`, `LocalIndexScientificSourceAdapter`, NearReady activation pattern (OE/BHL) |

## 6.2 Architectural integration (detail)

1. **Data path:** Crossref Works API (`ISSN 2727-3806`) → `OsmiaCrossrefSnapshotBuilder` (CC BY filter) → JSONL → `LocalIndexIngestionService` → `ScientificRecordStore` + `LocalIndexSearchEngine` / memory `LocalScientificIndex` → `LocalIndexScientificSourceAdapter` → Stage-3 retrieval → evidence validation → `AnswerComposer` / claim attribution.
2. **Harvest:** `php artisan scientific:harvest-osmia-crossref` (optional `--reuse-snapshot`, `--memory`, `--probe-query`).
3. **Metadata/content:** DOI as identity; title/abstract/authors/year/URL/journal; no PDF binary ingest.
4. **Quality/identity:** Incomplete works skipped; DOI required; license URL must be CC BY 4.0 (NC rejected).
5. **Dedup:** Store identity by DOI / record id; retention policy supersedes prior generations.
6. **LocalIndex:** Durable SQLite store + intended Sqlite FTS5; memory index accepted for harness when FTS rebuild blocked.
7. **Search engine:** Catalog registers `osmia` → `LocalIndexScientificSourceAdapter`; CapVer G3-05 STATIC_DOWNLOAD search capability.
8. **Answer/claims:** Hits enter validation; only validated evidence may attribute claims (gate currently empty on sample).
9. **Refresh:** Re-run harvest command; bounded pages; reuse snapshot for offline re-ingest.
10. **Errors:** Builder throws → command FAILURE; ingest report status; FTS rebuild failure logged without wiping durable ACTIVE rows.

## 6.3 Phased plan (executed / status)

| Phase | Goal | Effort | Status |
|---|---|---|---|
| A Contract & Design | Seat, licensing, profile | 2h | DONE |
| B Access & Harvest | Crossref snapshot builder + artisan | 3h | DONE (80 CC BY records) |
| C Validation & Normalization | Retain policy / DOI identity | 1h | DONE |
| D LocalIndex Persist & Index | Durable store + FTS | 4h | PARTIAL — store OK; **FTS5 rebuild SQLSTATE[HY000] on Docker Desktop bind-mount** |
| E Retrieval Integration | Catalog + eligibility + adapter | 3h | DONE (memory index search SUCCESS) |
| F Answer & Attribution | Validate + compose with osmia hits | 3h | PARTIAL — retrieval OK; validation emptied sample answers |
| G Regression | Unit tests + non-regression of existing sources | 2h | DONE unit (4); live Stage-3 early-stop still OA/CR/S2 |
| H Activation | NearReady ACTIVE + CapVer spec + config flag | 2h | DONE wiring; scientific proof incomplete |

## 6.4 Proof plan vs evidence

| Proof | Result |
|---|---|
| Records in LocalIndex store | PASS — gen≥1, 80 ACTIVE |
| Search with agri query | PASS — memory LocalScientificIndex (`Nomada`, `abeille`) |
| Durable FTS search | FAIL — HY000 rebuild |
| Live agent surfaces osmia | NOT proven (FTS cold → UNAVAILABLE) |
| Claim attribution to osmia docs | NOT proven on sample compose |
| No harm to existing sources | PASS — eligibility scoped to bee/pollinator cues |

## 6.5 Risks

| Risk | Mitigation |
|---|---|
| NC contamination | Crossref license URL must contain `by/4.0` and not `by-nc` |
| FTS on Windows bind mount | Offline rebuild on Linux/CI; memory index acceptance harness |
| Over-selection | `GovernedScientificSourceEligibility` requires bee/pollinator cues |
| REMVT false CC BY | Prefer record-level licenses over About page |

## 6.6 Decision

Proceeded with G3-05 implementation. Do **not** claim `SOURCE_ADDITION_VERIFIED` until FTS + validated attribution pass.
