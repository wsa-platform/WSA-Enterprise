import { createElement, type ReactNode } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { ApiError } from '../api/client'
import { AuthProvider } from '../context/AuthContext'
import i18n from '../i18n/config'
import { HomePage } from '../pages/public/HomePage'
import { I18nextProvider } from 'react-i18next'
import { MemoryRouter } from 'react-router-dom'
import {
  HomeScientificResearchSearchView,
  normalizeResearchQuery,
  resolveResearchSearchError,
} from './HomeScientificResearchSearch'

beforeAll(() => {
  const store = new Map<string, string>()
  vi.stubGlobal('localStorage', {
    getItem: (key: string) => store.get(key) ?? null,
    setItem: (key: string, value: string) => {
      store.set(key, String(value))
    },
    removeItem: (key: string) => {
      store.delete(key)
    },
    clear: () => store.clear(),
  })
})

function renderWithProviders(node: ReactNode) {
  return renderToStaticMarkup(
    createElement(
      AuthProvider,
      null,
      createElement(
        I18nextProvider,
        { i18n },
        createElement(MemoryRouter, null, node),
      ),
    ),
  )
}

function renderView(props: {
  query: string
  loading: boolean
  error: string | null
  result: Parameters<typeof HomeScientificResearchSearchView>[0]['result']
  feedbackState?: Parameters<typeof HomeScientificResearchSearchView>[0]['feedbackState']
  onPositiveFeedback?: () => void
}) {
  return renderToStaticMarkup(
    createElement(
      I18nextProvider,
      { i18n },
      createElement(HomeScientificResearchSearchView, {
        ...props,
        onQueryChange: () => undefined,
        onSubmit: () => undefined,
      }),
    ),
  )
}

