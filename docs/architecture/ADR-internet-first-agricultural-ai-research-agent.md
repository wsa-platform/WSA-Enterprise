# ADR — Internet-First Agricultural AI Research Agent

**Status:** APPROVED  
**Version:** 1.1  
**Scope:** WSA Enterprise Agricultural AI Research System  
**Primary Decision:** Internet-First Scientific Research  
**Maximum Implementation Stages:** 10

---

## 1. Decision

WSA Enterprise will implement an **Agricultural AI Research Agent** whose primary research path starts with the Internet and trusted external scientific/agricultural sources.

The WSA Knowledge Library is **not** the first search step. It acts as the platform's scientific memory, evidence repository, historical knowledge layer, and comparison layer.

The approved research flow is:

```text
User Question
    ↓
Agricultural AI Research Agent
    ↓
Query Understanding
    ↓
Research Planning
    ↓
Source Selection
    ↓
🌐 Internet-First Scientific Search
    ↓
Source Collection
    ↓
Scientific Source Validation
    ↓
Evidence Extraction / Normalization
    ↓
📚 WSA Knowledge Library
    ↓
Evidence Comparison / Merge
    ↓
Evidence Synthesis
    ↓
Scientific Answer + Citations + Source Links
    ↓
Verified Knowledge Persistence
```

## 2. Architectural Principles

1. **Internet First** is the approved research ordering.
2. External scientific/official sources are the primary starting point for research.
3. The WSA Library is the platform's **Knowledge Memory**, not the primary search engine.
4. The AI model is not the source of scientific truth; verified evidence is.
5. No fabricated sources, URLs, DOI values, authors, institutions, studies, or numerical claims.
6. When evidence is insufficient, the system must explicitly report insufficient evidence rather than invent an answer.
7. Conflicting evidence must be preserved with conditions, ranges, and source attribution rather than silently flattened.
8. New external knowledge should be persisted in the Library only after the required validation/evidence rules are satisfied.
9. Existing WSA AI, RAG, PostgreSQL, pgvector, Library, OpenAlex, Crossref, source registry, and validation infrastructure should be reused where appropriate.
10. New packages, databases, vector stores, or parallel technology stacks are not introduced without a demonstrated architectural need.
11. Plant AI Diagnosis remains completely independent from the Agricultural AI Research Agent and Scientific Knowledge Engine.
12. Existing completed work must not be deleted, rebuilt, or moved without explicit approval.
13. WordPress remains separate from WSA Enterprise backend development and is not modified as part of this architecture.

## 3. Generic Agricultural Scope

The Research Agent is not crop-specific. It must be designed for general agricultural scientific questions, including but not limited to:

- Plant production and crop cultivation.
- Irrigation and water management.
- Soil science.
- Fertilization and plant nutrition.
- Plant pests and diseases.
- Nutrient deficiencies.
- Crop productivity and yield.
- Varieties and genetic/agronomic characteristics.
- Animal production.
- Poultry and birds.
- Beekeeping and honey bees.
- Aquaculture and fish production.
- Animal feed and nutrition.
- Agricultural economics.
- Agricultural industries and processing.
- Agricultural research and scientific publications.
- Other agricultural scientific questions supported by reliable evidence.

## 4. Scientific Source Strategy

The system should support trusted source classes such as:

- Universities and agricultural faculties.
- Government agricultural institutions.
- Agricultural research centers.
- International agricultural organizations.
- Peer-reviewed scientific journals.
- Scientific research indexes and databases.
- Official plant protection and agricultural authorities.
- Other validated scientific/agricultural sources.

External providers must be integrated through adapter-style interfaces so additional sources can be added without rebuilding the core research engine.

Examples of future/source adapters include OpenAlex, Crossref, PubMed, AGRIS, FAO, IPPC, universities, research centers, government agencies, and specialized scientific databases, subject to source validation and implementation readiness.

## 5. Role of the WSA Library

The WSA Library provides:

