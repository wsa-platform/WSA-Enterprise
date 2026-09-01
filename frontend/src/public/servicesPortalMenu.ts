import { publicPaths } from '../navigation/paths'
import { cropsMenuReducer, type CropsMenuAction } from './cropsMenu'

export const SERVICES_PORTAL_ROUTES = {
  employment: '/services/employment',
  market: publicPaths.market,
  'plant-ai-diagnosis': '/services/plant-ai-diagnosis',
  projects: '/services/projects',
} as const

export type ServicesPortalServiceId = keyof typeof SERVICES_PORTAL_ROUTES

/** Service slugs backed by dedicated ServicesPortalPage components. */
export type ServicesPortalPageId = 'employment' | 'plant-ai-diagnosis' | 'projects'

export type ServicesPortalMenuItem = {
  id: ServicesPortalServiceId
  to: (typeof SERVICES_PORTAL_ROUTES)[ServicesPortalServiceId]
  icon: string
  labelKey: string
}

/** Header dropdown destinations for بوابة الخدمات. */
export const SERVICES_PORTAL_MENU_ITEMS: readonly ServicesPortalMenuItem[] = [
  {
    id: 'employment',
    to: SERVICES_PORTAL_ROUTES.employment,
    icon: '👨‍🌾',
    labelKey: 'website.servicesPortal.employment',
  },
  {
    id: 'market',
    to: SERVICES_PORTAL_ROUTES.market,
    icon: '🛒',
    labelKey: 'website.servicesPortal.market',
  },
  {
    id: 'plant-ai-diagnosis',
    to: SERVICES_PORTAL_ROUTES['plant-ai-diagnosis'],
    icon: '🔬',
    labelKey: 'website.servicesPortal.plantAiDiagnosis',
  },
  {
    id: 'projects',
    to: SERVICES_PORTAL_ROUTES.projects,
    icon: '🏡',
    labelKey: 'website.servicesPortal.projects',
  },
] as const

export { cropsMenuReducer, type CropsMenuAction as ServicesPortalMenuAction }

export function isServicesPortalPageId(
  value: string | undefined,
): value is ServicesPortalPageId {
  return value === 'employment' || value === 'plant-ai-diagnosis' || value === 'projects'
}

export function servicesPortalMenuItem(id: ServicesPortalServiceId): ServicesPortalMenuItem {
  const item = SERVICES_PORTAL_MENU_ITEMS.find((entry) => entry.id === id)
  if (!item) throw new Error(`Unknown services portal item: ${id}`)
  return item
}

export function isServicesPortalPath(pathname: string): boolean {
  return pathname.startsWith('/services/') || pathname === '/market' || pathname.startsWith('/market/')
}
