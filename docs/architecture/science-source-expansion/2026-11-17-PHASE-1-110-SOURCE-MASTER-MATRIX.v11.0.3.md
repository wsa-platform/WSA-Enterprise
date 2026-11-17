# Phase 1 - 110-Source Master Status Matrix (Rev 11.0.3)

- Generated (UTC): 2026-11-17T19:51:36Z
- HEAD: `547563e5d81d512aedc01bcd2e57dd73868410c9`
- Membership: `110.v1` from `ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json` (sha256 `94b586ee38f2b3dc8b2e0df70e3be0d10f5310f928ad8e2cb1568c280467f69d`)

> **The full JSON registry is authoritative:** `2026-11-17-PHASE-1-110-SOURCE-MASTER-STATUS-REGISTRY.v11.0.3.json`. This Markdown is a derived summary; any discrepancy is resolved in favor of the JSON. Reconciliation proof: `2026-11-17-PHASE-1-110-SOURCE-RECONCILIATION.v11.0.3.json`.

## Claim guard

- Membership VERIFIED means the seat exists in the 110.v1 register (sha256 matched). It is not classification, capability, endpoint verification, eligibility, or activation.
- No source is assigned SCIENTIFIC_INTEGRATION_VERIFIED: section 5.12.1 minimum evidence is not demonstrated per source in this registry.
- NOT_TESTED is not FAILED and not VERIFIED. UNKNOWN means evidence may exist but is not source-attributed here.
- Live connectivity values are historical Rev 11.0.1 results (2026-11-17T19:08:22Z); no new probes were run.
- Shared-component tests are not copied across sources (section 5.8).

## 1. Headline (section 23.5)

| Metric | Value |
|---|---|
| Source identities (unique, from register) | 110 |
| Membership VERIFIED | 110 |
| Scientifically integrated (section 5.12.1 minimum evidence) | 0 |
| Contract verified (historical, not re-run) | 5 (G1-01, G1-02, G1-17, G5-04, G6-20) |
| Live connectivity VERIFIED (Rev 11.0.1 probe, historical) | 4 (G1-01, G1-02, G5-04, G6-20) |
| Contract-verified only, no live proof | 1 (G1-17 Dr. Duke - scope-limited projection) |
| Live connectivity NOT_TESTED | 105 |
| Partial scientific integration (PARTIALLY_VERIFIED) | 5 |
| Seat-specific blockers | 1 (G2-26) |
| Historical integration claims preserved | G5-04 GBIF, G6-20 BHL (SCIENTIFIC_SEARCH_INTEGRATION_VERIFIED); G1-17 (limited local projection) |

## 2. Group distribution

| Group | Seats |
|---|---|
| G1 | 22 |
| G2 | 26 |
| G3 | 14 |
| G4 | 7 |
| G5 | 19 |
| G6 | 22 |
| **Total** | 110 |

## 3. Summary by status dimension (section 5.3)

| Dimension | CONTRACT_VERIFIED_ONLY | LIVE_CONNECTIVITY_VERIFIED | NOT_IMPLEMENTED | NOT_TESTED | NOT_VERIFIED | PARTIALLY_VERIFIED | UNKNOWN | VERIFIED |
|---|---|---|---|---|---|---|---|---|
| source_identity_status |  |  |  |  | 105 | 5 |  |  |
| adr_binding_status |  |  |  |  |  |  |  | 110 |
| membership_status |  |  |  |  |  |  |  | 110 |
| registry_status |  |  | 105 |  |  |  |  | 5 |
| capability_status |  |  |  |  | 105 | 5 |  |  |
| access_family_status |  |  |  |  |  | 5 | 105 |  |
| adapter_status |  |  | 105 |  |  |  |  | 5 |
| contract_test_status |  |  |  | 105 |  |  |  | 5 |
| live_connectivity_status | 1 | 4 |  | 105 |  |  |  |  |
| csq_integration_status |  |  |  | 105 |  | 5 |  |  |
| query_translation_status |  |  |  | 105 |  |  | 5 |  |
| normalization_status |  |  |  | 105 |  |  | 5 |  |
| deduplication_status |  |  |  | 105 |  |  | 5 |  |
| ranking_status |  |  |  | 105 |  |  | 5 |  |
| provenance_status |  |  |  | 105 |  | 1 | 4 |  |
| evidence_pipeline_status |  |  |  | 105 |  |  | 5 |  |
| answer_traceability_status |  |  |  | 105 |  |  | 5 |  |
| integration_test_status |  |  |  | 105 |  | 5 |  |  |

