# 6.3 — Phased execution detail

## A. Source Contract & Integration Design — DONE (~2h)

- **Goal:** Legal/commercial eligibility + seat contract for G3-05.
- **Files:** `CurrentMembershipNearReadySeatCatalog`, CapVer catalog, NextGate (inventory-only), licensing notes.
- **Work:** CC BY verification; BulkDataset profile; ACTIVE NearReady contract; NextGate `superseded_by_near_ready_g3_05`.
- **Deps:** Selection after REMVT BLOCKED_LICENSE.
- **Tests:** Seat ACTIVE + licensing permits METADATA_STORAGE.
- **Evidence:** `00`, `01`.
- **Exit:** Contract ACTIVE; license PERMITTED for LocalIndex metadata.

## B. Access & Harvest Implementation — DONE (~3h)

- **Goal:** Bounded Crossref→JSONL harvest.
- **Files:** `OsmiaCrossrefSnapshotBuilder`, `HarvestOsmiaCrossrefCommand`.
- **Work:** Paginated Works API; CC BY filter; `--reuse-snapshot` / `--probe-query`.
- **Deps:** A.
- **Tests:** Live harvest 80 rows; reuse-snapshot path.
- **Evidence:** snapshot path + store gen.
- **Exit:** ≥1 CC BY JSONL snapshot written.

## C. Data Validation & Normalization — DONE (~1h)

- **Goal:** DOI identity, skip incomplete/NC.
- **Files:** Builder + `LocalRecordRetentionPolicy` reuse.
- **Exit:** Only CC BY complete works retained.

## D. LocalIndex Persistence & Indexing — PARTIAL (~4h spent)

- **Goal:** Durable ACTIVE rows + searchable FTS.
- **Files:** Ingestion service, Sqlite FTS engine.
- **Status:** Store PASS (80 ACTIVE); FTS rebuild **FAIL HY000** (Docker Desktop bind-mount).
- **Exit unmet for VERIFIED:** Durable FTS search success.

## E. Scientific Retrieval Integration — DONE (memory) (~3h)

- **Goal:** Catalog + eligibility + adapter search.
- **Files:** Eligibility, language catalog, knowledge type, config `osmia.enabled`, adapter via NearReady registration.
- **Evidence:** memory probe Nomada/abeille success.
- **Exit for wiring:** Catalog has `osmia`; eligibility scoped.

## F. Scientific Answer & Claim Attribution — PARTIAL (~3h)

- **Goal:** Validated claims cite osmia docs in final answer.
- **Status:** Adapter hit exists; validation emptied → `no_validated_evidence`.
- **Exit unmet:** `osmia_attribution_signal=true` with claim citations.

## G. Regression & Performance — UNIT DONE (~2h)

- **Goal:** No damage to existing sources; unit coverage.
- **Tests:** `OsmiaG305OnboardingTest` (4); live Stage-3 still OA/CR/S2 without osmia (expected while FTS cold).
- **Exit for VERIFIED:** Broader regression green + osmia not forced into non-bee queries.

## H. Activation & Production Readiness — WIRING DONE / PROOF OPEN (~2h)

- **Goal:** Production-safe activation.
- **Status:** NearReady ACTIVE + CapVer + config; scientific proof incomplete → classification `READY_FOR_ONBOARDING`.
