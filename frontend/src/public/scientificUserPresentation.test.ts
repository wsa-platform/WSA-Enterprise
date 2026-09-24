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

  it('maps insufficient evidence to a human status without fabricating sources', () => {
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

  it('keeps a gate-passed answer when citations are empty and does not fabricate a source', () => {
    const presentation = toScientificUserPresentation({
      status: 'synthesis_completed_with_partial_conflicts',
      answer: 'Supported value is 6609520 t.',
      citations: [],
      research_metadata: {
        direct_evidence_gate: 'PASSED',
        evidence_sufficient: true,
      },
    })

    expect(presentation?.human_status).toBe('answered')
    expect(presentation?.primary_answer).toBe('Supported value is 6609520 t.')
    expect(presentation?.sources).toEqual([])
    expect(JSON.stringify(presentation)).not.toContain('doi.org')
    expect(JSON.stringify(presentation)).not.toContain('google.')
  })

  it('does not present an answer when the direct evidence gate failed', () => {
    const presentation = toScientificUserPresentation({
      status: 'scientific_generated',
      answer: 'Supporting-only narrative.',
      citations: [{ citation_id: 'cite-1', title: 'Paper', url: 'https://example.org/p' }],
      research_metadata: {
        direct_evidence_gate: 'INSUFFICIENT_DIRECT_EVIDENCE',
        evidence_sufficient: false,
      },
    })

    expect(presentation?.human_status).toBe('insufficient')
    expect(presentation?.primary_answer).toBeNull()
    expect(presentation?.user_notice_code).toBe('insufficient_direct_evidence')
  })
})