Each row sums to 110.

## 4. Scientific integration fields (section 5.12.1)

| Field | BLOCKED | NOT_TESTED | PARTIALLY_VERIFIED | UNKNOWN |
|---|---|---|---|---|
| orchestrator_binding_status |  | 105 | 2 | 3 |
| csq_query_translation_status |  | 105 |  | 5 |
| source_specific_semantic_preservation_status |  | 105 |  | 5 |
| retrieval_execution_status |  | 105 | 5 |  |
| response_validation_status |  | 105 |  | 5 |
| evidence_pipeline_status |  | 105 |  | 5 |
| answer_traceability_status |  | 105 |  | 5 |
| scientific_integration_defect | 1 |  |  | 109 |

`scientific_identity_test_reference` and `scientific_integration_next_action` are populated for all 110 (explicit `NOT_TESTED`/`UNKNOWN` or a reference); see JSON.

## 5. Access-family distribution

| Access family | Seats |
|---|---|
| BULK_DATASET | 1 |
| OAI_PMH | 1 |
| REST_API | 2 |
| STATIC_DATASET | 1 |
| UNKNOWN | 105 |

Family is mapped only where the runtime catalog / `ic_record` asserts it (5 seats). EXIT-08/10 matrices are family-level and carry no per-seat mapping, so the other 105 stay `UNKNOWN`. Documentary access hints from the 2026-10-13 IC review are kept in JSON as `documentary_access_evidence_state` and are not verification.

## 6. Seats with source-specific evidence (the 5 runtime-governed seats)

| Seat | Name | source_key | Family | Live (Rev 11.0.1) | HTTP | Contract | Sci. integration | Historical claim |
|---|---|---|---|---|---|---|---|---|
| G1-01 | FAO AGRIS | agris | BULK_DATASET | LIVE_CONNECTIVITY_VERIFIED | 200 | VERIFIED_HISTORICAL_NOT_RERUN | PARTIALLY_VERIFIED | - |
| G1-02 | Organic Eprints | organic_eprints | OAI_PMH | LIVE_CONNECTIVITY_VERIFIED | 200 | VERIFIED_HISTORICAL_NOT_RERUN | PARTIALLY_VERIFIED | - |
| G1-17 | Dr. Duke's Databases | dr_duke | STATIC_DATASET | CONTRACT_VERIFIED_ONLY | - | VERIFIED_HISTORICAL_NOT_RERUN | PARTIALLY_VERIFIED | SCIENTIFIC_SEARCH_INTEGRATION_VERIFIED_LIMITED_LOCAL_PROJECTION |
| G5-04 | GBIF | gbif | REST_API | LIVE_CONNECTIVITY_VERIFIED | 200 | VERIFIED_HISTORICAL_NOT_RERUN | PARTIALLY_VERIFIED | SCIENTIFIC_SEARCH_INTEGRATION_VERIFIED |
| G6-20 | Biodiversity Heritage Library (BHL) | bhl | REST_API | LIVE_CONNECTIVITY_VERIFIED | 200 | VERIFIED_HISTORICAL_NOT_RERUN | PARTIALLY_VERIFIED | SCIENTIFIC_SEARCH_INTEGRATION_VERIFIED |

## 7. Live-connectivity keys that are NOT 110.v1 seats (preserved, not counted)

| source_key | Live status | HTTP | Note |
|---|---|---|---|
| openalex | LIVE_CONNECTIVITY_VERIFIED | 200 |  |
| crossref | LIVE_CONNECTIVITY_VERIFIED | 200 |  |
| semantic_scholar | BLOCKED_EXTERNAL_SERVICE | 429 | API key present; provider still returned 429 |
| fao_stat | BLOCKED_CREDENTIALS | 403 | username/password present in config; HTTP 403 — authorization/permission or credential validity issue; values not printed |
| consensus | BLOCKED_CREDENTIALS | - | CONSENSUS_API_KEY absent — probe skipped |

