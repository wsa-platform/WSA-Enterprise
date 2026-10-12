# ADR-023 Phase 1 — Source-by-Source Implementation Matrix (109 seats)

**Status:** PHASE 1 ACCOUNTABILITY ARTIFACT — not a runtime registry, not membership authority, not acceptance.  
**Date:** 2026-10-12  
**Governance:** [`ADR-023-scientific-source-expansion.md`](../ADR-023-scientific-source-expansion.md) §8.24–§8.25  
**Membership input:** `docs/architecture/science-source-expansion/ADR-023-109-MEMBERSHIP-REGISTER.v1.json` (`membership_revision` `109.v1`, Git blob `af67418c1c070c6e53ffb3ab1eef62890149c4b7` at HEAD `ba24abda`). Rows are generated from the register in register order; no seat was added, removed, renamed, merged, or bound.  

Membership remains governed by D-09 and the register; this matrix only records, per seat, which Phase 1 artifacts exist and what evidence supports them. Membership ≠ identity binding ≠ capability evidence ≠ adapter ≠ acceptance ≠ activation (D-10 invariants). IMPLEMENTED ≠ VERIFIED ≠ ACTIVE.

## Summary (2026-10-12)

| Category | Seats | Evidence |
|---|---|---|
| Membership CURRENT | 109 | Register; `Adr023109MembershipRegisterContractTest` |
| Verified access/protocol family (per-seat IC artifact) | 0 | No IC record is written by production code; IC inventory OPEN (D-10.8, §8.18.2) |
| Persisted identity binding | 0 | No production writer of `IdentityBindingRepository`; no seat literal in `backend/app`, `config`, `database`, `routes`, `bootstrap` |
| Runtime Source Registry entry | 0 | No Runtime Source Registry exists; `ScientificSourceAdapterRegistry` holds only the five Stage-3 keys (`openalex`, `crossref`, `semantic_scholar`, `consensus`, `fao_stat`), none bound to a seat |
| Capability fact (any state) | 0 written by production code | No production caller of `CapabilityStoreRepository`; missing fact = UNVERIFIED (D-10.5.1) |
| Adapter IMPLEMENTED for the seat | 0 | — |
| VERIFIED (CapVer) | 0 | No CapVer exists (§8.18.2) |
| ACTIVE | 0 | No seat activation-state model; Membership → ACTIVE forbidden (D-10.10.4) |
| ACCEPTED | 0 | Source acceptance is Phase 2 |
| BLOCKED for Phase 1 implementation | 109 | B1–B4 below |

## Status codes

| Code | Meaning |
|---|---|
| `NC` | NOT CLASSIFIED — no verified per-seat integration classification; family must not be inferred from name or website (D-10.8.2) |
| `ABSENT` | No artifact exists in the repository or is written by production code |
| `NONE` | No binding exists; `canonical_identity_id` stays NULL until explicit SAME_AS evidence (D-10.3.4) |
| `UNV` | UNVERIFIED — no Capability fact; absence is never UNAVAILABLE (D-10.5.1) |
| `NI` | NOT IMPLEMENTED |
| `MEM` | Only membership is tested (`Adr023109MembershipRegisterContractTest`); no seat-level runtime test exists |
| `NR` | NOT RUN — nothing seat-specific exists to test |
| `UNK` | External dependencies unknown until per-seat access verification |
| `NA-ACT` | NOT ACTIVE |
| `NA-ACC` | NOT ACCEPTED (Phase 2 scope) |

## Blockers

- **B1** — No Implementation Authorization Gate for registry, identity, capability, adapter, or onboarding work (D-10.15; §8.9.8; §8.18 "NO NEXT IMPLEMENTATION UNIT IS AUTHORIZED"; §8.24).
- **B2** — No verified per-seat integration classification (D-10.8); IC Persistence is itself BLOCKED (§8.22, §8.25).
- **B3** — CapVer organizational ownership, implementing service, and enforcement location are deferred (D-10.5.9; §8.23).
- **B4** — Per-seat access evidence (protocol, authentication, license, rate limits) has not been verified for any seat.

## Matrix

Columns: Access family · Access evidence · IC artifact · Identity binding · Runtime Registry · Capability · Adapter · CSQ→source query mapping · Normalization · Provenance · Unit/contract tests · Integration tests · External deps · Activation · Acceptance · Blockers.

