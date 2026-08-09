# Phase 6 architecture

Phase 6 strengthens production readiness across security, API integration, web/mobile UX, AI services, search, media handling, and performance without replacing earlier milestones.

## Authorization and tenant isolation

- All tenant-owned APIs resolve the active organization through `ResolvesOrganization`.
- Authenticated users may pass `X-Organization-Id` when they belong to multiple organizations.
- Cross-tenant header selection returns **403**.
- Record lookups continue to filter by `organization_id`; foreign IDs outside the tenant return **404** or **422**.

## Platform integration endpoints

| Endpoint | Purpose |
| --- | --- |
| `GET /api/v1/platform/organizations` | List organizations for the signed-in user |
| `GET /api/v1/platform/workflow-summary` | Aggregate counts for farms, diagnosis, training, library |

## AI service layer

- Provider contract remains `AiProviderInterface`.
- `AiRequestValidator` whitelists request types: `diagnosis`, `library_summary`, `library_qa`, `training_assistance`.
- `AiResponseNormalizer` returns consistent decision-support payloads.
- `AiService` logs usage metadata, handles failures, and enforces configured timeout metadata.
- Default provider remains `mock`; no external credentials are required for tests.

## Media references

- Diagnosis images and library documents store disk/path metadata only.
- `MediaReferenceService` validates allowed disks and rejects unsafe paths.
- API responses expose `{ disk, reference }` metadata instead of raw filesystem paths.

## Library search

- PostgreSQL-compatible search using `ILIKE` in production and `LIKE` in SQLite tests.
- Supports query text, category, crop type, tag, and pagination filters.
- Orders exact title matches ahead of partial matches.

## Performance indexes

Backward-safe indexes were added for common tenant filters on diagnosis requests, library items, training enrollments, AI requests, and farms.

## Web navigation

The React app uses route-based navigation for:

- Dashboard
- Farms
- Crops
- Soil
- Diagnosis
- Training
- Library
- AI Services

## Mobile navigation

The Flutter app adds:

- Session restore via `shared_preferences`
- Dashboard summary screen
- Farms and fields tabs
- Existing diagnosis, training, and library lists

## Demo workflow

1. Sign in as `admin@wsa.test`
2. Load dashboard and workflow summary
3. Browse farms and crop records
4. Submit or review a diagnosis request
5. Open training enrollments/courses
6. Search the agricultural library
7. Run a mock AI library Q&A request

Green Valley Farm demo data from earlier seeders remains intact.
