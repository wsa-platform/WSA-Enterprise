import { describe, expect, it } from 'vitest'
import { buildInternalBreadcrumbs, flattenNavTargets, visibleNavSections } from './internalNav'
import {
  PUBLIC_TOP_NAV_FORBIDDEN_PATHS,
  PUBLIC_TOP_NAV_ITEMS,
  REQUIRED_INTERNAL_NAV_PATHS,
  internalPaths,
  publicPaths,
} from './paths'
import { PLANT_PRODUCTION_CATEGORY_ITEMS } from '../public/plantProductionMenu'
import { SERVICES_PORTAL_MENU_ITEMS } from '../public/servicesPortalMenu'
import { loginPathForProtectedRoute, safeReturnPath, unknownRouteFallback } from './routeGuards'

describe('internal navigation map', () => {
  const targets = flattenNavTargets()

  it('covers the required authenticated destinations', () => {
    expect(targets).toEqual(expect.arrayContaining([...REQUIRED_INTERNAL_NAV_PATHS]))
    expect(targets).not.toContain('/dashboard')
    expect(targets).toContain(internalPaths.account)
    expect(targets).toContain(internalPaths.profile)
    expect(targets).not.toContain('/account/products')
    expect(targets).not.toContain('/account/products/new')
    expect(targets).toContain(publicPaths.market)
  })

  it('does not keep Marketplace Seller product management inside the ERP workspace nav', () => {
    expect(targets).not.toContain(internalPaths.products)
    expect(targets).not.toContain(internalPaths.newProduct)
    expect(targets.some((path) => path.startsWith('/seller'))).toBe(false)
    expect(targets).not.toContain('/cart')
    expect(targets).not.toContain('/checkout')
  })

  it('hides permission-gated items while access is loading', () => {
    const sections = visibleNavSections(() => true, true)
    const loadingTargets = flattenNavTargets(sections)
    expect(loadingTargets).toContain(internalPaths.account)
    expect(loadingTargets).toContain(internalPaths.profile)
    expect(loadingTargets).toContain(publicPaths.market)
  })
})

describe('internal breadcrumbs', () => {
  const t = (key: string) => key

  it('points the home crumb at the account workspace, not the legacy dashboard', () => {
    const crumbs = buildInternalBreadcrumbs(internalPaths.profile, t)
    expect(crumbs[0]).toEqual({ label: 'nav.home', to: '/account' })
    expect(crumbs[0]?.to).not.toBe('/')
    expect(crumbs[0]?.to).not.toBe('/dashboard')
  })

  it('distinguishes add-product from edit-product crumbs', () => {
    expect(buildInternalBreadcrumbs(internalPaths.newProduct, t).at(-1)?.label).toBe('market.addProduct')
    expect(buildInternalBreadcrumbs('/seller/listings/42', t).at(-1)?.label).toBe('market.editProduct')
  })
})

describe('route guards', () => {
  it('sends authenticated unknown URLs away from a hard-coded dashboard default', () => {
    expect(unknownRouteFallback(true)).toBe('/account')
    expect(unknownRouteFallback(false)).toBe('/')
  })

  it('rejects open redirects when returning after login', () => {
    expect(safeReturnPath('/account/products')).toBe('/account/products')
    expect(safeReturnPath('https://evil.example')).toBe('/')
    expect(safeReturnPath('//evil.example')).toBe('/')
    expect(loginPathForProtectedRoute('/account/profile')).toBe('/login?next=%2Faccount%2Fprofile')
  })
})

describe('public top navigation regression', () => {
  it('exposes the approved nine-item public header menu and keeps sell/blog paths out', () => {
    expect(PUBLIC_TOP_NAV_ITEMS).toHaveLength(9)
    const linkPaths = PUBLIC_TOP_NAV_ITEMS
      .filter((item) => item.kind === 'link')
      .map((item) => item.to)
    expect(linkPaths).toEqual([
      '/',
      '/sections/beekeeping',
      '/sections/training',
      '/agricultural-market',
      '/sections/medicinal-plants',
      '/sections/store',
      '/about',
    ])
    expect(PUBLIC_TOP_NAV_ITEMS.some((item) => item.kind === 'plantProduction')).toBe(true)
    expect(PUBLIC_TOP_NAV_ITEMS.some((item) => item.kind === 'servicesPortal')).toBe(true)
    expect(PLANT_PRODUCTION_CATEGORY_ITEMS).toHaveLength(5)
    expect(PLANT_PRODUCTION_CATEGORY_ITEMS.map((item) => item.to)).toEqual([
      '/plant-production/field-crops',
      '/plant-production/vegetable-crops',
      '/plant-production/fruit-trees',
      '/plant-production/ornamental-plants',
      '/plant-production/medicinal-aromatic-plants',
    ])
    expect(SERVICES_PORTAL_MENU_ITEMS).toHaveLength(4)
    expect(SERVICES_PORTAL_MENU_ITEMS.map((item) => item.to)).toEqual([
      '/jobs/enter',
      '/market',
      '/services/plant-ai-diagnosis',
      '/services/projects',
    ])
    for (const forbidden of PUBLIC_TOP_NAV_FORBIDDEN_PATHS) {
      expect(linkPaths).not.toContain(forbidden)
    }
  })
})
