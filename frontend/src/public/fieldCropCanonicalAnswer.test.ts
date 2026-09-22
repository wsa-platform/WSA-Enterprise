import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { I18nextProvider } from 'react-i18next'
import i18n from '../i18n/config'
import {
  hasCanonicalStage5Answer,
  resolveCanonicalCropAnswerText,
  resolveCropAnswerRenderMode,
  type FieldCropCultivationProfile,
} from '../api/fieldCropCultivation'
import { FieldCropCanonicalAnswerView } from './FieldCropCanonicalAnswerView'
import { FieldCropFarmingNeedsPanel, FieldCropProfileArticle } from './FieldCropFarmingNeedsPanel'

function baseLegacy(): FieldCropCultivationProfile {
  return {
    crop: {
      id: 'wheat',
      name: 'القمح',
      category_id: 'grains',
      category_name: 'محاصيل الحبوب',
    },
    service_option: 'farming-needs',
    title: 'زراعة واحتياجات محصول القمح',
    load_state: 'library_complete',
    message: null,
    sections: [
      {
        key: 'soil',
        title: 'التربة',
        content: 'LEGACY_SECTION_CONTENT_ONLY',
        source: null,
        verified: true,
      },
    ],
    references: [{ title: 'Legacy ref', url: 'https://library.example/legacy' }],
    library: {
      item_id: 1,
      slug: 'wheat',
      reused_existing: true,
      missing_sections_filled: [],
    },
  }
}

function canonicalFixture(
  overrides: Partial<FieldCropCultivationProfile> = {},
): FieldCropCultivationProfile {
  return {
    ...baseLegacy(),
    status: 'completed',
    stage: 5,
    answer: 'CANONICAL_STAGE5_ANSWER_BODY',
    concise_summary: 'Short summary',
    detailed_explanation: 'CANONICAL_STAGE5_ANSWER_BODY',
    key_findings: ['Finding A'],
    language: 'en',
    confidence: 0.81,
    limitations: ['limited_geo_coverage'],
    uncertainty: null,
    conflicts: [],
    claims: [
      {
        claim_id: 'claim-1',
        claim_text: 'Wheat needs drainage',
        question_claim_id: 'qc-1',
        evidence_ids: ['ev-1'],
      },
    ],
    citations: [
      {
        citation_id: 'cite-1',
        title: 'Wheat Study',
        doi: '10.1000/wheat',
        url: 'https://example.org/wheat-direct',
        evidence_id: 'ev-1',
        organization: 'Org',
      },
    ],
    ...overrides,
  }
}

function renderCanonical(profile: FieldCropCultivationProfile) {
  return renderToStaticMarkup(
    createElement(
      I18nextProvider,
      { i18n },
      createElement(FieldCropCanonicalAnswerView, { profile }),
    ),
  )
}

describe('P7-U2 crop canonical Stage 5 helpers', () => {
  it('resolves answer then concise_summary', () => {
    expect(
      resolveCanonicalCropAnswerText({
        answer: '  A  ',
        concise_summary: 'B',
      }),
    ).toBe('A')
    expect(
      resolveCanonicalCropAnswerText({
        answer: '   ',
        concise_summary: ' Summary ',
      }),
    ).toBe('Summary')
    expect(hasCanonicalStage5Answer({ answer: null, concise_summary: null })).toBe(false)
  })

  it('chooses canonical vs limited vs legacy modes explicitly', () => {
    expect(resolveCropAnswerRenderMode(canonicalFixture())).toBe('canonical')
    expect(
      resolveCropAnswerRenderMode({
        answer: null,
        concise_summary: null,
        status: 'insufficient_evidence',
      }),
    ).toBe('limited')
    expect(
      resolveCropAnswerRenderMode({
        answer: null,
        concise_summary: null,
        status: 'completed',
      }),
    ).toBe('legacy')
  })
})

