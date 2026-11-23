# 02 — Architectural Design

## Decisions

1. **Presentation mode:** Causal/effects questions must not use numeric `range` chrome when sense is `crop_water_requirement`.
2. **Answer body contract:** `answer` remains main body only; when findings are source prose, surface **attributed** first-sentence excerpts (ISS-AR-005), never unmarked English as the Arabic voice.
3. **No ADR-022/023 change:** English retrieval compilation and membership≠activation preserved.
4. **No new source activation** (G2-15 deferred).

## Alternatives rejected

- Weakening Relevance/Validation to lengthen answers.
- Translating evidence with paid MT.
- Force-including all abstract text unmarked.
