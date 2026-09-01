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

export const PUBLIC_TOP_NAV_ITEMS = [
  { to: publicPaths.home, labelKey: 'website.nav.home', end: true },
  { to: publicPaths.cropsField, labelKey: 'website.nav.plantProduction', end: true },
  { to: '/sections/beekeeping', labelKey: 'website.nav.honeyBees', end: false },
  { to: '/#home-categories', labelKey: 'website.nav.servicesPortal', end: false },
  { to: '/sections/training', labelKey: 'website.nav.training', end: false },
  { to: '/library', labelKey: 'website.nav.library', end: false },
  { to: '/sections/medicinal-plants', labelKey: 'website.nav.blog', end: false },
  { to: '/sections/store', labelKey: 'website.nav.store', end: false },
  { to: '/about', labelKey: 'website.nav.aboutPlatform', end: false },
] as const

export const PUBLIC_TOP_NAV_FORBIDDEN_PATHS = ['/sell', '/seller', '/blog'] as const
