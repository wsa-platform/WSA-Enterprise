# 08 — Query Construction Evidence

## Before (conceptual, post-`63d693f` residual)

- Arabic CWR process could still lean toward coarse `irrigation` / prior deficit bias for non-deficit surfaces.
- Sense OR-gate could classify systems inventory as CWR, skewing process terms.

## After

| Surface (example) | English retrieval concept |
|---|---|
| الري الناقص | `deficit irrigation` |
| الري المنظم | `regulated deficit irrigation` |
| كمية الري | `irrigation quantity` |
| الاحتياج المائي… | `crop water requirement` |
| جدولة الري | `irrigation scheduling` |
| كفاءة استخدام مياه الري | `irrigation water-use efficiency` |
| تأثير الري على الإنتاجية | `effects of irrigation on yield` |
| تأثير المياه على النمو | `effects of water availability on growth` |

Implementation: `AgriculturalEntityCatalog::irrigationRetrievalEnglishConcept` ← `ScientificSearchQueryBuilder::englishRetrievalFallbackForRole`.

## Protections verified in tests

- No Arabic script in executable English variants (`preg_match('/\p{Arabic}/u') === 0`).
- Quantity questions do not collapse exclusively to deficit irrigation.
