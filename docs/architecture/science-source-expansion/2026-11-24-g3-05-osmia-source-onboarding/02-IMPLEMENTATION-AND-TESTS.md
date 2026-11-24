# 02 — Implementation and Tests

## Code

| Component | Role |
|---|---|
| `OsmiaCrossrefSnapshotBuilder` | Bounded Crossref→JSONL, CC BY filter |
| `HarvestOsmiaCrossrefCommand` | `scientific:harvest-osmia-crossref` |
| `CurrentMembershipNearReadySeatCatalog` | G3-05 ACTIVE contract + BulkDataset profile |
| `GovernedScientificSourceEligibility` | Bee/pollinator scope for `osmia` |
| `CapVerAuthorizedSeatSpecCatalog` | G3-05 CapVer spec |
| `agricultural_intelligence.osmia.enabled` | Config flag |
| `OsmiaG305OnboardingTest` | Seat, catalog, eligibility, fixture ingest/search |

## Commands run

```text
php artisan test --filter=OsmiaG305OnboardingTest
→ Tests: 4 passed (17 assertions), Duration ~33s, exit 0
  ✓ near ready seat is runtime active with cc by licensing
  ✓ catalog registers osmia governed adapter
  ✓ eligibility requires bee or pollinator cues
  ✓ jsonl fixture ingests and is searchable

php artisan scientific:harvest-osmia-crossref --max-records=80
→ written 80, ingest completed, store generation ≥1

php artisan scientific:harvest-osmia-crossref --reuse-snapshot --memory --probe-query=Nomada
→ probe_status=success, result_count=5, source osmia
```

## Limitation

Sqlite FTS rebuild on connection `scientific_records` raises `SQLSTATE[HY000]` in this Docker Desktop bind-mount environment. Memory `LocalScientificIndex` search against durable ACTIVE rows succeeds.
