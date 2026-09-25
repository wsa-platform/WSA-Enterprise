import { describe, expect, it } from 'vitest'
import { SCIENTIFIC_CANDIDATE_THRESHOLD, toScientificUserPresentation } from './scientificUserPresentation'

describe('scientific user presentation', () => {
  it('uses the server presentation contract and never exposes internals', () => {
    const presentation = toScientificUserPresentation({
      status: 'scientific_generated',
      answer: 'Internal dump should be ignored when presentation exists.',
      confidence: 0.91,
      limitations: ['raw_limit'],
      conflicts: [{ evidence_id: 'ev-1' }],
      user_presentation: {
        primary_answer: 'Clear primary answer.',
        human_status: 'answered',
        user_notice_code: null,
        candidates: [{ result_id: 'cand-a', answer: 'Second answer.' }],
        sources: [{ result_id: 'src-1', title: 'Paper', original_url: 'https://example.org/p' }],
        candidate_selection: {
          threshold: SCIENTIFIC_CANDIDATE_THRESHOLD,
          presented_count: 2,
          confidence_exposed: false,
          directness_unchanged: true,
        },
      },
    })

    expect(presentation?.primary_answer).toBe('Clear primary answer.')
    expect(presentation?.candidates[0]?.result_id).toBe('cand-a')
    expect(presentation?.sources[0]?.result_id).toBe('src-1')
    expect(presentation?.candidate_selection?.threshold).toBe(0.50)
    expect(presentation?.candidate_selection?.confidence_exposed).toBe(false)
    expect(JSON.stringify(presentation)).not.toContain('raw_limit')
    expect(JSON.stringify(presentation)).not.toContain('evidence_id')
    expect(JSON.stringify(presentation)).not.toContain('0.91')
  })

  it('does not reconstruct a final answer from raw fields when presentation is absent', () => {
    const presentation = toScientificUserPresentation({
      status: 'scientific_generated',
      answer: 'Supported value is 6609520 t.',
      confidence: 0.80,
      citations: [],
      research_metadata: {
        direct_evidence_gate: 'PASSED',
        evidence_sufficient: true,
      },
    })

    expect(presentation?.human_status).toBe('insufficient')
    expect(presentation?.primary_answer).toBeNull()
    expect(presentation?.user_notice_code).toBe('insufficient_direct_evidence')
    expect(presentation?.sources).toEqual([])
  })

  it('does not treat raw answer as final when primary_answer is null and confidence is below 0.50', () => {
    const presentation = toScientificUserPresentation({
      status: 'scientific_generated',
      answer: 'Raw ineligible synthesis.',
      confidence: 0.40,
      user_presentation: {
        primary_answer: null,
        human_status: 'insufficient',
        user_notice_code: 'insufficient_direct_evidence',
        candidates: [],
        sources: [],
      },
    })

    expect(presentation?.primary_answer).toBeNull()
    expect(presentation?.human_status).toBe('insufficient')
  })

  it('displays backend primary_answer and ignores raw synthesis.answer', () => {
    const presentation = toScientificUserPresentation({
      status: 'scientific_generated',
      answer: 'Internal dump.',
      confidence: 0.40,
      user_presentation: {
        primary_answer: 'Backend decided this is displayable.',
        human_status: 'answered',
        user_notice_code: null,
        candidates: [],
        sources: [],
      },
    })

    expect(presentation?.primary_answer).toBe('Backend decided this is displayable.')
    expect(presentation?.human_status).toBe('answered')
  })

  it('does not treat a high-confidence candidate as the final answer', () => {
    const presentation = toScientificUserPresentation({
      status: 'scientific_generated',
      answer: 'Raw ineligible synthesis.',
      confidence: 0.40,
      answer_candidates: [{ result_id: 'c-high', answer: 'High-confidence candidate text.' }],
      user_presentation: {
        primary_answer: null,
        human_status: 'insufficient',
        user_notice_code: 'insufficient_direct_evidence',
        candidates: [{ result_id: 'c-high', answer: 'High-confidence candidate text.' }],
        sources: [],
      },
    })

    expect(presentation?.primary_answer).toBeNull()
    expect(presentation?.human_status).toBe('insufficient')
    expect(presentation?.candidates[0]?.answer).toBe('High-confidence candidate text.')
  })

  it('maps insufficient presentation without fabricating sources', () => {
    const presentation = toScientificUserPresentation({
      status: 'insufficient_evidence',
      answer: 'Composer diagnostic sentence.',
      citations: [],
      conflicts: [{ evidence_id: 'ev-9' }],
    })

    expect(presentation?.human_status).toBe('insufficient')
    expect(presentation?.primary_answer).toBeNull()
    expect(presentation?.user_notice_code).toBe('insufficient_direct_evidence')
    expect(presentation?.sources).toEqual([])
  })
})
