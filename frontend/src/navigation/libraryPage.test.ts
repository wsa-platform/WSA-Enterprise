import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import { MemoryRouter } from 'react-router-dom'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { AuthProvider } from '../context/AuthContext'
import i18n from '../i18n/config'
import { PUBLIC_TOP_NAV_ITEMS, publicPaths } from './paths'
import { LibraryPage } from '../pages/public/LibraryPage'
import {
  LIBRARY_PLANT_PRODUCTION_CATEGORIES,
  LIBRARY_PLANT_PRODUCTION_SECTION_TITLE,
} from '../public/library/libraryPlantProductionCategories'
import {
  getLibraryCropSectionLabel,
  getLibraryCropSectionsForCrop,
} from '../public/library/libraryCropSections'
import { getLibraryCropsForCategory } from '../public/library/libraryCrops'

function renderLibraryPage() {
  return renderToStaticMarkup(
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
}

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

  it('renders public header, library body, and footer without workspace chrome', () => {
    const html = renderLibraryPage()

    expect(html).toContain('class="gs-header')
    expect(html).toContain('class="gs-footer')
    expect(html).toContain('class="library-page"')
    expect(html).toContain(LIBRARY_PLANT_PRODUCTION_SECTION_TITLE)
    expect(html).not.toContain('app-shell')
    expect(html).not.toContain('dashboard')
    expect(html).not.toContain('workspace')
    expect(html).not.toContain('password')
    expect(html).not.toContain('إنشاء عنصر مكتبة')
  })

  it('shows الإنتاج النباتي as the only sidebar section and all 12 categories', () => {
    const html = renderLibraryPage()
    const sidebarMatches = html.match(/library-page__sidebar-item/g) ?? []

    expect(sidebarMatches).toHaveLength(1)
    expect(LIBRARY_PLANT_PRODUCTION_CATEGORIES).toHaveLength(12)

    for (const category of LIBRARY_PLANT_PRODUCTION_CATEGORIES) {
      expect(html).toContain(category.name)
    }

    expect(html).toContain('النخيل')
    expect(html).not.toContain('محاصيل النخيل')
    expect(html.match(/library-page__category-card/g)?.length).toBe(12)
  })

  it('phase 2: grains category exposes existing wheat corn oats and sorghum crops', () => {
    const cropIds = getLibraryCropsForCategory('grains').map((crop) => crop.id)
    expect(cropIds).toEqual(expect.arrayContaining(['wheat', 'corn', 'oats', 'sorghum']))
  })

  it('phase 2: each crop has four dynamic sections', () => {
    for (const cropName of ['القمح', 'الذرة', 'الشوفان', 'الذرة الرفيعة']) {
      const sections = getLibraryCropSectionsForCrop(cropName)
      expect(sections).toHaveLength(4)
      expect(sections[0]?.label).toBe('الزراعة والاحتياجات الزراعية')
      expect(sections[1]?.label).toBe(getLibraryCropSectionLabel('scientific-research', cropName))
    }
  })

  it('phase 2: library page has no dashboard workspace or library password UI', () => {
    const html = renderLibraryPage()
    expect(html).not.toMatch(/library-dashboard|library-workspace|library-account/i)
    expect(html).not.toContain('Library Dashboard')
    expect(html).not.toContain('Library Workspace')
  })
})
