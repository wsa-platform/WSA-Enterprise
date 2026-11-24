import { describe, expect, it } from 'vitest'
import type { ResearchAgentQueryResponse } from '../api/researchAgent'
import {
  buildHomeResearchDisplayModel,
  extractStableResultSetId,
  researchFailureTranslationKey,
} from './stableResearchPresentation'

describe('stableResearchPresentation', () => {
  it('uses stable page answers as authority and deduplicates', () => {
    const response: ResearchAgentQueryResponse = {
      user_presentation: {
        primary_answer: 'Same answer',
        human_status: 'answered',
        candidates: [{ result_id: 'c1', answer: 'Same answer' }],
        sources: [],
      },
      multi_answers: [
        { answer: 'Same answer', result_id: 'm1' },
        { answer: 'Same answer', result_id: 'm2' },
      ],
      stable_result_set: {
        created: true,
        result_set_id: 'srs_1',
      },
    }

    const model = buildHomeResearchDisplayModel({
      queryResponse: response,
      stablePage: {
        result_set_id: 'srs_1',
        page: 2,
        page_size: 10,
        total_answers: 11,
        total_pages: 2,
        answers: [
          { answer: 'Page two answer A', result_id: 'a', position: 10 },
          { answer: 'Page two answer A', result_id: 'b', position: 11 },
          { answer: 'Page two answer B', result_id: 'c', position: 12 },
        ],
        rerun_performed: false,
      },
    })

    expect(model.usesStableResultSet).toBe(true)
    expect(model.pagination?.page).toBe(2)
    expect(model.answers).toHaveLength(2)
    expect(model.answers[0]?.answer).toBe('Page two answer A')
    expect(model.answers[1]?.answer).toBe('Page two answer B')
    expect(extractStableResultSetId(response)).toBe('srs_1')
  })

  it('falls back to legacy presentation without claiming stable pagination', () => {
    const model = buildHomeResearchDisplayModel({
      queryResponse: {
        user_presentation: {
          primary_answer: 'Legacy only',
          human_status: 'answered',
          candidates: [{ result_id: 'x', answer: 'Alt' }],
          sources: [],
        },
      },
      stablePage: null,
    })
    expect(model.usesStableResultSet).toBe(false)
    expect(model.pagination).toBeNull()
    expect(model.answers.map((a) => a.answer)).toEqual(['Legacy only', 'Alt'])
  })

  it('maps failure classes to user-facing translation keys without raw codes', () => {
    expect(researchFailureTranslationKey('ALL_RESULTS_REJECTED')).toBe(
      'website.research.failureAllRejected',
    )
    expect(researchFailureTranslationKey('RESULT_SET_FAILURE', 'stable_result_set_missing_or_expired')).toBe(
      'website.research.failureResultSetExpired',
    )
    expect(researchFailureTranslationKey(null)).toBeNull()
  })

  it('promotes composed primary over weaker page-1 multi-answer candidate', () => {
    const composed =
      'In both years, deficit irrigation resulted in a considerable reduction in wheat yield.'
    const weak =
      'Raw wastewater irrigation substantially reduced fertilizer requirement of wheat.'
    const model = buildHomeResearchDisplayModel({
      queryResponse: {
        answer: composed,
        user_presentation: {
          primary_answer: composed,
          human_status: 'answered',
          candidates: [],
          sources: [],
        },
        stable_result_set: {
          created: true,
          result_set_id: 'srs_prom',
        },
      },
      stablePage: {
        result_set_id: 'srs_prom',
        page: 1,
        page_size: 10,
        total_answers: 2,
        total_pages: 1,
        answers: [
          { answer: weak, result_id: 'weak', position: 0 },
          { answer: composed, result_id: 'strong', position: 1 },
        ],
        rerun_performed: false,
      },
    })

    expect(model.answers[0]?.answer).toBe(composed)
    expect(model.answers[1]?.answer).toBe(weak)
  })
})
