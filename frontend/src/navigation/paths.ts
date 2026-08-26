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
  { to: publicPaths.market, labelKey: 'website.nav.market', end: true },
  { to: '/sections/small-projects', labelKey: 'website.nav.projects', end: false },
  { to: '/about', labelKey: 'website.nav.aboutPlatform', end: false },
  { to: '/#home-categories', labelKey: 'website.nav.services', end: false },
] as const

export const PUBLIC_TOP_NAV_FORBIDDEN_PATHS = ['/sell', '/seller', '/blog'] as const
