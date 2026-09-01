export const internalPaths = {
  account: '/account',
  profile: '/account/profile',
  products: '/seller/listings',
  newProduct: '/seller/listings/new',
  editProduct: (listingId: number | string) => `/seller/listings/${listingId}`,
  settings: '/settings',
  login: '/login',
  jobSeekerEnter: '/jobs/enter',
  jobSeekerHome: '/jobs/application',
  employerHome: '/employer',
} as const

export const publicPaths = {
  home: '/',
  market: '/market',
  listing: (id: number | string) => `/market/${id}`,
  cropsField: '/crops/field',
  cropsSugar: '/crops/sugar',
  cropsForage: '/crops/forage',
} as const

export const REQUIRED_INTERNAL_NAV_PATHS = [
  internalPaths.account,
  internalPaths.profile,
  publicPaths.market,
] as const

export type PublicNavLinkItem = {
  kind: 'link'
  to: string
  labelKey: string
  end?: boolean
}

export type PublicNavPlantProductionItem = {
  kind: 'plantProduction'
  labelKey: 'website.nav.plantProduction'
}

export type PublicNavServicesPortalItem = {
  kind: 'servicesPortal'
  labelKey: 'website.nav.servicesPortal'
}

export type PublicNavItem = PublicNavLinkItem | PublicNavPlantProductionItem | PublicNavServicesPortalItem

export const PUBLIC_TOP_NAV_ITEMS: readonly PublicNavItem[] = [
  { kind: 'link', to: publicPaths.home, labelKey: 'website.nav.home', end: true },
  { kind: 'plantProduction', labelKey: 'website.nav.plantProduction' },
  { kind: 'link', to: '/sections/beekeeping', labelKey: 'website.nav.honeyBees', end: false },
  { kind: 'servicesPortal', labelKey: 'website.nav.servicesPortal' },
  { kind: 'link', to: '/sections/training', labelKey: 'website.nav.training', end: false },
  { kind: 'link', to: '/library', labelKey: 'website.nav.library', end: false },
  { kind: 'link', to: '/sections/medicinal-plants', labelKey: 'website.nav.blog', end: false },
  { kind: 'link', to: '/sections/store', labelKey: 'website.nav.store', end: false },
  { kind: 'link', to: '/about', labelKey: 'website.nav.aboutPlatform', end: false },
] as const

export const PUBLIC_TOP_NAV_FORBIDDEN_PATHS = ['/sell', '/seller', '/blog'] as const
