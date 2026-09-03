import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from './client'
import { queryPublicResearchAgent } from './researchAgent'
import { queryPublicResearchAgent as queryFromBarrel } from './index'

describe('researchAgent API', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    vi.stubEnv('VITE_PUBLIC_ORG_SLUG', 'wsa-demo')
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
})