These are `LEGACY_STAGE3` runtime registrations with `adr_id = null`. No seat was invented for them.

## 8. Dual-pair seats (10 pairs, 20 separate identities)

| Label | Seat A | Seat B |
|---|---|---|
| FlowerBase | G1-12 | G6-01 |
| HortDB V1.0 | G1-13 | G6-03 |
| Tropicals.cn | G1-14 | G6-02 |
| sCentInDB | G1-15 | G6-04 |
| AromaDb | G1-16 | G6-05 |
| Dr. Duke's | G1-17 | G6-06 |
| MPNS | G1-18 | G6-07 |
| FNCD | G1-19 | G6-08 |
| FEAtl | G1-20 | G6-09 |
| CRFG | G1-21 | G6-10 |

Both seats of every pair remain separate rows. Dr. Duke live/contract evidence is attributed to G1-17 only (runtime catalog binding); G6-06 is not credited.

## 9. Blocker register (summary)

| Blocker | Seats |
|---|---|
| NO_RUNTIME_REGISTRATION_OR_ADAPTER | 105 |
| IDENTITY_NOT_VERIFIED | 105 |
| CAPABILITY_NOT_VERIFIED | 105 |
| FULL_ODS_INGEST_NOT_CLAIMED (LocalIndex persist completed_packages=124; FTS deferred) | 1 |
| OPERATIONAL_INTEGRATION_PARTIAL (historical; exact remaining Phase 1 conditions not re-audited here) | 1 |
| SCOPE_LIMITED_PROJECTION (OD-G1-17); live endpoint not re-probed in Rev 11.0.1 | 1 |
| SEAT_SPECIFIC_BLOCKER P1-BLK-2026-11-11-G2-26-01 (no GB-IC-01 scope / no persisted IC artifact) | 1 |
| DUAL_PAIR_PEER_G1-17_EVIDENCE_NOT_ATTRIBUTED_TO_THIS_SEAT | 1 |

## 10. Reconciliation

- Result: **RECONCILED** - 25/25 checks passed (count = 110, 0 duplicate IDs, 0 invented, 0 missing, order preserved).
- Registry SHA-256 at reconciliation: `218b05b114b17ee81bf1e2d68b63db24629f39c2d431c5a8b2623b742ad3b891`
- Full proof: `2026-11-17-PHASE-1-110-SOURCE-RECONCILIATION.v11.0.3.json`

## 11. Compact seat matrix (110 rows)

Legend: Id=source_identity, Reg=runtime registry, Ad=adapter, Ct=contract tests, Live=live connectivity, Sci=scientific integration.

