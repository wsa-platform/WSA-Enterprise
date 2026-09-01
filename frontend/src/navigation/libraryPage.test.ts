import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import { MemoryRouter } from 'react-router-dom'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { AuthProvider } from '../context/AuthContext'
import i18n from '../i18n/config'
import { PUBLIC_TOP_NAV_ITEMS, publicPaths } from './paths'
import { LibraryPage } from '../pages/public/LibraryPage'

describe('library page', () => {
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
    expect(publicPaths.library).toBe('/library')
  })

  it('places المكتبة directly after training in the public header menu', () => {
    const trainingIndex = PUBLIC_TOP_NAV_ITEMS.findIndex(
      (item) => item.kind === 'link' && item.to === '/sections/training',
    )
    const libraryIndex = PUBLIC_TOP_NAV_ITEMS.findIndex(
      (item) => item.kind === 'link' && item.to === publicPaths.library,
    )

    expect(trainingIndex).toBeGreaterThanOrEqual(0)
    expect(libraryIndex).toBe(trainingIndex + 1)
    expect(i18n.t('website.nav.library')).toBe('المكتبة')
  })

  it('renders public header and footer with an empty body only', () => {
    const html = renderToStaticMarkup(
      createElement(
        AuthProvider,
        null,
        createElement(
          I18nextProvider,
          { i18n },
          createElement(
            MemoryRouter,
            { initialEntries: [publicPaths.library] },
            createElement(LibraryPage),
          ),
        ),
      ),
    )

    expect(html).toMatch(/min-height:\s*calc\(100dvh - 18rem\)/)
    expect(html).toContain('background-color:#ffffff')
    expect(html).toContain('class="gs-header')
    expect(html).toContain('class="gs-footer')
    expect(html).not.toContain('app-shell')
    expect(html).not.toContain('dashboard')
    expect(html).not.toContain('workspace')
    expect(html).not.toContain('إنشاء عنصر مكتبة')
    expect(html).not.toContain('المكتبة الزراعية')
  })
})
