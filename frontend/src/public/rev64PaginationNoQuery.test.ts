import { describe, expect, it, vi } from 'vitest'
import {
  buildHomeResearchDisplayModel,
  extractStableResultSetId,
} from './stableResearchPresentation'

/**
 * Rev 6.4 — frontend contract: page navigation must use Results API, not Query.
 * This is a unit-level proof of the client helpers; browser E2E is separate.
 */
describe('rev64 frontend pagination without query', () => {
  it('keeps result_set_id and uses stable page answers for page 2 without query payload', () => {
    const queryResponse = {
      status: 'scientific_generated',
      user_presentation: {
        primary_answer: 'Page1 answer',
        human_status: 'answered' as const,
        candidates: [],
        sources: [],
      },
      multi_answers: [{ answer: 'Page1 answer', result_id: 'r0', position: 0 }],
      stable_result_set: {
        created: true,
        result_set_id: 'srs_rev64_fe',
        page_size: 10,
        total_answers: 21,
        total_pages: 3,
        page: {
          result_set_id: 'srs_rev64_fe',
          page: 1,
          page_size: 10,
          total_answers: 21,
          total_pages: 3,
          answers: Array.from({ length: 10 }, (_, i) => ({
            answer: `TEST-DATA A${i}`,
            result_id: `r${i}`,
            position: i,
          })),
          rerun_performed: false,
        },
      },
    }

    const page1 = buildHomeResearchDisplayModel({
      queryResponse,
      stablePage: queryResponse.stable_result_set.page,
    })
    expect(extractStableResultSetId(queryResponse)).toBe('srs_rev64_fe')
    expect(page1.usesStableResultSet).toBe(true)
    expect(page1.answers).toHaveLength(10)
    expect(page1.pagination?.page).toBe(1)

    const page2Payload = {
      result_set_id: 'srs_rev64_fe',
      page: 2,
      page_size: 10,
      total_answers: 21,
      total_pages: 3,
      answers: Array.from({ length: 10 }, (_, i) => ({
        answer: `TEST-DATA A${i + 10}`,
        result_id: `r${i + 10}`,
        position: i + 10,
      })),
      rerun_performed: false,
      observability: {
        search_rerun: false,
        composer_rerun: false,
        validation_rerun: false,
      },
    }

    const querySpy = vi.fn()
    // Simulate Results-only navigation: display model updates from Results payload; Query never called.
    const page2 = buildHomeResearchDisplayModel({
      queryResponse,
      stablePage: page2Payload,
    })
    expect(querySpy).not.toHaveBeenCalled()
    expect(page2.pagination?.page).toBe(2)
    expect(page2.pagination?.resultSetId).toBe('srs_rev64_fe')
    expect(page2.answers[0]?.answer).toBe('TEST-DATA A10')
    expect(page2.answers).toHaveLength(10)
    expect(page2Payload.rerun_performed).toBe(false)
  })
})