| Seat | Name | Id | Reg | Ad | Ct | Live | Sci | Dual | Seat blocker |
|---|---|---|---|---|---|---|---|---|---|
| G1-01 | FAO AGRIS | P | V | V | V | LIVE_OK | P |  |  |
| G1-02 | Organic Eprints | P | V | V | V | LIVE_OK | P |  |  |
| G1-03 | Turkish Journal of Agriculture and Forestry | NV | NI | NI | NT | NT | NT |  |  |
| G1-04 | AgroSpace | NV | NI | NI | NT | NT | NT |  |  |
| G1-05 | AgriRxiv | NV | NI | NI | NT | NT | NT |  |  |
| G1-06 | AgroPortal | NV | NI | NI | NT | NT | NT |  |  |
| G1-07 | YYU Journal of Agricultural Sciences | NV | NI | NI | NT | NT | NT |  |  |
| G1-08 | J. Korean Soc. Int. Agriculture | NV | NI | NI | NT | NT | NT |  |  |
| G1-09 | Int. J. Horticultural Science | NV | NI | NI | NT | NT | NT |  |  |
| G1-10 | JISHS / eJHS | NV | NI | NI | NT | NT | NT |  |  |
| G1-11 | Sarhad Journal of Agriculture | NV | NI | NI | NT | NT | NT |  |  |
| G1-12 | FlowerBase | NV | NI | NI | NT | NT | NT | dual:G6-01 |  |
| G1-13 | HortDB V1.0 | NV | NI | NI | NT | NT | NT | dual:G6-03 |  |
| G1-14 | Tropicals.cn | NV | NI | NI | NT | NT | NT | dual:G6-02 |  |
| G1-15 | sCentInDB | NV | NI | NI | NT | NT | NT | dual:G6-04 |  |
| G1-16 | AromaDb | NV | NI | NI | NT | NT | NT | dual:G6-05 |  |
| G1-17 | Dr. Duke's Databases | P | V | V | V | CONTRACT_ONLY | P | dual:G6-06 |  |
| G1-18 | MPNS — Kew | NV | NI | NI | NT | NT | NT | dual:G6-07 |  |
| G1-19 | Fruit and Nut Cultivars Database | NV | NI | NI | NT | NT | NT | dual:G6-08 |  |
| G1-20 | FEAtl | NV | NI | NI | NT | NT | NT | dual:G6-09 |  |
| G1-21 | CRFG | NV | NI | NI | NT | NT | NT | dual:G6-10 |  |
| G1-22 | GrainGenes | NV | NI | NI | NT | NT | NT |  |  |
| G2-01 | Poultry Science | NV | NI | NI | NT | NT | NT |  |  |
| G2-02 | Journal of Applied Poultry Research | NV | NI | NI | NT | NT | NT |  |  |
| G2-03 | European Poultry Science | NV | NI | NI | NT | NT | NT |  |  |
| G2-04 | Brazilian Journal of Poultry Science | NV | NI | NI | NT | NT | NT |  |  |
| G2-05 | Journal of Poultry Science | NV | NI | NI | NT | NT | NT |  |  |
| G2-06 | Journal of Animal Science and Biotechnology | NV | NI | NI | NT | NT | NT |  |  |
| G2-07 | Animal Frontiers | NV | NI | NI | NT | NT | NT |  |  |
| G2-08 | Veterinary and Animal Science | NV | NI | NI | NT | NT | NT |  |  |
| G2-09 | Turkish Journal of Veterinary & Animal Sciences | NV | NI | NI | NT | NT | NT |  |  |
| G2-10 | Journal of Animal Production (Hayvansal Üretim) | NV | NI | NI | NT | NT | NT |  |  |
| G2-11 | Aquaculture and Fisheries | NV | NI | NI | NT | NT | NT |  |  |
| G2-12 | Turkish Journal of Fisheries and Aquatic Sciences | NV | NI | NI | NT | NT | NT |  |  |
| G2-13 | Marine and Fishery Sciences (MAFIS) | NV | NI | NI | NT | NT | NT |  |  |
| G2-14 | AIMS Animal Science | NV | NI | NI | NT | NT | NT |  |  |
| G2-15 | Andalasian Livestock | NV | NI | NI | NT | NT | NT |  |  |
| G2-16 | Hayvan Bilimi ve Ürünleri Dergisi (JASP) | NV | NI | NI | NT | NT | NT |  |  |
| G2-17 | REMVT | NV | NI | NI | NT | NT | NT |  |  |
| G2-18 | Farm Animal Health and Nutrition | NV | NI | NI | NT | NT | NT |  |  |
| G2-19 | Animal Bioscience | NV | NI | NI | NT | NT | NT |  |  |
| G2-20 | Aquaculture Journal | NV | NI | NI | NT | NT | NT |  |  |
| G2-21 | Aquaculture Reports | NV | NI | NI | NT | NT | NT |  |  |
| G2-22 | LIVEMAP | NV | NI | NI | NT | NT | NT |  |  |
| G2-23 | LivestockDev | NV | NI | NI | NT | NT | NT |  |  |
| G2-24 | IDEAL Project | NV | NI | NI | NT | NT | NT |  |  |
| G2-25 | CIRAD Portals | NV | NI | NI | NT | NT | NT |  |  |
| G2-26 | Aquaculture, Fish and Fisheries | NV | NI | NI | NT | NT | NT |  | G2-26 blocker |
| G3-01 | APIS | NV | NI | NI | NT | NT | NT |  |  |
| G3-02 | Uludag Bee Journal | NV | NI | NI | NT | NT | NT |  |  |
| G3-03 | Journal of Apicultural Science | NV | NI | NI | NT | NT | NT |  |  |
| G3-04 | Frontiers in Bee Science | NV | NI | NI | NT | NT | NT |  |  |
| G3-05 | OSMIA | NV | NI | NI | NT | NT | NT |  |  |
| G3-06 | EUNK | NV | NI | NI | NT | NT | NT |  |  |
| G3-07 | Apidologie | NV | NI | NI | NT | NT | NT |  |  |
| G3-08 | USDA-ARS Carl Hayden Honey Bee Research Center | NV | NI | NI | NT | NT | NT |  |  |
| G3-09 | USDA-ARS Bee Research Laboratory — Beltsville | NV | NI | NI | NT | NT | NT |  |  |
| G3-10 | Harry H. Laidlaw Jr. Honey Bee Research Facility — UC Davis | NV | NI | NI | NT | NT | NT |  |  |
| G3-11 | University of Minnesota Bee Lab | NV | NI | NI | NT | NT | NT |  |  |
| G3-12 | Washington State University Bee Program | NV | NI | NI | NT | NT | NT |  |  |
| G3-13 | MSPB | NV | NI | NI | NT | NT | NT |  |  |
| G3-14 | UrBAN | NV | NI | NI | NT | NT | NT |  |  |
| G4-01 | SilkBase | NV | NI | NI | NT | NT | NT |  |  |
| G4-02 | KAIKObase | NV | NI | NI | NT | NT | NT |  |  |
| G4-03 | Silkworm Gene Resources Database (SGRDB / NAAS) | NV | NI | NI | NT | NT | NT |  |  |
| G4-04 | SilkDB / Silkworm Knowledgebase | NV | NI | NI | NT | NT | NT |  |  |
| G4-05 | International Sericultural Commission (ISC / INSERCO) | NV | NI | NI | NT | NT | NT |  |  |
| G4-06 | Sericologia | NV | NI | NI | NT | NT | NT |  |  |
| G4-07 | Queen Sirikit Department of Sericulture (QSDS) | NV | NI | NI | NT | NT | NT |  |  |
| G5-01 | InsectBrainDatabase (IBdb) | NV | NI | NI | NT | NT | NT |  |  |
| G5-02 | AGRICOLA (USDA/NAL) | NV | NI | NI | NT | NT | NT |  |  |
| G5-03 | ELIE — Entomological Label Information Extraction | NV | NI | NI | NT | NT | NT |  |  |
| G5-04 | GBIF | P | V | V | V | LIVE_OK | P |  |  |
| G5-05 | Journal of Insect Science (JIS) | NV | NI | NI | NT | NT | NT |  |  |
| G5-06 | npj Entomology | NV | NI | NI | NT | NT | NT |  |  |
| G5-07 | Insects (MDPI) | NV | NI | NI | NT | NT | NT |  |  |
| G5-08 | Current Research in Insect Science | NV | NI | NI | NT | NT | NT |  |  |
| G5-09 | Frontiers in Insect Science | NV | NI | NI | NT | NT | NT |  |  |
| G5-10 | Florida Entomologist | NV | NI | NI | NT | NT | NT |  |  |
| G5-11 | Egyptian Academic Journal of Biological Sciences A — Entomology | NV | NI | NI | NT | NT | NT |  |  |
| G5-12 | PhytoFrontiers™ | NV | NI | NI | NT | NT | NT |  |  |
| G5-13 | Phytopathology Research | NV | NI | NI | NT | NT | NT |  |  |
| G5-14 | Tropical Plant Pathology | NV | NI | NI | NT | NT | NT |  |  |
| G5-15 | BMC Plant Biology | NV | NI | NI | NT | NT | NT |  |  |
| G5-16 | Pathogens (MDPI) | NV | NI | NI | NT | NT | NT |  |  |
| G5-17 | Journal of Plant Protection and Pathology — Mansoura University | NV | NI | NI | NT | NT | NT |  |  |
| G5-18 | Iranian Journal of Plant Pathology | NV | NI | NI | NT | NT | NT |  |  |
| G5-19 | Plant Pathology Journal — ANSInet | NV | NI | NI | NT | NT | NT |  |  |
| G6-01 | FlowerBase | NV | NI | NI | NT | NT | NT | dual:G1-12 |  |
| G6-02 | Tropicals.cn | NV | NI | NI | NT | NT | NT | dual:G1-14 |  |
| G6-03 | HortDB V1.0 | NV | NI | NI | NT | NT | NT | dual:G1-13 |  |
| G6-04 | sCentInDB | NV | NI | NI | NT | NT | NT | dual:G1-15 |  |
| G6-05 | AromaDb | NV | NI | NI | NT | NT | NT | dual:G1-16 |  |
| G6-06 | Dr. Duke's Phytochemical and Ethnobotanical Databases | NV | NI | NI | NT | NT | NT | dual:G1-17 |  |
| G6-07 | MPNS – Kew | NV | NI | NI | NT | NT | NT | dual:G1-18 |  |
| G6-08 | Fruit and Nut Cultivars Database (FNCD) | NV | NI | NI | NT | NT | NT | dual:G1-19 |  |
| G6-09 | FEAtl – Fruit Expression Atlas | NV | NI | NI | NT | NT | NT | dual:G1-20 |  |
| G6-10 | CRFG – California Rare Fruit Growers | NV | NI | NI | NT | NT | NT | dual:G1-21 |  |
| G6-11 | Chrysanthemum Genome Database (CGD) | NV | NI | NI | NT | NT | NT |  |  |
| G6-12 | OfIR – Osmanthus fragrans Information Resource | NV | NI | NI | NT | NT | NT |  |  |
| G6-13 | PGRDEU | NV | NI | NI | NT | NT | NT |  |  |
| G6-14 | GDR – Genome Database for Rosaceae | NV | NI | NI | NT | NT | NT |  |  |
| G6-15 | PeachMD | NV | NI | NI | NT | NT | NT |  |  |
| G6-16 | Citrus Genome Database (CGD) | NV | NI | NI | NT | NT | NT |  |  |
| G6-17 | HarnesstomDB | NV | NI | NI | NT | NT | NT |  |  |
| G6-18 | INIA Peru – Banco de Germoplasma | NV | NI | NI | NT | NT | NT |  |  |
| G6-19 | BINA – Paraguay | NV | NI | NI | NT | NT | NT |  |  |
| G6-20 | Biodiversity Heritage Library (BHL) | P | V | V | V | LIVE_OK | P |  |  |
| G6-21 | New York Botanical Garden (NYBG) | NV | NI | NI | NT | NT | NT |  |  |
| G6-22 | PG Syllabus – UBKV | NV | NI | NI | NT | NT | NT |  |  |

