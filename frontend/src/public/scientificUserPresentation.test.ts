import { describe, expect, it } from 'vitest'
import {
  SCIENTIFIC_CANDIDATE_THRESHOLD,
  SEARCH_RESULT_CONFIDENCE_THRESHOLD,
  isEligibleScientificResearchResult,
  toScientificUserPresentation,
} from './scientificUserPresentation'

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

  it('maps multiple scientific results without collapsing them to one', () => {
    const presentation = toScientificUserPresentation({
      user_presentation: {
        primary_answer: null,
        human_status: 'insufficient',
        user_notice_code: 'insufficient_direct_evidence',
        candidates: [],
        sources: [],
        results: [
          { result_id: 'https://openalex.org/W1', title: 'Paper One', confidence: 0.80 },
          { result_id: 'https://openalex.org/W2', title: 'Paper Two', confidence: 0.70 },
          { result_id: 'https://openalex.org/W3', title: 'Paper Three', confidence: 0.50 },
        ],
      },
    })

    expect(presentation?.results.map((row) => row.result_id)).toEqual([
      'https://openalex.org/W1',
      'https://openalex.org/W2',
      'https://openalex.org/W3',
    ])
    expect(presentation?.primary_answer).toBeNull()
  })

  it('filters scientific results by Stage 4 evidence confidence, not overallConfidence', () => {
    expect(SEARCH_RESULT_CONFIDENCE_THRESHOLD).toBe(0.50)
    expect(isEligibleScientificResearchResult({ result_id: 'a', confidence: 0.49 })).toBe(false)
    expect(isEligibleScientificResearchResult({ result_id: 'b', confidence: 0.50 })).toBe(true)
    expect(isEligibleScientificResearchResult({ result_id: 'c', confidence: 0.51 })).toBe(true)
    expect(isEligibleScientificResearchResult({ result_id: 'missing' })).toBe(false)
    expect(isEligibleScientificResearchResult({ result_id: 'nan', confidence: Number.NaN })).toBe(false)

    const presentation = toScientificUserPresentation({
      confidence: 0.91,
      user_presentation: {
        primary_answer: null,
        human_status: 'insufficient',
        user_notice_code: 'insufficient_direct_evidence',
        candidates: [],
        sources: [],
        results: [
          { result_id: 'hidden', title: 'Below threshold', confidence: 0.49 },
          { result_id: 'boundary', title: 'Exact threshold', confidence: 0.50 },
          { result_id: 'above', title: 'Above threshold', confidence: 0.51 },
        ],
      },
    })

    expect(presentation?.results.map((row) => row.result_id)).toEqual(['boundary', 'above'])
    expect(JSON.stringify(presentation)).not.toContain('0.91')
    expect(JSON.stringify(presentation)).not.toContain('Below threshold')
  })

  it('does not invent results from citations or answer candidates', () => {
    const presentation = toScientificUserPresentation({
      citations: [{ citation_id: 'cite-1', title: 'Citation is not a result' }],
      answer_candidates: [{ result_id: 'cand-1', answer: 'Candidate is not a paper' }],
      user_presentation: {
        primary_answer: null,
        human_status: 'insufficient',
        user_notice_code: 'insufficient_direct_evidence',
        candidates: [{ result_id: 'cand-1', answer: 'Candidate is not a paper' }],
        sources: [{ result_id: 'cite-1', title: 'Citation is not a result' }],
      },
    })

    expect(presentation?.results).toEqual([])
    expect(presentation?.sources[0]?.title).toBe('Citation is not a result')
    expect(presentation?.candidates[0]?.answer).toBe('Candidate is not a paper')
  })

  it('excludes results that omit confidence and does not invent a final answer', () => {
    const presentation = toScientificUserPresentation({
      confidence: 0.80,
      user_presentation: {
        primary_answer: 'Backend final answer.',
        human_status: 'answered',
        user_notice_code: null,
        candidates: [],
        sources: [],
        results: [
          { result_id: 'no-conf', title: 'Missing confidence' },
          { result_id: 'ok', title: 'Eligible paper', confidence: 0.50 },
          { result_id: '', title: 'Empty id', confidence: 0.90 },
        ],
      },
    })

    expect(presentation?.primary_answer).toBe('Backend final answer.')
    expect(presentation?.results.map((row) => row.result_id)).toEqual(['ok'])
  })

  it('maps missing results to an empty list without changing primary_answer', () => {
    const presentation = toScientificUserPresentation({
      user_presentation: {
        primary_answer: 'Answer only.',
        human_status: 'answered',
        user_notice_code: null,
        candidates: [],
        sources: [],
      },
    })

    expect(presentation?.results).toEqual([])
    expect(presentation?.primary_answer).toBe('Answer only.')
  })
})