| adr_id | Display name (register) | Group | Dual seat | Family | Access evidence | IC | Identity | Registry | Cap | Adapter | Query map | Normalize | Provenance | Unit | Integration | Ext deps | Activation | Acceptance | Blockers |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| G1-01 | FAO AGRIS | G1 | — | NC | Historical GO-1/1b/1c assessment of 62-register #1 "AGRIS" exists; not bound to this seat (display-name match is insufficient, D-10.3.3) | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-02 | Organic Eprints | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-03 | Turkish Journal of Agriculture and Forestry | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-04 | AgroSpace | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-05 | AgriRxiv | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-06 | AgroPortal | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-07 | YYU Journal of Agricultural Sciences | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-08 | J. Korean Soc. Int. Agriculture | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-09 | Int. J. Horticultural Science | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-10 | JISHS / eJHS | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-11 | Sarhad Journal of Agriculture | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-12 | FlowerBase | G1 | G6-01 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-13 | HortDB V1.0 | G1 | G6-03 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-14 | Tropicals.cn | G1 | G6-02 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-15 | sCentInDB | G1 | G6-04 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-16 | AromaDb | G1 | G6-05 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-17 | Dr. Duke's Databases | G1 | G6-06 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-18 | MPNS — Kew | G1 | G6-07 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-19 | Fruit and Nut Cultivars Database | G1 | G6-08 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-20 | FEAtl | G1 | G6-09 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-21 | CRFG | G1 | G6-10 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G1-22 | GrainGenes | G1 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-01 | Poultry Science | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-02 | Journal of Applied Poultry Research | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-03 | European Poultry Science | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-04 | Brazilian Journal of Poultry Science | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-05 | Journal of Poultry Science | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-06 | Journal of Animal Science and Biotechnology | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-07 | Animal Frontiers | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-08 | Veterinary and Animal Science | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-09 | Turkish Journal of Veterinary & Animal Sciences | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-10 | Journal of Animal Production (Hayvansal Üretim) | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-11 | Aquaculture and Fisheries | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-12 | Turkish Journal of Fisheries and Aquatic Sciences | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-13 | Marine and Fishery Sciences (MAFIS) | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-14 | AIMS Animal Science | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-15 | Andalasian Livestock | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-16 | Hayvan Bilimi ve Ürünleri Dergisi (JASP) | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-17 | REMVT | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-18 | Farm Animal Health and Nutrition | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-19 | Animal Bioscience | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-20 | Aquaculture Journal | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-21 | Aquaculture Reports | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-22 | LIVEMAP | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-23 | LivestockDev | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-24 | IDEAL Project | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G2-25 | CIRAD Portals | G2 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-01 | APIS | G3 | — | NC | GO-ID-G3-01 pin: external identity VERIFIED (journal Apis, ISSN 3058-0382); no access method assessed | ABSENT | PINNED external identity (GO-ID-G3-01); no persisted binding | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-02 | Uludag Bee Journal | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-03 | Journal of Apicultural Science | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-04 | Frontiers in Bee Science | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-05 | OSMIA | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-06 | EUNK | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-07 | Apidologie | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-08 | USDA-ARS Carl Hayden Honey Bee Research Center | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-09 | USDA-ARS Bee Research Laboratory — Beltsville | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-10 | Harry H. Laidlaw Jr. Honey Bee Research Facility — UC Davis | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-11 | University of Minnesota Bee Lab | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-12 | Washington State University Bee Program | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-13 | MSPB | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G3-14 | UrBAN | G3 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G4-01 | SilkBase | G4 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G4-02 | KAIKObase | G4 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G4-03 | Silkworm Gene Resources Database (SGRDB / NAAS) | G4 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G4-04 | SilkDB / Silkworm Knowledgebase | G4 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G4-05 | International Sericultural Commission (ISC / INSERCO) | G4 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G4-06 | Sericologia | G4 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G4-07 | Queen Sirikit Department of Sericulture (QSDS) | G4 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-01 | InsectBrainDatabase (IBdb) | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-02 | AGRICOLA (USDA/NAL) | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-03 | ELIE — Entomological Label Information Extraction | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-04 | GBIF | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-05 | Journal of Insect Science (JIS) | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-06 | npj Entomology | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-07 | Insects (MDPI) | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-08 | Current Research in Insect Science | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-09 | Frontiers in Insect Science | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-10 | Florida Entomologist | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-11 | Egyptian Academic Journal of Biological Sciences A — Entomology | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-12 | PhytoFrontiers™ | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-13 | Phytopathology Research | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-14 | Tropical Plant Pathology | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-15 | BMC Plant Biology | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-16 | Pathogens (MDPI) | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-17 | Journal of Plant Protection and Pathology — Mansoura University | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-18 | Iranian Journal of Plant Pathology | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G5-19 | Plant Pathology Journal — ANSInet | G5 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-01 | FlowerBase | G6 | G1-12 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-02 | Tropicals.cn | G6 | G1-14 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-03 | HortDB V1.0 | G6 | G1-13 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-04 | sCentInDB | G6 | G1-15 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-05 | AromaDb | G6 | G1-16 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-06 | Dr. Duke's Phytochemical and Ethnobotanical Databases | G6 | G1-17 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-07 | MPNS – Kew | G6 | G1-18 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-08 | Fruit and Nut Cultivars Database (FNCD) | G6 | G1-19 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-09 | FEAtl – Fruit Expression Atlas | G6 | G1-20 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-10 | CRFG – California Rare Fruit Growers | G6 | G1-21 (dual_pairs ≠ SAME_AS) | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-11 | Chrysanthemum Genome Database (CGD) | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-12 | OfIR – Osmanthus fragrans Information Resource | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-13 | PGRDEU | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-14 | GDR – Genome Database for Rosaceae | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-15 | PeachMD | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-16 | Citrus Genome Database (CGD) | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-17 | HarnesstomDB | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-18 | INIA Peru – Banco de Germoplasma | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-19 | BINA – Paraguay | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-20 | Biodiversity Heritage Library (BHL) | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-21 | New York Botanical Garden (NYBG) | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |
| G6-22 | PG Syllabus – UBKV | G6 | — | NC | none recorded | ABSENT | NONE | ABSENT | UNV | NI | NI | NI | NI | MEM | NR | UNK | NA-ACT | NA-ACC | B1 B2 B3 B4 |

## Change control

This matrix must be regenerated or amended by a governed ADR-023 record whenever a seat-level artifact is added. A status may advance only with cited evidence; VERIFIED requires CapVer (§8.23); ACTIVE requires the D-10.7.6 chain and an accepted Onboarding Contract (D-10.11). It never changes membership.
