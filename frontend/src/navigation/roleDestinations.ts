import { getMeContext, getOrganizations } from '../api'
import type { User } from '../api'
import { internalPaths } from './paths'

export const JOB_SEEKER_HOME = '/jobs/application'
export const JOB_SEEKER_ENTER = '/jobs/enter'
export const JOB_SEEKER_AUTH_ENTER = '/jobs/enter/seeker'
export const EMPLOYER_HOME = '/employer'
export const EMPLOYER_ENTER = '/employer/enter'
export const ADMIN_HOME = '/admin/users'
export const ACCOUNT_HOME = internalPaths.account
export const LEGACY_DASHBOARD_PATH = '/dashboard'

export const AUDIENCE_STORAGE_KEY = 'wsa.auth.audience'
export const AUTH_NEXT_STORAGE_KEY = 'wsa.auth.next'
export const AUTH_PROVIDER_STORAGE_KEY = 'wsa.auth.provider'

export type AuthAudience = 'job_seeker' | 'employer' | 'admin'

export type RoleFlags = {
  isAdmin: boolean
  isEmployer: boolean
  isJobSeeker: boolean
}

function safePath(value: string | null | undefined, fallback = '/'): string {
  return value && value.startsWith('/') && !value.startsWith('//') ? value : fallback
}

export function parseAudience(value: string | null | undefined): AuthAudience | null {
  if (value === 'job_seeker' || value === 'employer' || value === 'admin') return value
  return null
}

export function persistAudience(audience: AuthAudience | null | undefined) {
  try {
    if (audience) {
      sessionStorage.setItem(AUDIENCE_STORAGE_KEY, audience)
      localStorage.setItem(AUDIENCE_STORAGE_KEY, audience)
      return
    }
    sessionStorage.removeItem(AUDIENCE_STORAGE_KEY)
    localStorage.removeItem(AUDIENCE_STORAGE_KEY)
  } catch {
    // ignore storage failures
  }
}

export function readStoredAudience(): AuthAudience | null {
  try {
    return parseAudience(sessionStorage.getItem(AUDIENCE_STORAGE_KEY))
      ?? parseAudience(localStorage.getItem(AUDIENCE_STORAGE_KEY))
  } catch {
    return null
  }
}

export function clearStoredAuthNavigation() {
  try {
    sessionStorage.removeItem(AUDIENCE_STORAGE_KEY)
    sessionStorage.removeItem(AUTH_NEXT_STORAGE_KEY)
    sessionStorage.removeItem(AUTH_PROVIDER_STORAGE_KEY)
    localStorage.removeItem(AUDIENCE_STORAGE_KEY)
  } catch {
    // ignore storage failures
  }
}

export function audienceFromPath(pathname: string): AuthAudience | null {
  if (
    pathname.startsWith('/jobs/application')
    || pathname.startsWith('/jobs/talent')
    || pathname.startsWith('/jobs/enter')
    || pathname.startsWith('/jobs/auth')
  ) {
    return 'job_seeker'
  }
  if (pathname.startsWith('/employer')) return 'employer'
  if (pathname.startsWith('/admin') || pathname === LEGACY_DASHBOARD_PATH) return 'admin'
  return null
}

export function roleFlagsFromPermissions(permissions: string[] | null | undefined): RoleFlags {
  const list = permissions ?? []
  const allowAll = list.includes('*')
  return {
    isAdmin: allowAll || list.includes('access.manage'),
    isEmployer: allowAll || list.includes('jobs.manage'),
    isJobSeeker: allowAll || list.includes('jobs.talent.register') || list.includes('jobs.talent.manage'),
  }
}

export function isUserDashboardPath(pathname: string): boolean {
  return pathname === LEGACY_DASHBOARD_PATH || pathname.startsWith(`${LEGACY_DASHBOARD_PATH}/`)
}

export function isMarketplacePath(pathname?: string | null): boolean {
  if (!pathname) return false
  return pathname === '/market'
    || pathname.startsWith('/market/')
    || pathname === '/seller/listings'
    || pathname.startsWith('/seller/listings/')
    || pathname === '/account/products'
    || pathname.startsWith('/account/products/')
}

export function canonicalSellerPath(pathname: string): string {
  if (pathname === '/account/products' || pathname === '/seller/listings') {
    return internalPaths.products
  }
  if (pathname === '/account/products/new' || pathname === '/seller/listings/new') {
    return internalPaths.newProduct
  }
  const listingMatch = pathname.match(/^\/(?:account\/products|seller\/listings)\/([^/]+)$/)
  if (listingMatch && listingMatch[1] !== 'new') {
    return internalPaths.editProduct(listingMatch[1])
  }
  return pathname
}

export function marketplaceReturnPath(pathname?: string | null): string {
  if (pathname && isMarketplacePath(pathname) && pathname !== '/market' && !pathname.startsWith('/market/')) {
    return canonicalSellerPath(pathname)
  }
  return internalPaths.products
}

export function loginHref(audience: AuthAudience | null, next: string): string {
  const params = new URLSearchParams()
  params.set('next', safePath(next))
  if (audience) params.set('audience', audience)
  return `/login?${params.toString()}`
}

export function registerHref(audience: AuthAudience | null, next: string): string {
  const params = new URLSearchParams()
  params.set('next', safePath(next))
  if (audience) params.set('audience', audience)
  return `/register?${params.toString()}`
}