- Scientific knowledge memory.
- Previously verified evidence.
- Historical knowledge.
- Comparison against newly discovered evidence.
- Deduplication and reuse.
- Knowledge growth over time.
- Traceability and provenance.

The Library can also help fill gaps when external research is incomplete, but this does not change the approved **Internet-First** ordering.

## 6. Research Agent Responsibilities

The Agricultural AI Research Agent is the top-level research orchestration layer. It is responsible for:

1. Understanding the user's question.
2. Identifying agricultural domain and entities.
3. Creating a research plan.
4. Selecting appropriate scientific source types.
5. Performing Internet-first research.
6. Collecting and organizing results.
7. Validating source quality and provenance.
8. Extracting and normalizing evidence.
9. Comparing external evidence with WSA Library knowledge.
10. Detecting and preserving conflicts.
11. Synthesizing evidence.
12. Producing a clear scientific answer with citations.
13. Persisting verified knowledge for future reuse.
14. Providing research trace/observability information where the UI supports it.

## 7. Scientific Knowledge Engine Relationship

The `AgriculturalScientificKnowledgeEngine` is the scientific evidence/knowledge substrate used by the Research Agent.

The existing `CropKnowledgeEngine` should become a thin adapter over the central knowledge capabilities rather than becoming a second independent research engine.

OpenAlex, Crossref, and future scientific providers are source adapters, not the central system brain.

## 8. Ten-Stage Implementation Plan

### Stage 1 — Foundation & Architecture

**Goal:** Establish and verify the architecture before expanding functionality.

Work includes:

- Review the existing WSA architecture and AI/RAG capabilities.
- Define Research Agent boundaries.
- Define Scientific Knowledge Engine boundaries.
- Preserve Plant AI Diagnosis independence.
- Reuse existing RAG, AI services, PostgreSQL, pgvector, Library, source registry, and validators.
- Avoid parallel or duplicate systems.

**Deliverable:** Approved architecture baseline and implementation foundation.

### Stage 2 — Query Understanding & Research Planning

Build the generic agricultural query-understanding and planning layer.

Capabilities include:

- Query understanding.
- Intent detection.
- Agricultural domain detection.
- Entity identification.
- Research-plan generation.
- Selection of appropriate research paths and source classes.

The system must handle questions across the full agricultural scope rather than only predefined crops.

**Deliverable:** Executable `Research Plan` for a generic agricultural question.

### Stage 3 — Multi-Source Internet-First Agricultural Search

Implement the external scientific search orchestration.

The system searches trusted Internet/scientific sources first and supports parallel source adapters where appropriate.

The adapter architecture must allow future providers to be added without changing the central orchestration logic.

**Deliverable:** Multi-source Internet-first research capability.

### Stage 4 — Evidence Collection & Scientific Validation

Transform search results into validated evidence.

Validation should consider, as applicable:

- Source identity.
- Source authority.
- Relevance.
- Publication metadata.
- DOI or equivalent identifiers when available.
- Original source URL.
- Publication date.
- Source type.
- Evidence quality.

The system must never fabricate scientific provenance.

**Deliverable:** Validated evidence set with provenance.

### Stage 5 — Evidence Synthesis & AI Answer Composition

Combine validated evidence and produce the scientific answer.

The synthesis layer must:

- Merge compatible evidence.
- Preserve source conditions.
- Preserve conflicting findings.
- Represent ranges where appropriate.
- Attribute claims to their supporting sources.
- Generate a clear answer with citations and source links.

The AI performs analysis and organization; it does not replace source evidence.

**Deliverable:** Evidence-grounded scientific answer.

### Stage 6 — Knowledge Persistence & Library Growth

Persist verified knowledge into the WSA Library as structured knowledge/evidence units.

Persist, as applicable:

- Claims.
- Evidence.
- Sources.
- Citations.
- URLs.
- Metadata.
- Conditions.
- Units.
- Ranges.
- Confidence/evidence metadata.
- Provenance.
- Dates and freshness information.

Previously verified knowledge should be reusable without treating an old AI-generated answer as a scientific source.

