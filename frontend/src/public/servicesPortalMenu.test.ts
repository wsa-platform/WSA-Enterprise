import { describe, expect, it } from 'vitest'
import {
  SERVICES_PORTAL_MENU_ITEMS,
  SERVICES_PORTAL_ROUTES,
  cropsMenuReducer,
  isServicesPortalPageId,
  isServicesPortalPath,
  servicesPortalMenuItem,
} from './servicesPortalMenu'

describe('services portal menu data', () => {
  it('exposes exactly four dropdown destinations with semantic routes', () => {
    expect(SERVICES_PORTAL_MENU_ITEMS).toHaveLength(4)
    expect(SERVICES_PORTAL_MENU_ITEMS.map((item) => item.id)).toEqual([
      'employment',
      'market',
      'plant-ai-diagnosis',
      'projects',
    ])
    expect(SERVICES_PORTAL_MENU_ITEMS.map((item) => item.to)).toEqual([
      '/jobs/enter',
      '/market',
      '/services/plant-ai-diagnosis',
      '/services/projects',
    ])
    expect(SERVICES_PORTAL_ROUTES).toEqual({
      employment: '/jobs/enter',
      market: '/market',
      'plant-ai-diagnosis': '/services/plant-ai-diagnosis',
      projects: '/services/projects',
    })
  })

  it('resolves service helpers and active path detection', () => {
    expect(isServicesPortalPageId('employment')).toBe(false)
    expect(isServicesPortalPageId('market')).toBe(false)
    expect(isServicesPortalPageId('unknown')).toBe(false)
    expect(servicesPortalMenuItem('employment').to).toBe('/jobs/enter')
    expect(servicesPortalMenuItem('projects').to).toBe('/services/projects')
    expect(isServicesPortalPath('/jobs/enter')).toBe(true)
    expect(isServicesPortalPath('/jobs/enter/seeker')).toBe(true)
    expect(isServicesPortalPath('/market')).toBe(true)
    expect(isServicesPortalPath('/market/42')).toBe(true)
    expect(isServicesPortalPath('/sections/jobs')).toBe(false)
  })
})

describe('services portal menu reducer', () => {
  it('opens on hover and closes on leave commit', () => {
    expect(cropsMenuReducer(false, { type: 'pointer_enter' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'pointer_leave_request' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'pointer_leave_commit' })).toBe(false)
  })

  it('toggles for touch interaction', () => {
    expect(cropsMenuReducer(false, { type: 'toggle' })).toBe(true)
    expect(cropsMenuReducer(true, { type: 'toggle' })).toBe(false)
  })
})