export function marketplaceLoginHref(next: string = internalPaths.products): string {
  return loginHref(null, next)
}

export function marketplaceRegisterHref(next: string = internalPaths.newProduct): string {
  return registerHref(null, next)
}

export function shouldStayOnPlatformAuthPage(_audience: AuthAudience | null, next?: string | null): boolean {
  return Boolean(next && isMarketplacePath(next))
}

export function employerCreateAccountHref(): string {
  return registerHref('employer', EMPLOYER_HOME)
}

export function employerSignInHref(): string {
  return loginHref('employer', EMPLOYER_HOME)
}

export function shouldOpenEmployerRegisterForm(isEmployer: boolean): boolean {
  return !isEmployer
}

export function employerWorkspaceGate(role: { is_job_seeker: boolean; is_employer: boolean } | null): 'blocked' | 'activate' | 'workspace' {
  if (role?.is_job_seeker) return 'blocked'
  if (role?.is_employer) return 'workspace'
  return 'activate'
}

export function publicHeaderAudience(
  stored?: AuthAudience | null,
  pathname?: string,
): Exclude<AuthAudience, 'admin'> | null {
  const fromPath = pathname ? audienceFromPath(pathname) : null
  if (fromPath === 'employer') return 'employer'
  if (pathname && isMarketplacePath(pathname)) return null
  if (stored === 'employer') return 'employer'
  return 'job_seeker'
}

export function publicLoginHref(stored?: AuthAudience | null, pathname?: string): string {
  const audience = publicHeaderAudience(stored, pathname)
  if (audience === 'employer') return loginHref('employer', EMPLOYER_HOME)
  if (audience === 'job_seeker') return loginHref('job_seeker', JOB_SEEKER_HOME)
  return marketplaceLoginHref(marketplaceReturnPath(pathname))
}

export function publicRegisterHref(stored?: AuthAudience | null, pathname?: string): string {
  const audience = publicHeaderAudience(stored, pathname)
  if (audience === 'employer') return registerHref('employer', EMPLOYER_HOME)
  if (audience === 'job_seeker') return registerHref('job_seeker', JOB_SEEKER_HOME)
  return marketplaceRegisterHref(internalPaths.newProduct)
}

export function jobSeekerStartPath(isAuthenticated: boolean): string {
  return isAuthenticated ? JOB_SEEKER_HOME : JOB_SEEKER_AUTH_ENTER
}

export function jobSeekerLandingPath(isAuthenticated: boolean): string {
  return isAuthenticated ? JOB_SEEKER_HOME : JOB_SEEKER_AUTH_ENTER
}

export function employerStartPath(isAuthenticated: boolean): string {
  return isAuthenticated ? EMPLOYER_HOME : EMPLOYER_ENTER
}

export function employerLogoutPath(): string {
  return loginHref('employer', EMPLOYER_HOME)
}

export function destinationForRoles(
  roles: RoleFlags,
  audience: AuthAudience | null,
  requestedNext?: string | null,
): string {
  const requested = requestedNext ? safePath(requestedNext, '') : ''
  const usableNext = requested && !isUserDashboardPath(requested) ? requested : ''

  if (isMarketplacePath(usableNext)) return canonicalSellerPath(usableNext)

  if (audience === 'employer') {
    return usableNext.startsWith('/employer') ? usableNext : EMPLOYER_HOME
  }

  if (audience === 'job_seeker') {
    if (usableNext.startsWith('/jobs/application') || usableNext.startsWith('/jobs/talent')) return usableNext
    return JOB_SEEKER_HOME
  }

  if (roles.isAdmin) {
    if (usableNext.startsWith('/admin')) return usableNext
    return ADMIN_HOME
  }

  if (roles.isEmployer && !roles.isJobSeeker) {
    return usableNext.startsWith('/employer') ? usableNext : EMPLOYER_HOME
  }

  if (roles.isJobSeeker) {
    if (usableNext.startsWith('/jobs/application') || usableNext.startsWith('/jobs/talent')) return usableNext
    return JOB_SEEKER_HOME
  }

  if (usableNext) return usableNext
  return ACCOUNT_HOME
}

export async function resolvePostAuthPath(options: {
  token: string
  organizationId?: number | null
  audience?: AuthAudience | null
  next?: string | null
}): Promise<string> {
  const audience = options.audience ?? readStoredAudience()
  let permissions: string[] = []

  try {
    const organizationId = options.organizationId
      ?? (await getOrganizations(options.token))[0]?.id
      ?? null
    if (organizationId) {
      const context = await getMeContext(options.token, organizationId)
      permissions = context.permissions
    }
  } catch {
    permissions = []
  }

  return destinationForRoles(roleFlagsFromPermissions(permissions), audience, options.next)
}

export async function completeAuthenticatedSession(options: {
  token: string
  user: User
  audience?: AuthAudience | null
  next?: string | null
  setSession: (token: string, user: User) => void
  setOrganizationId: (organizationId: number | null) => void
}): Promise<string> {
  persistAudience(options.audience)
  options.setSession(options.token, options.user)
  const organizations = await getOrganizations(options.token)
  const organizationId = organizations[0]?.id ?? null
  if (organizationId) options.setOrganizationId(organizationId)
  else options.setOrganizationId(null)
  return resolvePostAuthPath({
    token: options.token,
    organizationId,
    audience: options.audience,
    next: options.next,
  })
}