**Deliverable:** Continuously growing scientific knowledge memory.

### Stage 7 — Research Trace & Observability

Expose or record the research process sufficiently for transparency and debugging.

The system should be able to represent information such as:

```text
Understanding Question
        ↓
Research Plan
        ↓
Sources Selected
        ↓
Search Results
        ↓
Validated Evidence
        ↓
Library Comparison
        ↓
Evidence Synthesis
        ↓
Final Answer
```

Observability should include useful metadata such as adapters/discoverers used, validation outcomes, failures, and evidence counts where supported.

**Deliverable:** Traceable and observable research workflow.

### Stage 8 — Plant AI Diagnosis Engine & Independent Mobile Track

Develop Plant AI Diagnosis as a completely separate technical track.

Plant AI Diagnosis may include:

- Plant image analysis.
- Vision-model inference.
- Candidate disease/pest identification.
- Nutrient-deficiency analysis where supported.
- Confidence estimation.
- Alternative candidate diagnoses when ambiguity exists.
- Additional-information requests when the image is insufficient.
- Evidence-based diagnosis/control knowledge within its own independent architecture.

The mobile client will use Flutter for Android and iOS, with its own diagnosis workflow.

**Critical independence rule:** Plant AI Diagnosis must not depend on the Agricultural AI Research Agent, and the Research Agent must not depend on Plant AI Diagnosis.

**Deliverable:** Independent Plant AI Diagnosis system and mobile application track.

### Stage 9 — Integration & Comprehensive Testing

Test the complete system across generic agricultural domains.

Research Agent test coverage should include, at minimum:

- Crop cultivation.
- Irrigation.
- Fertilization.
- Soil.
- Pests and diseases.
- Nutrient deficiencies.
- Productivity/yield.
- Varieties.
- Animal production.
- Poultry.
- Beekeeping.
- Aquaculture.
- Feed/nutrition.
- Agricultural research queries.
- Insufficient-evidence scenarios.
- Conflicting-source scenarios.
- Source failure/fallback scenarios.

Plant AI Diagnosis is tested independently.

**Deliverable:** Full regression and integration test suite with verified behavior.

### Stage 10 — Production Hardening & Expansion

Prepare the system for production and controlled expansion.

Includes:

- Performance hardening.
- Security review.
- Failure handling.
- Source-validation hardening.
- Observability.
- Regression protection.
- Freshness/revalidation mechanisms.
- Documentation.
- Migration/compatibility guidance.
- Additional scientific source adapters.
- Controlled future expansion of agricultural domains.

Potential future source integrations include PubMed, AGRIS, FAO, IPPC, additional universities, research centers, government sources, and specialized scientific indexes, subject to validation.

**Deliverable:** Production-ready Agricultural AI Research platform with extensible scientific source architecture.

## 9. Parallel Development Strategy

Stages belonging to the Research Agent and Plant AI Diagnosis can be developed in parallel when their interfaces and independence boundaries are preserved.

The two tracks must not create hidden runtime dependencies.

```text
RESEARCH TRACK
1 → 2 → 3 → 4 → 5 → 6 → 7 → 9 → 10

DIAGNOSIS TRACK
                         8 → 9 → 10
```

Parallel development is intended to reduce elapsed project time without reducing validation or architectural discipline.

## 10. Completion & Change-Control Rules

Every implementation stage follows:

```text
Implementation
    ↓
Tests
    ↓
Review
    ↓
Approval
    ↓
Checkpoint
    ↓
Commit / Push
```

No stage is considered complete merely because code exists. It must pass the required tests and review.

Existing completed functionality must remain intact.

No deletion, rebuild, migration, or architectural replacement of completed systems is permitted without explicit approval.

## 11. Final Architectural Principle

The WSA Agricultural AI Research platform follows this permanent high-level principle:

> **Internet First → Scientific Validation → WSA Knowledge Library → Evidence Synthesis → Answer + Citations → Verified Knowledge Persistence**

The AI organizes and reasons over evidence; **scientifically validated sources remain the source of truth**.
