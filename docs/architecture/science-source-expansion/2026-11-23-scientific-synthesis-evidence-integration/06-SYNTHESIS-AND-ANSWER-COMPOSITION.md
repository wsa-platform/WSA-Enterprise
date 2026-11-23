# 06 — Synthesis and Answer Composition

## ScientificSynthesisIntegrator

Produces `researchMetadata.scientific_synthesis` with:
- supported_findings
- partial_findings
- contextual_differences
- genuine_conflicts
- insufficient_claims
- evidence_gaps
- synthesis_notes (EN/AR/TR/FR connective framing — not new facts)

## AnswerComposer changes

- Invokes integrator after claims/conflicts.
- Detailed explanation adds context note when contextual differences exist.
- Genuine conflicts keep existing conflict note behavior.
- Main answer body modes unchanged (list/process/range/comparison/explanatory).

## Integrity

Connecting notes never invent numeric results, citations, or causal upgrades.
