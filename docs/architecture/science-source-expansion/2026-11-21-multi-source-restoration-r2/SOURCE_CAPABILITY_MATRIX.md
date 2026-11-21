# Source Capability Matrix

| source_key | Knowledge types (primary) | Domains | Query categories | Evidence types | Notes |
|---|---|---|---|---|---|
| openalex | SCHOLARLY_EVIDENCE | cross-domain scholarly | bibliographic search | articles/works | Internet-first default |
| crossref | SCHOLARLY_EVIDENCE | cross-domain scholarly | DOI/metadata search | bibliographic | Internet-first default |
| semantic_scholar | SCHOLARLY_EVIDENCE | cross-domain scholarly | paper search | articles | Internet-first default |
| consensus | SCHOLARLY_EVIDENCE | scholarly meta | explicit only | aggregated claims | Never auto-selected |
| fao_stat | STATISTICAL_OBSERVATION | agriculture statistics | QCL/dimensions | official stats | Consideration ≠ acceptance |
| agris | SCHOLARLY_EVIDENCE / agricultural lit | agriculture | local index | bibliographic rows | Bounded local corpus |
| dr_duke | CHEMICAL_ASSOCIATION / TRADITIONAL_USE | ethnobotany/phytochem | local index | ethnobot rows | Not clinical efficacy |
| gbif | TAXONOMIC_IDENTITY / BIODIVERSITY_OCCURRENCE | biodiversity/taxonomy | species match / checklist | taxon hierarchy | One source among many |
| organic_eprints | SCHOLARLY_EVIDENCE / practice | organic/agroecology | local OAI index | eprints | Relevance-gated |
| bhl | BIBLIOGRAPHIC_HERITAGE (+ taxonomy literature) | heritage biodiversity lit | REST name search | page/title records | Protected on taxonomy Qs |

Capability declarations for legacy keys use `legacyOperationalBasis` (ADR-023 §8.35.1). Governed seats carry onboarding contracts + CapVer references via registration.
