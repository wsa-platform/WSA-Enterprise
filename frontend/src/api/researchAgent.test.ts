import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from './client'
import { getCurrentLanguage } from '../i18n/config'
import {
  buildHomePositiveFeedbackPayload,
  formatResearchConflictText,
  queryPublicResearchAgent,
  submitResearchFeedback,
} from './researchAgent'
import { queryPublicResearchAgent as queryFromBarrel } from './index'

describe('researchAgent API', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    vi.unstubAllEnvs()
  })

  it('is re-exported from the API barrel', () => {
    expect(queryFromBarrel).toBe(queryPublicResearchAgent)
  })

  it('POSTs organization + trimmed query to Laravel public research endpoint', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify({
          status: 'completed',
          answer: 'إجابة موثقة',
          citations: [{ title: 'Source A', doi: '10.1/a', url: 'https://example.test/a' }],
        }),
        { status: 200, headers: { 'Content-Type': 'application/json' } },
      ),
    )

    const result = await queryPublicResearchAgent('  ما ري الذرة؟  ')

    expect(result.answer).toBe('إجابة موثقة')
    expect(result.citations?.[0]?.title).toBe('Source A')
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/public/research-agent/query')
    expect(init?.method).toBe('POST')
    expect(JSON.parse(String(init?.body))).toEqual({
      organization: 'wsa-demo',
      query: 'ما ري الذرة؟',
    })
    expect(init?.headers).toEqual(
      expect.objectContaining({
        'Accept-Language': getCurrentLanguage(),
      }),
    )
  })

  it('surfaces ApiError without calling external scholarly hosts from the browser', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ message: 'Organization not found.' }), {
        status: 404,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    await expect(queryPublicResearchAgent('irrigation')).rejects.toBeInstanceOf(ApiError)
    const [url] = (globalThis.fetch as ReturnType<typeof vi.fn>).mock.calls[0]
    expect(String(url)).not.toContain('openalex')
    expect(String(url)).not.toContain('crossref')
  })

  it('formats Stage 5 conflict strings and detail maps without inventing science', () => {
    expect(formatResearchConflictText('amounts differ')).toBe('amounts differ')
    expect(formatResearchConflictText({ detail: 'irrigation amounts differ' })).toBe(
      'irrigation amounts differ',
    )
  })

  it('builds Home positive-only feedback payload for the existing R7 API', () => {
    const payload = buildHomePositiveFeedbackPayload({
      question: '  wheat irrigation?  ',
      uiLocale: 'en',
      answerLanguage: 'ar',
    })
    expect(payload).toEqual({
      polarity: 'positive',
      research_source: 'home',
      question: 'wheat irrigation?',
      ui_locale: 'en',
      answer_language: 'ar',
    })
    expect(payload.polarity).not.toBe('negative')
  })

  it('POSTs positive feedback to the existing R7 endpoint', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ status: 'recorded', persisted: true }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await submitResearchFeedback(
      buildHomePositiveFeedbackPayload({
        question: 'wheat irrigation?',
        uiLocale: 'en',
      }),
    )

    expect(result.persisted).toBe(true)
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/public/research-agent/feedback')
    expect(JSON.parse(String(init?.body))).toEqual({
      polarity: 'positive',
      research_source: 'home',
      question: 'wheat irrigation?',
      ui_locale: 'en',
    })
    expect(JSON.parse(String(init?.body)).polarity).not.toBe('negative')
  })
})
