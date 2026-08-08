# PostgreSQL data model

WSA-Enterprise uses organization-scoped multi-tenancy. All tenant-owned records carry an `organization_id` foreign key and API reads/writes are filtered through the authenticated user's organization membership.

## Core platform (Phase 1–2)

```
users --< organization_user >-- organizations --< projects --< tasks
```

| Table | Purpose |
| --- | --- |
| users | User identities |
| organizations | Tenant/workspace boundary |
| organization_user | Membership and role |
| projects | Organization-owned initiatives |
| tasks | Project work items |
| personal_access_tokens | Sanctum API tokens |

## Business modules (Phase 3)

| Domain | Tables |
| --- | --- |
| Access control | roles, permissions, role_user, permission_role |
| Workforce directory | companies, branches, employees |
| Catalog | customers, suppliers, products, categories, warehouses |
| Operations | inventory_balances, inventory_movements, purchase_orders, purchase_order_items |
| Commerce | sales_orders, sales_order_items, invoices, invoice_items, app_notifications |

## Agricultural modules (Phase 4)

Farm, crop, and soil data form the foundation for future Phase 5 capabilities (plant disease diagnosis, treatment recommendations, training, research library, and AI services).

```
organizations --< farms --< farm_regions
                      \--< farm_fields --< farm_blocks
                      \--< greenhouses
                      \--< irrigation_zones
                      \--< gis_maps

farm_fields / farm_blocks / greenhouses ~~~ gps_coordinates (polymorphic)

organizations --< crop_types --< crop_varieties
              \--< crop_seasons
              \--< growth_stages
              \--< crop_harvests
              \--< crop_yields

organizations --< soil_analyses --< soil_nutrients
              \--< soil_recommendations
```

| Table | Purpose | Key relationships |
| --- | --- | --- |
| farms | Top-level farm sites | organization |
| farm_regions | Subdivisions within a farm | farm |
| farm_fields | Planting areas | farm, region (optional) |
| farm_blocks | Sub-field planting units | field |
| greenhouses | Protected cultivation structures | farm, field (optional) |
| irrigation_zones | Water delivery zones | farm, field/block/greenhouse (optional) |
| gps_coordinates | Boundary/location points | polymorphic (`coordinateable`) |
| gis_maps | GeoJSON map layers | farm (optional) |
| crop_types | Crop catalog | organization |
| crop_varieties | Varieties per crop type | crop_type |
| crop_seasons | Planting seasons | farm (optional) |
| growth_stages | Phenological stages | crop_type (optional) |
| crop_harvests | Harvest events | season, crop_type, field/block |
| crop_yields | Yield forecasts vs actuals | season, crop_type, field/block |
| soil_analyses | Lab/field soil samples | farm, field, block |
| soil_nutrients | Nutrient readings per analysis | soil_analysis |
| soil_recommendations | Agronomic actions | soil_analysis, field, block |

### API surface

All agricultural endpoints require Sanctum authentication and are scoped by organization:

| Prefix | Modules |
| --- | --- |
| `/api/v1/farm/{module}` | farms, regions, fields, blocks, greenhouses, irrigation-zones, gps-coordinates, gis-maps |
| `/api/v1/crop/{module}` | types, varieties, seasons, growth-stages, harvests, yields |
| `/api/v1/soil/{module}` | analyses, nutrients, recommendations |

### Phase 5 extension points

The Phase 4 schema is designed so future modules can attach without restructuring core tables:

- **Plant disease diagnosis** — add `diagnosis_requests` (organization, field/block, image path, status) and `diagnosis_results` linked to crop types; reuse existing file storage config.
- **Treatment recommendations** — extend `soil_recommendations` category enum or add `treatment_recommendations` referencing diagnosis results.
- **Training & courses** — add `courses`, `course_modules`, `enrollments` scoped by organization.
- **Research library** — add `library_items` (organization, title, type, file/url, tags) with optional links to crop types.
- **AI services** — queue-backed inference jobs referencing organization and source records (diagnosis, soil analysis, etc.).

Demo agricultural data is seeded by `AgriculturalSeeder` (invoked from `DatabaseSeeder`) for the `wsa-demo` workspace.
