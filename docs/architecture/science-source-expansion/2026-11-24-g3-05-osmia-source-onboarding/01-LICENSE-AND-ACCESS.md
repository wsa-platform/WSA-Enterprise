# 01 — License and Access

## G3-05 OSMIA

- About: https://www.osmia-journal-hymenoptera.com/about.html — CC BY 4.0
- Crossref: `GET https://api.crossref.org/journals/2727-3806/works` — works include `license.URL = …/by/4.0/`
- Harvest filter drops BY-NC / BY-ND
- Usages declared PERMITTED: retrieval, citation_linking, metadata_storage, excerpt_display; FULL_TEXT CONDITIONAL

## G2-17 REMVT (rejected this mission)

- Identify/ListRecords: PASS
- About page claims CC BY 4.0
- **Record-level OAI `dc:rights`:** `creativecommons.org/licenses/by-nc-sa/4.0/` → **BLOCKED_LICENSE** for commercial LocalIndex