Membership is VERIFIED and ADR binding is VERIFIED for all 110 (omitted from the compact table).

## 12. Sources of truth

| Role | Path |
|---|---|
| Authoritative membership | docs/architecture/science-source-expansion/ADR-023-CURRENT-MEMBERSHIP-REGISTER.v2.json |
| Prior membership-only matrix | docs/architecture/science-source-expansion/2026-11-11-PHASE-1-SOURCE-MATRIX.v110.json |
| Live connectivity (Rev 11.0.1) | docs/architecture/science-source-expansion/2026-11-17-PHASE-1-EXIT-08-LIVE-CONNECTIVITY.v11.0.1.json |
| Credential requirements | docs/architecture/science-source-expansion/2026-11-17-PHASE-1-EXIT-08-CREDENTIAL-REQUIREMENTS.v11.0.1.json |
| EXIT-08 family matrix | docs/architecture/science-source-expansion/2026-11-11-PHASE-1-EXIT-08-FAMILY-VERIFICATION-MATRIX.v10.6.1.json |
| EXIT-10 verified family | docs/architecture/science-source-expansion/2026-11-12-PHASE-1-EXIT-10-VERIFIED-FAMILY.v10.10.json |
| Historical IC review (109 seats) | docs/architecture/science-source-expansion/2026-10-13-PHASE-1-IC-EVIDENCE-REVIEW.v1.json |
| Full registry (authoritative) | docs/architecture/science-source-expansion/2026-11-17-PHASE-1-110-SOURCE-MASTER-STATUS-REGISTRY.v11.0.3.json |
| Reconciliation | docs/architecture/science-source-expansion/2026-11-17-PHASE-1-110-SOURCE-RECONCILIATION.v11.0.3.json |
