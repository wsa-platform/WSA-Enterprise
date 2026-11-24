# 05 — Multilingual Test Design

## Target Languages

EN, AR, TR, FR — paired scientific sense where possible.

## Paired Questions Used (live probe)

| ID | Language | Question | Intended sense |
|---|---|---|---|
| EN_DEFICIT | en | What are the effects of deficit irrigation on wheat yield? | crop_water_requirement / irrigation effects |
| AR_DEFICIT | ar | ما تأثير الري الناقص على إنتاجية القمح؟ | same |
| TR_CWR | tr | Buğday sulama suyu ihtiyacı nedir? | crop_water_requirement |
| FR_CWR | fr | Quel est le besoin en eau du blé? | crop_water_requirement |
| AR_DISEASE | ar | ما أمراض صدأ القمح الشائعة؟ | disease |
| EN_INSUFFICIENT | en | … Martian soil … | exotic / insufficient |

## Identity Preservation Checks

| Dimension | EN↔AR deficit | TR/FR CWR |
|---|---|---|
| Crop (wheat) | Preserved in sense/intent | Preserved |
| Research intent irrigation | Both `irrigation` | Both `irrigation` |
| Scientific sense | Both `crop_water_requirement` | Both `crop_water_requirement` |
| Answer language | Matches request (`en`/`ar`) | Matches (`tr`/`fr`) |
| Source language | Often EN excerpts | Often EN excerpts with localized prefix |

## Language Separation

- Question language ≠ forced source language (EN sources OK).
- Answer chrome localized (headers, uncertainty, synthesis notes).
- Empirical sentences may remain EN under source-excerpt policy — **not** treated as identity failure unless sense/crop/intent drift.

## Verification Levels Claimed

| Level | EN | AR | TR | FR |
|---|---|---|---|---|
| CONTRACT_VERIFIED | yes | yes | yes (matrix) | yes (matrix) |
| FIXTURE_VERIFIED | yes | yes | yes | yes |
| LIVE_RETRIEVAL_VERIFIED | yes* | yes* | yes* | yes* |
| LIVE_ANSWER_VERIFIED | PARTIAL | PARTIAL | PARTIAL | PARTIAL |
| LIVE_UI_VERIFIED | NOT_RUN | PARTIAL shell only | NOT_RUN | NOT_RUN |

\*Live retrieval via agent against local sources; LocalIndex ≠ PRODUCTION_READY.
