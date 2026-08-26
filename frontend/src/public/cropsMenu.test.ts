import { createElement, type ReactNode } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import { MemoryRouter } from 'react-router-dom'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { AuthProvider } from '../context/AuthContext'
import i18n from '../i18n/config'
import { HomePage } from '../pages/public/HomePage'
import { CropsCategoryMenu } from './CropsCategoryMenu'
import {
  CROPS_CATEGORY_ITEMS,
  CROPS_CATEGORY_ROUTES,
  cropsMenuReducer,
} from './cropsMenu'

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

function renderMenu(defaultOpen = false) {
  return renderWithProviders(createElement(CropsCategoryMenu, { defaultOpen }))
}

describe('crops category menu data', () => {
  it('exposes exactly three submenu destinations with semantic routes', () => {
    expect(CROPS_CATEGORY_ITEMS).toHaveLength(3)
    expect(CROPS_CATEGORY_ITEMS.map((item) => item.id)).toEqual(['field', 'sugar', 'forage'])
    expect(CROPS_CATEGORY_ITEMS.map((item) => item.to)).toEqual([
      '/crops/field',
      '/crops/sugar',
      '/crops/forage',
    ])
    expect(CROPS_CATEGORY_ROUTES).toEqual({
      field: '/crops/field',
      sugar: '/crops/sugar',
      forage: '/crops/forage',
    })
  })
})

describe('cropsMenuReducer', () => {
  it('opens on hover/focus and click-open', () => {
    expect(cropsMenuReducer(false, { type: 'pointer_enter' })).toBe(true)
    expect(cropsMenuReducer(false, { type: 'open' })).toBe(true)
  })

  it('toggles for touch interaction', () => {
    expect(cropsMenuReducer(false, { type: 'toggle' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'toggle' })).toBe(false)
  })

  it('closes on escape and leave commit without flicker on leave request', () => {
    expect(cropsMenuReducer(true, { type: 'pointer_leave_request' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'pointer_leave_commit' })).toBe(false)
    expect(cropsMenuReducer(true, { type: 'escape' })).toBe(false)
    expect(cropsMenuReducer(true, { type: 'close' })).toBe(false)
  })
})

describe('CropsCategoryMenu markup', () => {
  it('renders المحاصيل on the homepage category surface', async () => {
    await i18n.changeLanguage('ar')
    const html = renderWithProviders(createElement(HomePage))
    expect(html).toContain('المحاصيل')
    expect(html).toContain('gs-crops-menu')
    expect(html).toContain('aria-haspopup="menu"')
  })

  it('opens the submenu with exactly three Arabic options and routes', async () => {
    await i18n.changeLanguage('ar')
    const closed = renderMenu(false)
    expect(closed).toContain('hidden')
    expect(closed).toContain('aria-expanded="false"')

    const open = renderMenu(true)
    expect(open).toContain('aria-expanded="true"')
    expect(open).toContain('is-open')
    expect(open).toContain('محاصيل الحقل')
    expect(open).toContain('محاصيل سكرية')
    expect(open).toContain('محاصيل أعلاف')
    expect(open).toContain('href="/crops/field"')
    expect(open).toContain('href="/crops/sugar"')
    expect(open).toContain('href="/crops/forage"')
    expect(open).toContain('🌾')
    expect(open).toContain('🍬')
    expect(open).toContain('🌿')
    expect(open.match(/gs-crops-submenu-item/g)?.length).toBe(3)
  })

  it('keeps accessible menu semantics for keyboard/focus usage', async () => {
    await i18n.changeLanguage('ar')
    const open = renderMenu(true)
    expect(open).toContain('role="menu"')
    expect(open).toContain('role="menuitem"')
    expect(open).toContain('aria-controls')
  })

  it('preserves other homepage category cards', async () => {
    await i18n.changeLanguage('ar')
    const html = renderWithProviders(createElement(HomePage))
    expect(html).toContain('محاصيل الخضروات')
    expect(html).toContain('href="/sections/vegetables"')
    expect(html).toContain('سوق المنتجات')
  })
})
