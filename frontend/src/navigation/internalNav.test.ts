import { describe, expect, it } from 'vitest'
import { buildInternalBreadcrumbs, flattenNavTargets, INTERNAL_NAV_SECTIONS, isNavItemVisible, visibleNavSections } from './internalNav'
import {
  PUBLIC_TOP_NAV_FORBIDDEN_PATHS,
  PUBLIC_TOP_NAV_ITEMS,
  REQUIRED_INTERNAL_NAV_PATHS,
  internalPaths,
  publicPaths,
} from './paths'
import { loginPathForProtectedRoute, safeReturnPath, unknownRouteFallback } from './routeGuards'

describe('internal navigation map', () => {
  const targets = flattenNavTargets()

  it('covers the required authenticated destinations', () => {
    expect(targets).toEqual(expect.arrayContaining([...REQUIRED_INTERNAL_NAV_PATHS]))
    expect(targets).toContain(internalPaths.dashboard)
    expect(targets).toContain(internalPaths.account)
    expect(targets).toContain(internalPaths.profile)
    expect(targets).toContain(internalPaths.products)
    expect(targets).toContain(internalPaths.newProduct)
    expect(targets).toContain(publicPaths.market)
  })

  it('does not invent a second product system or seller dashboard', () => {
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
    expect(loadingTargets).not.toContain(internalPaths.products)
    expect(loadingTargets).not.toContain(internalPaths.newProduct)
  })

  it('hides own-product links when the user lacks market permissions', () => {
    const can = (permission: string) => permission === 'platform.view'
    expect(isNavItemVisible(
      INTERNAL_NAV_SECTIONS.flatMap((section) => section.items).find((item) => item.to === internalPaths.products)!,
      can,
      false,
    )).toBe(false)
    expect(isNavItemVisible(
      INTERNAL_NAV_SECTIONS.flatMap((section) => section.items).find((item) => item.to === internalPaths.newProduct)!,
      can,
      false,
    )).toBe(false)
  })
})

describe('internal breadcrumbs', () => {
  const t = (key: string) => key

  it('points the home crumb at the authenticated dashboard', () => {
    const crumbs = buildInternalBreadcrumbs(internalPaths.profile, t)
    expect(crumbs[0]).toEqual({ label: 'nav.home', to: '/dashboard' })
    expect(crumbs[0]?.to).not.toBe('/')
  })

  it('distinguishes add-product from edit-product crumbs', () => {
    expect(buildInternalBreadcrumbs(internalPaths.newProduct, t).at(-1)?.label).toBe('market.addProduct')
    expect(buildInternalBreadcrumbs('/account/products/42', t).at(-1)?.label).toBe('market.editProduct')
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
  it('keeps market and sell links out of the public header', () => {
    const paths = PUBLIC_TOP_NAV_ITEMS.map((item) => item.to)
    for (const forbidden of PUBLIC_TOP_NAV_FORBIDDEN_PATHS) {
      expect(paths).not.toContain(forbidden)
    }
    expect(paths).toEqual(['/'])
  })
})
