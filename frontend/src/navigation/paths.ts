export const internalPaths = {
  dashboard: '/dashboard',
  account: '/account',
  profile: '/account/profile',
  products: '/account/products',
  newProduct: '/account/products/new',
  editProduct: (listingId: number | string) => `/account/products/${listingId}`,
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
} as const

export const REQUIRED_INTERNAL_NAV_PATHS = [
  internalPaths.dashboard,
  internalPaths.account,
  internalPaths.profile,
  internalPaths.products,
  internalPaths.newProduct,
  publicPaths.market,
] as const

export const PUBLIC_TOP_NAV_ITEMS = [
  { to: publicPaths.home, labelKey: 'website.nav.home', end: true },
] as const

export const PUBLIC_TOP_NAV_FORBIDDEN_PATHS = ['/market', '/sell', '/seller'] as const