describe('P7-U2 FieldCropCanonicalAnswerView', () => {
  it('A/B renders Stage 5 answer as primary and does not include legacy section text', async () => {
    await i18n.changeLanguage('en')
    const html = renderCanonical(canonicalFixture())
    expect(html).toContain('CANONICAL_STAGE5_ANSWER_BODY')
    expect(html).toContain('data-testid="crop-canonical-answer"')
    expect(html).not.toContain('LEGACY_SECTION_CONTENT_ONLY')
    expect(html).toContain('data-answer-mode="canonical"')
  })

  it('D/E renders confidence and limitations from backend values', async () => {
    await i18n.changeLanguage('en')
    const html = renderCanonical(canonicalFixture({ confidence: 0.81, limitations: ['limited_geo_coverage'] }))
    expect(html).toContain('0.81')
    expect(html).toContain('limited_geo_coverage')
    expect(html).toContain('data-testid="crop-confidence"')
    expect(html).toContain('data-testid="crop-limitations"')
  })

  it('F insufficient evidence is marked limited and shows notice', async () => {
    await i18n.changeLanguage('en')
    const html = renderCanonical(
      canonicalFixture({
        status: 'insufficient_evidence',
        answer: 'Insufficient evidence for a factual crop answer.',
        limitations: ['insufficient_validated_evidence_for_question_claim'],
        confidence: 0,
      }),
    )
    expect(html).toContain('gs-field-crop-canonical--limited')
    expect(html).toContain('insufficient_validated_evidence_for_question_claim')
    expect(html).toContain('limited by insufficient or conflicting')
  })

  it('G renders conflicts and uncertainty', async () => {
    await i18n.changeLanguage('en')
    const html = renderCanonical(
      canonicalFixture({
        status: 'conflicted',
        conflicts: [{ detail: 'irrigation amounts differ' }],
        uncertainty: 'competing_sources',
      }),
    )
    expect(html).toContain('irrigation amounts differ')
    expect(html).toContain('competing_sources')
    expect(html).toContain('data-testid="crop-conflicts"')
  })

  it('H/I citation URL is direct original and not Google', async () => {
    await i18n.changeLanguage('en')
    const html = renderCanonical(canonicalFixture())
    expect(html).toContain('href="https://example.org/wheat-direct"')
    expect(html).not.toContain('google.com')
    expect(html).not.toContain('google.')
  })

  it('J/K answer language metadata preserved; UI locale does not rewrite answer body', async () => {
    await i18n.changeLanguage('ar')
    const html = renderCanonical(canonicalFixture({ language: 'en', answer: 'ENGLISH_ANSWER_BODY' }))
    expect(html).toContain('ENGLISH_ANSWER_BODY')
    expect(html).toContain('data-answer-language="en"')
    expect(html).toContain('لغة الإجابة: en')
  })

  it('L preserves claim and evidence identity in the model output', async () => {
    await i18n.changeLanguage('en')
    const html = renderCanonical(canonicalFixture())
    expect(html).toContain('question_claim_id=qc-1')
    expect(html).toContain('evidence_ids=ev-1')
    expect(html).toContain('evidence: ev-1')
  })
})

describe('P7-U2 FieldCropProfileArticle', () => {
  function renderArticle(profile: FieldCropCultivationProfile) {
    return renderToStaticMarkup(
      createElement(
        I18nextProvider,
        { i18n },
        createElement(FieldCropProfileArticle, { profile }),
      ),
    )
  }

  it('B does not render legacy sections when Stage 5 answer exists', async () => {
    await i18n.changeLanguage('en')
    const html = renderArticle(canonicalFixture())
    expect(html).toContain('data-crop-answer-mode="canonical"')
    expect(html).toContain('CANONICAL_STAGE5_ANSWER_BODY')
    expect(html).not.toContain('LEGACY_SECTION_CONTENT_ONLY')
    expect(html).not.toContain('data-legacy-section')
    expect(html).not.toContain('https://library.example/legacy')
  })

  it('C legacy fallback renders sections when Stage 5 answer is absent', async () => {
    await i18n.changeLanguage('en')
    const html = renderArticle(baseLegacy())
    expect(html).toContain('data-crop-answer-mode="legacy"')
    expect(html).toContain('LEGACY_SECTION_CONTENT_ONLY')
    expect(html).toContain('data-legacy-section="true"')
  })

  it('limited without answer does not fall back to legacy sections as answer', async () => {
    await i18n.changeLanguage('en')
    const html = renderArticle({
      ...baseLegacy(),
      status: 'insufficient_evidence',
      answer: null,
      concise_summary: null,
      limitations: ['insufficient_validated_evidence_for_question_claim'],
    })
    expect(html).toContain('data-crop-answer-mode="limited"')
    expect(html).not.toContain('LEGACY_SECTION_CONTENT_ONLY')
    expect(html).toContain('insufficient_validated_evidence_for_question_claim')
  })
})

describe('P7-U2 FieldCropFarmingNeedsPanel modes', () => {
  it('C legacy fallback renders sections when Stage 5 answer is absent', async () => {
    await i18n.changeLanguage('en')
    expect(resolveCropAnswerRenderMode(baseLegacy())).toBe('legacy')
    expect(baseLegacy().sections[0]?.content).toBe('LEGACY_SECTION_CONTENT_ONLY')
  })

  it('M accepts dual-contract profile with legacy + Stage 5 fields', () => {
    const profile = canonicalFixture()
    expect(profile.sections.length).toBeGreaterThan(0)
    expect(profile.library.item_id).toBe(1)
    expect(hasCanonicalStage5Answer(profile)).toBe(true)
    expect(resolveCropAnswerRenderMode(profile)).toBe('canonical')
  })

  it('limited mode without answer does not promote legacy as scientific answer', () => {
    const profile: FieldCropCultivationProfile = {
      ...baseLegacy(),
      status: 'insufficient_evidence',
      answer: null,
      concise_summary: null,
      limitations: ['insufficient_validated_evidence_for_question_claim'],
    }
    expect(resolveCropAnswerRenderMode(profile)).toBe('limited')
    expect(resolveCropAnswerRenderMode(profile)).not.toBe('legacy')
  })
})

describe('P7-U2 panel module', () => {
  it('exports the container used by FieldCropSelector', () => {
    expect(typeof FieldCropFarmingNeedsPanel).toBe('function')
    expect(typeof FieldCropProfileArticle).toBe('function')
  })
})
