# ADR-019 — Protect the Crop Page Pipeline

- **Status:** Accepted
- **Date:** 2026-09-17
- **Scope:** WSA-Enterprise public agricultural research architecture
- **Decision type:** Architectural boundary / protected path

## Decision

The existing **Crop Page / Field Crop pipeline is a protected architectural path**.

It MUST NOT be modified, refactored, replaced, optimized, reorganized, or behaviorally changed under any circumstance as part of work on the Home Page research pipeline or related scientific-research fixes.

The Crop Page pipeline is treated as a **protected baseline**. Its current behavior, contracts, inputs, outputs, and sequencing must remain unchanged.

## Protected Path

The protected flow is:

`PlantProductionPage` → `FieldCropSelector` → `FieldCropFarmingNeedsPanel` → `fetchFieldCropKnowledgeProfile` → `GET /public/field-crops/farming-needs-profile` → `PublicFieldCropCultivationController` → `AgriculturalResearchAgent::conductCropProfileResearch()` → `conductResearch()` → `ResearchPlanner::planKnowledgeQuery()` → crop-profile planning → `KnowledgeQueryPlan` → scientific search / validation / composition → existing crop-profile completion flow.

The following are explicitly protected:

- `FieldCropSelector`
- `FieldCropFarmingNeedsPanel`
- `fieldCropCultivation.ts`
- `PublicFieldCropCultivationController`
- `conductCropProfileResearch()`
- crop-profile planning behavior and contracts
- crop-page-specific request/response contracts
- existing crop-page behavior and output semantics
- any other implementation that is part of the established Crop Page pipeline, even if it is shared with another flow

## Home Page Policy

The Home Page free-question pipeline may be repaired independently.

Typical Home Page concerns include:

- Query Understanding
- entity and breed recognition
- intent and property recognition
- query construction
- statistical evidence alignment
- relevance filtering
- ranking
- answer composition
- latency and provider orchestration

However, a shared component MUST NOT be changed merely because it is also used by the Home Page if that change could alter the protected Crop Page behavior.

## Shared Components Rule

If a defect is located in a component shared by the Home Page and Crop Page:

1. First determine whether the proposed change can affect the Crop Page.
2. If it can affect the Crop Page, do **not** modify the shared behavior.
3. Prefer a Home-Page-specific branch, adapter, policy, rule, wrapper, or other isolated implementation.
4. Only a change proven to preserve the Crop Page contract may be considered, and preservation must be demonstrated by tests before acceptance.

## Regression Requirement

Every future change affecting shared research infrastructure must preserve the Crop Page as a baseline.

The required invariant is:

`Crop Page behavior before change == Crop Page behavior after change`

with respect to its established contracts, routing, inputs, outputs, research behavior, and user-visible semantics.

## Architectural Principle

The system has **one common scientific research foundation where appropriate, but the Crop Page entry/planning path is a protected boundary**.

The existence of shared downstream services does not authorize changes to the Crop Page path.

The objective is to improve the Home Page pipeline **without changing the Crop Page pipeline**.

## Consequence

Future investigations must explicitly classify every proposed modification as one of:

- **Home-only:** allowed when it does not touch Crop Page behavior.
- **Shared but behavior-preserving:** requires explicit proof through targeted regression tests.
- **Shared and potentially behavior-changing:** prohibited; isolate the Home Page behavior instead.

This ADR is the governing architectural constraint for subsequent work on the two research entry paths.