describe('homepage scientific research search', () => {
  it('renders Arabic research section after hero without touching header market search', async () => {
    await i18n.changeLanguage('ar')
    const html = renderWithProviders(createElement(HomePage))
    expect(html).toContain('البحث العلمي الزراعي')
    expect(html).toContain('بحث علمي')
    expect(html).toContain('hp-research-section')
    expect(html).toContain('for="home-research-query"')
    expect(html).toContain('id="public-header-search"')
    expect(html.indexOf('hp-hero')).toBeLessThan(html.indexOf('hp-research-section'))
    expect(html.indexOf('hp-research-section')).toBeLessThan(html.indexOf('home-categories'))
  })

  it('prevents empty or whitespace-only queries', () => {
    expect(normalizeResearchQuery('')).toBeNull()
    expect(normalizeResearchQuery('   ')).toBeNull()
    expect(normalizeResearchQuery('  ري الذرة  ')).toBe('ري الذرة')
  })

  it('shows loading state copy and disables controls', async () => {
    await i18n.changeLanguage('ar')
    const html = renderView({
      query: 'ري الذرة',
      loading: true,
      error: null,
      result: null,
    })
    expect(html).toContain('جاري البحث في المصادر العلمية...')
    expect(html).toContain('disabled')
    expect(html).toContain('value="ري الذرة"')
  })

  it('shows success answer and citation fields', async () => {
    await i18n.changeLanguage('ar')
    const html = renderView({
      query: 'ري الذرة',
      loading: false,
      error: null,
      result: {
        status: 'completed',
        answer: 'إجابة موثقة من الخادم',
        citations: [
          {
            title: 'Irrigation Study',
            doi: '10.1000/irrigation',
            url: 'https://example.test/paper',
            source_type: 'journal',
          },
        ],
      },
    })
    expect(html).toContain('إجابة موثقة من الخادم')
    expect(html).toContain('Irrigation Study')
    expect(html).toContain('DOI: 10.1000/irrigation')
    expect(html).toContain('https://example.test/paper')
    expect(html).toContain('الحالة: completed')
  })

  it('maps ApiError to localized user-facing messages without stack traces', async () => {
    await i18n.changeLanguage('ar')
    const message = resolveResearchSearchError(new ApiError('server boom', 503))
    expect(message).toContain('تعذر الاتصال بخدمة البحث العلمي')
    expect(message).not.toContain('Error:')
    expect(message).not.toContain('stack')

    const html = renderView({
      query: 'ري الذرة',
      loading: false,
      error: message,
      result: null,
    })
    expect(html).toContain('role="alert"')
    expect(html).toContain('تعذر الاتصال بخدمة البحث العلمي')
  })

  it('maps HTTP 504 timeout to search-duration copy, not server-down copy', async () => {
    await i18n.changeLanguage('ar')
    const timeoutMessage = resolveResearchSearchError(new ApiError('gateway timeout', 504))
    expect(timeoutMessage).toContain('انتهت مهلة البحث العلمي')
    expect(timeoutMessage).not.toContain('تعذر الاتصال بخدمة البحث العلمي')

    const unavailable = resolveResearchSearchError(new ApiError('offline', 0))
    expect(unavailable).toContain('تعذر الاتصال بخدمة البحث العلمي')

    const proxyDown = resolveResearchSearchError(new ApiError('bad gateway', 502))
    expect(proxyDown).toContain('تعذر الاتصال بخدمة البحث العلمي')

    const searchFailed = resolveResearchSearchError(new ApiError('internal error', 500))
    expect(searchFailed).toContain('تعذر إكمال البحث العلمي حالياً')
    expect(searchFailed).not.toContain('تعذر الاتصال بخدمة البحث العلمي')
  })

  it('renders English research chrome when the platform language is English', async () => {
    await i18n.changeLanguage('en')
    const html = renderView({
      query: 'wheat irrigation',
      loading: false,
      error: null,
      result: null,
    })
    expect(html).toContain('Agricultural scientific research')
    expect(html).toContain('Scientific search')
    expect(html).not.toContain('البحث العلمي الزراعي')
  })

  it('renders conflicts and uncertainty from Stage 5 fields without replacing the answer', async () => {
    await i18n.changeLanguage('en')
    const html = renderView({
      query: 'wheat irrigation',
      loading: false,
      error: null,
      result: {
        status: 'completed',
        answer: 'Scientific answer in English.',
        confidence: 0.72,
        limitations: ['limited_geo_coverage'],
        uncertainty: 'competing_sources',
        conflicts: [{ detail: 'irrigation amounts differ' }],
        citations: [{ title: 'Source A', url: 'https://example.org/paper' }],
      },
    })
    expect(html).toContain('Scientific answer in English.')
    expect(html).toContain('data-testid="home-research-confidence"')
    expect(html).toContain('limited_geo_coverage')
    expect(html).toContain('data-testid="home-research-uncertainty"')
    expect(html).toContain('competing_sources')
    expect(html).toContain('data-testid="home-research-conflicts"')
    expect(html).toContain('irrigation amounts differ')
    expect(html).toContain('Source A')
    expect(html).toContain('href="https://example.org/paper"')
    expect(html).toContain('>Source A</a>')
  })

  it('omits conflict and uncertainty blocks when fields are absent', async () => {
    await i18n.changeLanguage('en')
    const html = renderView({
      query: 'wheat irrigation',
      loading: false,
      error: null,
      result: {
        status: 'completed',
        answer: 'Answer only',
        citations: [],
      },
    })
    expect(html).toContain('Answer only')
    expect(html).not.toContain('data-testid="home-research-conflicts"')
    expect(html).not.toContain('data-testid="home-research-uncertainty"')
    expect(html).toContain('data-testid="home-research-sources-empty"')
    expect(html).toContain('No eligible direct citations are available for this answer.')
    expect(html).not.toContain('No citations to display.')
  })

  it('represents empty citations as an honest ineligible source state', async () => {
    await i18n.changeLanguage('en')
    const html = renderView({
      query: 'wheat irrigation',
      loading: false,
      error: null,
      result: {
        status: 'insufficient_evidence',
        answer: 'Insufficient scientific evidence was found for a factual answer.',
        uncertainty: 'Insufficient scientific evidence was found for a factual answer.',
        citations: [],
      },
    })
    expect(html).toContain('data-testid="home-research-sources-empty"')
    expect(html).not.toContain('data-testid="home-research-uncertainty"')
    expect(html).toContain('Insufficient scientific evidence was found for a factual answer.')
    expect(html).not.toContain('No citations to display.')
  })

  it('renders additional information separately from the main answer', async () => {
    await i18n.changeLanguage('en')
    const html = renderView({
      query: 'wheat irrigation',
      loading: false,
      error: null,
      result: {
        status: 'completed',
        answer: 'Main scientific answer.',
        additional_information: 'Supporting context only.',
        citations: [{ title: 'Source A', url: 'https://example.org/paper' }],
      },
    })
    expect(html).toContain('Main scientific answer.')
    expect(html).toContain('data-testid="home-research-additional"')
    expect(html).toContain('Supporting context only.')
  })

  it('renders positive feedback control and success/error states', async () => {
    await i18n.changeLanguage('en')
    const idle = renderView({
      query: 'q',
      loading: false,
      error: null,
      result: { status: 'completed', answer: 'A', citations: [] },
      feedbackState: 'idle',
      onPositiveFeedback: () => undefined,
    })
    expect(idle).toContain('data-testid="home-research-feedback-positive"')
    expect(idle).toContain('This answer was useful')

    const success = renderView({
      query: 'q',
      loading: false,
      error: null,
      result: { status: 'completed', answer: 'A', citations: [] },
      feedbackState: 'success',
      onPositiveFeedback: () => undefined,
    })
    expect(success).toContain('data-testid="home-research-feedback-success"')
    expect(success).toContain('Thank you. Your feedback was recorded.')
    expect(success).toContain('disabled')

    const failure = renderView({
      query: 'q',
      loading: false,
      error: null,
      result: { status: 'completed', answer: 'A', citations: [] },
      feedbackState: 'error',
      onPositiveFeedback: () => undefined,
    })
    expect(failure).toContain('data-testid="home-research-feedback-error"')
    expect(failure).toContain('Could not submit feedback')
    expect(failure).toContain('Answer')
  })

  it('does not render feedback control when no handler is provided', async () => {
    await i18n.changeLanguage('en')
    const html = renderView({
      query: 'q',
      loading: false,
      error: null,
      result: { status: 'completed', answer: 'A', citations: [] },
    })
    expect(html).not.toContain('data-testid="home-research-feedback"')
  })

  it('renders Turkish and French research chrome from i18n', async () => {
    await i18n.changeLanguage('tr')
    const trHtml = renderView({
      query: 'sulama',
      loading: false,
      error: null,
      result: null,
    })
    expect(trHtml).toContain('Tarımsal bilimsel araştırma')
    expect(trHtml).toContain('Bilimsel arama')
    expect(trHtml).not.toContain('البحث العلمي الزراعي')

    await i18n.changeLanguage('fr')
    const frHtml = renderView({
      query: 'irrigation',
      loading: false,
      error: null,
      result: null,
    })
    expect(frHtml).toContain('Recherche scientifique agricole')
    expect(frHtml).toContain('Recherche scientifique')
    expect(frHtml).not.toContain('البحث العلمي الزراعي')
  })
})
