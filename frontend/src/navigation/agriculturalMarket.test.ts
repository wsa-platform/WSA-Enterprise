import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import { MemoryRouter } from 'react-router-dom'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { AuthProvider } from '../context/AuthContext'
import i18n from '../i18n/config'
import { PUBLIC_TOP_NAV_ITEMS, publicPaths } from './paths'
import { AgriculturalMarketPage } from '../pages/public/AgriculturalMarketPage'

describe('agricultural market page', () => {
  beforeAll(async () => {
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
    await i18n.changeLanguage('ar')
  })

  it('registers the public route path', () => {
    expect(publicPaths.agriculturalMarket).toBe('/agricultural-market')
  })

  it('places السوق الزراعي directly after training in the public header menu', () => {
    const trainingIndex = PUBLIC_TOP_NAV_ITEMS.findIndex(
      (item) => item.kind === 'link' && item.to === '/sections/training',
    )
    const agriculturalMarketIndex = PUBLIC_TOP_NAV_ITEMS.findIndex(
      (item) => item.kind === 'link' && item.to === publicPaths.agriculturalMarket,
    )

    expect(trainingIndex).toBeGreaterThanOrEqual(0)
    expect(agriculturalMarketIndex).toBe(trainingIndex + 1)
    expect(i18n.t('website.nav.agriculturalMarket')).toBe('السوق الزراعي')
  })

  it('renders a blank public page without workspace or dashboard chrome', () => {
    const html = renderToStaticMarkup(
      createElement(
        AuthProvider,
        null,
        createElement(
          I18nextProvider,
          { i18n },
          createElement(
            MemoryRouter,
            { initialEntries: [publicPaths.agriculturalMarket] },
            createElement(AgriculturalMarketPage),
          ),
        ),
      ),
    )

    expect(html).toContain('class="public-site"')
    expect(html).not.toContain('app-shell')
    expect(html).not.toContain('dashboard')
    expect(html).not.toContain('workspace')
  })
})
