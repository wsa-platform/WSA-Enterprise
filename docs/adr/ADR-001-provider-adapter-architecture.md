# ADR-001: Universal Agricultural Intelligence Provider & Adapter Architecture

**Status:** Accepted / Implemented
**Date:** 2026-09-09
**Branch:** `phase-18-m18-ai-marketing-communications`

## Context

WSA Enterprise’s agricultural research stack (Query Understanding → Planning → Multi-source scientific search → Validation → Synthesis) is scholarly-first. Product direction requires a **Universal Agricultural Answer Engine** that can combine scientific indices, general web evidence, disease models, environmental data, MCP tools, and execution adapters — without crop-specific hard-coding and without collapsing web eligibility when science is insufficient.

## Decision

1. Introduce a **provider/adapter architecture** under `App\Services\Agriculture\Intelligence\` with:
   - `AgriculturalProviderRegistry` (id, name, type, capabilities, version, priority, timeout, health, auth, confidence meta, evidence capability, limitations, config, enabled)
   - Integration contracts: `AgriculturalProviderInterface`, `WebSearchProviderInterface`, `PlantDiseaseAnalysisProviderInterface`
   - **Canonical Agricultural Result** DTO (provider-agnostic)
2. **Reuse** existing Semantic Scholar / OpenAlex / Crossref adapters via `ScientificAdapterBridgeProvider` — do not duplicate SS.
3. **FAO/FAOSTAT** is a real `ScientificSourceAdapterInterface` implementation (`FaoStatScientificSourceAdapter`) using `https://fenixservices.fao.org/faostat/api/v1`. Codes are never fabricated; invalid lookups are skipped.
4. **Web search is a separate family** from scholarly sources. Without `WEB_SEARCH_ENABLED` + key + endpoint → `NOT_CONFIGURED` (no fake results).
5. Disease / MCP / FieldSense / OctoPus adapters are implemented as REST abstractions; missing infra → `NOT_CONFIGURED` / `BLOCKED` (OctoPus license not assumed commercial-OK).
6. Open-Meteo is the **primary weather** provider; MCP adapters do not duplicate weather logic.
7. Normalization services: Agricultural / Web / Disease / Environmental + safe unit normalization (original + normalized, or refuse unsafe conversion).
8. Feature flags and secrets live in `config/agricultural_intelligence.php` / env — never in code.

## Consequences

- New architecture coexists with Stage 3 scientific pipeline; ClaimEvidenceMatcher / ASVS / RelevanceGate / Directness remain strict.
- Registry-driven selection is capability-based (no potato/tomato/cucumber branches).
- Providers fail in isolation; orchestrator continues.

## Implementation map

| Component | Location |
|-----------|----------|
| Registry | `Intelligence/Registry/AgriculturalProviderRegistry.php` |
| Orchestrator | ADR-002 / `UniversalAnswerOrchestrator` |
| FAO | `Intelligence/Adapters/Scientific/FaoStatScientificSourceAdapter.php` |
| Web | `Intelligence/Adapters/Web/*` |
| Disease | `Intelligence/Adapters/Disease/*` |
| Environmental / MCP | `Intelligence/Adapters/Environmental/*` |
| OctoPus | `Intelligence/Adapters/Execution/OctoPusExecutionAdapter.php` |
| Config | `config/agricultural_intelligence.php` |

## Honest status

| Provider | Status |
|----------|--------|
| OpenAlex / Crossref / Semantic Scholar | Implemented (reused) |
| FAOSTAT | Implemented (requires `FAO_ENABLED` + valid `item_code`) |
| Web search | Abstraction + HTTP when configured; **NOT_CONFIGURED** by default |
| Open-Meteo | Implemented (needs lat/lon) |
| Disease adapters | REST stubs; **NOT_CONFIGURED** without endpoints |
| AgriSignal / Agriculture MCP | **NOT_CONFIGURED** without endpoints |
| FieldSense | **NOT_CONFIGURED** without endpoint |
| OctoPus | **BLOCKED** until `OCTOPUS_LICENSE_STATUS` is `permitted` or `internal_ok` |
