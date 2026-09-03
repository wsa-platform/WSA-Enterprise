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

  it('shows loading state copy and disables controls', () => {
    const html = renderToStaticMarkup(
      createElement(HomeScientificResearchSearchView, {
        query: 'ري الذرة',
        loading: true,
        error: null,
        result: null,
        onQueryChange: () => undefined,
        onSubmit: () => undefined,
      }),
    )
    expect(html).toContain('جاري البحث في المصادر العلمية...')
    expect(html).toContain('disabled')
    expect(html).toContain('value="ري الذرة"')
  })

  it('shows success answer and citation fields', () => {
    const html = renderToStaticMarkup(
      createElement(HomeScientificResearchSearchView, {
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
        onQueryChange: () => undefined,
        onSubmit: () => undefined,
      }),
    )
    expect(html).toContain('إجابة موثقة من الخادم')
    expect(html).toContain('Irrigation Study')
    expect(html).toContain('DOI: 10.1000/irrigation')
    expect(html).toContain('https://example.test/paper')
    expect(html).toContain('الحالة: completed')
  })

  it('maps ApiError to Arabic user-facing messages without stack traces', () => {
    const message = resolveResearchSearchError(new ApiError('server boom', 503))
    expect(message).toContain('تعذر الاتصال بخدمة البحث العلمي')
    expect(message).not.toContain('Error:')
    expect(message).not.toContain('stack')

    const html = renderToStaticMarkup(
      createElement(HomeScientificResearchSearchView, {
        query: 'ري الذرة',
        loading: false,
        error: message,
        result: null,
        onQueryChange: () => undefined,
        onSubmit: () => undefined,
      }),
    )
    expect(html).toContain('role="alert"')
    expect(html).toContain('تعذر الاتصال بخدمة البحث العلمي')
  })
})
