# ADR: Internet-First Agricultural AI Research Agent

**Status:** Accepted  
**Date:** September 2026  
**Stage:** 1 — Foundation & Architecture

## Context

WSA Enterprise requires a generic agricultural scientific research capability that discovers verified evidence from external scholarly sources first, persists findings in the WSA Knowledge Library as memory, and orchestrates research through a dedicated agent layer without coupling to crop-specific UI flows or Plant AI Diagnosis.

## Decision

1. **Internet-First Scientific Research** is the primary discovery strategy.
2. **External scientific/official sources** (OpenAlex, Crossref, future adapters) are searched **before** library discovery.
3. The **WSA Knowledge Library** is **not** the primary first-search source. It serves as:
   - Knowledge memory
   - Evidence repository
   - Historical knowledge
   - Comparison layer
   - Enrichment layer
   - Gap-fill layer
   - Reuse/deduplication layer
4. **`AgriculturalResearchAgent`** is the top-level research orchestration layer.
5. **`ResearchPlanner`** performs deterministic v1 research planning.
6. **`AgriculturalScientificKnowledgeEngine`** is the central scientific evidence/knowledge substrate.
7. **`CropKnowledgeEngine`** functionality is preserved and evolved behind the knowledge engine.
8. OpenAlex and Crossref are **source adapters**, not the central research engine.
9. **Plant AI Diagnosis** remains completely independent.
10. Existing AI/RAG/PostgreSQL/pgvector infrastructure is reused. No parallel stacks.

## Research Flow

```
User Query
    → AgriculturalResearchAgent
    → ResearchPlanner
    → AgriculturalScientificKnowledgeEngine
    → External Scientific Search (OpenAlex, Crossref, …)
    → Source Validation
    → Evidence Extraction
    → WSA Library Memory (recall, enrichment, gap-fill)
    → Evidence Comparison/Merge
    → Final Research Context
```

## Consequences

- Library-stored sections may be recalled before discovery when already verified (memory).
- New discovery runs external providers before library discoverers.
- Crop farming-needs and generic agricultural queries share the same agent architecture.
- Future providers (PubMed, AGRIS, FAO, etc.) plug in as discoverer adapters without rewriting the agent.

## Out of Scope (Stage 1)

- Database schema changes
- Plant AI Diagnosis changes
- WordPress changes
- New packages (Laravel AI SDK, Scout, Meilisearch, etc.)
- Broad UI work
