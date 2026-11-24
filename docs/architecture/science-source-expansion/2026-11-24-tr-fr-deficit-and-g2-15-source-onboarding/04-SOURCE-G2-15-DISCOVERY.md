# 04 — Source G2-15 Discovery

| Field | Value |
|---|---|
| ADR ID | G2-15 |
| Display name | Andalasian Livestock |
| Source key | `andalasian_livestock` |
| Official site | https://alive.lppm.unand.ac.id/index.php/alive |
| Publisher | LPPM Universitas Andalas |
| E-ISSN | 3063-1912 |
| DOI prefix | 10.25077/alive |
| Scope | Animal science / livestock (peer-reviewed OA journal) |
| Registry | CURRENT_MEMBER, **activation INACTIVE** |

## OAI-PMH

| Verb | Result |
|---|---|
| Identify | HTTP 200 — repositoryName Andalasian Livestock; protocol 2.0 |
| ListRecords `oai_dc` | HTTP 200 — records returned (artifact ~227 KB) |

Endpoint: `https://alive.lppm.unand.ac.id/index.php/alive/oai`

Artifacts: `g2_15_oai_identify.xml`, `g2_15_oai_listrecords_head.xml`

**WAF:** Prior historical note of Identify block; **current probe succeeds** — access not BLOCKED.
