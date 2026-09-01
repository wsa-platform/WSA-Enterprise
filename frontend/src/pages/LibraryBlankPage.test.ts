import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { describe, expect, it } from 'vitest'
import { LibraryBlankPage } from './LibraryBlankPage'

describe('LibraryBlankPage', () => {
  it('renders a completely empty page', () => {
    const html = renderToStaticMarkup(createElement(LibraryBlankPage))
    expect(html).toBe('<div></div>')
  })

  it('does not render workspace, dashboard, or library admin UI', () => {
    const html = renderToStaticMarkup(
      createElement(
        MemoryRouter,
        { initialEntries: ['/library'] },
        createElement(
          Routes,
          null,
          createElement(Route, { path: '/library', element: createElement(LibraryBlankPage) }),
        ),
      ),
    )

    expect(html).toBe('<div></div>')
    expect(html).not.toContain('app-shell')
    expect(html).not.toContain('dashboard')
    expect(html).not.toContain('panel')
    expect(html).not.toContain('<form')
    expect(html).not.toContain('المكتبة الزراعية')
    expect(html).not.toContain('نشر العنصر')
    expect(html).not.toContain('إنشاء عنصر مكتبة')
    expect(html).not.toContain('<button')
    expect(html).not.toContain('<input')
  })
})
