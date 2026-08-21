import { getMeContext, getOrganizations } from '../api'
import type { User } from '../api'
import { internalPaths } from './paths'

export const JOB_SEEKER_HOME = '/jobs/application'
export const EMPLOYER_HOME = '/employer'
export const ADMIN_HOME = internalPaths.dashboard
export const ACCOUNT_HOME = internalPaths.account

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
  if (pathname.startsWith('/admin') || pathname === ADMIN_HOME) return 'admin'
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
  return pathname === ADMIN_HOME || pathname.startsWith(`${ADMIN_HOME}/`)
}

export function destinationForRoles(
  roles: RoleFlags,
  audience: AuthAudience | null,
  requestedNext?: string | null,
): string {
  const requested = requestedNext ? safePath(requestedNext, '') : ''
  const usableNext = requested && !isUserDashboardPath(requested) ? requested : ''

  if (audience === 'employer') {
    return usableNext.startsWith('/employer') ? usableNext : EMPLOYER_HOME
  }

  if (audience === 'job_seeker' && !roles.isAdmin) {
    if (usableNext.startsWith('/jobs/application') || usableNext.startsWith('/jobs/talent')) return usableNext
    return JOB_SEEKER_HOME
  }

  if (roles.isAdmin) {
    if (requested && (isUserDashboardPath(requested) || requested.startsWith('/admin'))) return requested
    return ADMIN_HOME
  }

  if (roles.isEmployer && !roles.isJobSeeker) {
    return usableNext.startsWith('/employer') ? usableNext : EMPLOYER_HOME
  }

  if (roles.isJobSeeker) {
    if (usableNext.startsWith('/jobs/application') || usableNext.startsWith('/jobs/talent')) return usableNext
    return JOB_SEEKER_HOME
  }

  return usableNext || ACCOUNT_HOME
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
