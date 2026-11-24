# 02 — Multilingual Answer Audit

## Post-remediation live probe (`substance_parity_probe.json`)

| Case | Completeness | Notes |
|---|---|---|
| EN_CWR | PARTIALLY_COMPLETE | water_req present; quantity often missing |
| AR_CWR | SUBSTANTIVELY_COMPLETE | improved after motivation/treatment filters |
| TR_CWR | SUBSTANTIVELY_COMPLETE | no longer treatment-list-only; ETc/WUE substance |
| FR_CWR | SUBSTANTIVELY_COMPLETE | same; yield-kg-as-Besoin blocked in unit tests |
| EN_DEFICIT | SUBSTANTIVELY_COMPLETE | empirical deficit→yield |
| AR_DEFICIT | SUBSTANTIVELY_COMPLETE | deficit + yield signals |
| TR_DEFICIT | NOT_RESPONSIVE | collapses to limited/conflict chrome |
| FR_DEFICIT | NOT_RESPONSIVE | same |
| EN_SALINITY | variable live | COMPLETE then INSUFFICIENT across runs (retrieval variability) |
| EN_INSUFFICIENT | INSUFFICIENT_EVIDENCE | Martian domain gate holds |

## Language vs substance

- Language fidelity (answer chrome TR/FR/AR): preserved.
- Source excerpts remain EN when sources are EN — labeled, not silent mix.
- Substance parity improved for CWR TR/FR vs prior mission; deficit TR/FR still weak (coverage/composition).
