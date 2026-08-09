# PostgreSQL data model

WSA-Enterprise uses organization-scoped multi-tenancy. All tenant-owned records carry an `organization_id` foreign key and API reads/writes are filtered through the authenticated user's organization membership.

## Core platform (Phase 1–2)

```
users --< organization_user >-- organizations --< projects --< tasks
```

## Business modules (Phase 3)

Access control, workforce directory, catalog, operations, and commerce tables remain unchanged from earlier phases.

## Agricultural modules (Phase 4)

Farm, crop, and soil tables provide the operational foundation for Phase 5 decision-support features.

## Phase 5 modules

### Disease diagnosis

```
organizations --< diagnosis_categories --< diagnosis_subjects
              \--< diagnosis_symptoms
              \--< diagnosis_diseases
              \--< diagnosis_requests --< diagnosis_results --< diagnosis_recommendations
```

Diagnosis outputs are stored with `is_decision_support = true` and are not authoritative medical/scientific diagnoses.

| Table | Purpose |
| --- | --- |
| diagnosis_categories | High-level diagnosis groupings |
| diagnosis_subjects | Crop/animal/topic subjects, optionally linked to `crop_types` |
| diagnosis_symptoms | Symptom catalog |
| diagnosis_diseases | Possible diseases/problems |
| diagnosis_requests | User-submitted cases with notes, symptoms, image metadata, workflow status |
| diagnosis_results | Decision-support results with confidence, severity, provider metadata |
| diagnosis_recommendations | Follow-up actions linked to a result |

### Training / education

```
organizations --< training_courses --< training_lessons --< training_objectives
                                              \--< training_quizzes --< training_questions
organizations --< training_enrollments --< training_progress
              \--< training_certificates
```

Arabic content fields (`title_ar`, `content_ar`, etc.) support Arabic-first delivery with English/Turkish-ready locale fields.

### Agricultural library

```
organizations --< library_categories
              \--< library_tags
              \--< library_items >--< library_item_tag >-- library_tags
```

Library items support articles/documents/resources, publication status, tags, crop links, and safe file metadata (`file_disk`, `file_path`).

### AI services foundation

```
organizations --< ai_requests
```

AI execution uses a provider contract (`AiProviderInterface`) with a default `mock` provider for local development and tests. Configuration:

- `AI_PROVIDER=mock`
- `AI_TIMEOUT=30`

No API credentials are stored in source control. Future providers can be added without rewriting diagnosis, training, or library business logic.

### Phase 5 API surface

| Prefix | Purpose |
| --- | --- |
| `/api/v1/diagnosis/requests` | Submit and review diagnosis workflow |
| `/api/v1/diagnosis/{module}` | categories, subjects, symptoms, diseases, recommendations |
| `/api/v1/training/enrollments` | Learner enrollment and progress |
| `/api/v1/training/progress/complete` | Lesson completion tracking |
| `/api/v1/training/{module}` | courses, lessons, objectives, quizzes, questions |
| `/api/v1/library/search` | Published library search/filter |
| `/api/v1/library/{module}` | categories, tags, items |
| `/api/v1/ai/provider` | Active provider metadata |
| `/api/v1/ai/requests` | Generic AI request log and invocation |

Demo Phase 5 data is seeded by `Phase5Seeder` after `AgriculturalSeeder` for the `wsa-demo` workspace.

## Phase 6 additions

### Platform endpoints

| Endpoint | Purpose |
| --- | --- |
| `/api/v1/platform/organizations` | Organizations available to the signed-in user |
| `/api/v1/platform/workflow-summary` | Cross-module counts for dashboard/workflow views |

### Authorization

- Optional `X-Organization-Id` request header selects the active tenant when a user belongs to multiple organizations.
- Invalid tenant selection returns **403**.
- Resource lookups remain scoped by `organization_id`.

### Media metadata

Diagnosis requests and library items expose safe `{ disk, reference }` metadata in API responses. Raw storage paths are hidden.

### Search and indexes

Library search supports category, crop type, tag, and relevance ordering. Phase 6 adds tenant-scoped indexes on high-traffic tables documented in `docs/phase6.md`.
