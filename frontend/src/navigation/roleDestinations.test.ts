import { describe, expect, it } from 'vitest'
import {
  ADMIN_HOME,
  EMPLOYER_HOME,
  EMPLOYER_ENTER,
  JOB_SEEKER_AUTH_ENTER,
  JOB_SEEKER_ENTER,
  JOB_SEEKER_HOME,
  LEGACY_DASHBOARD_PATH,
  audienceFromPath,
  canonicalSellerPath,
  destinationForRoles,
  employerLogoutPath,
  employerStartPath,
  isMarketplacePath,
  jobSeekerLandingPath,
  jobSeekerStartPath,
  loginHref,
  marketplaceLoginHref,
  marketplaceRegisterHref,
  parseAudience,
  shouldStayOnPlatformAuthPage,
  publicLoginHref,
  publicRegisterHref,
  registerHref,
  roleFlagsFromPermissions,
} from './roleDestinations'
import { loginPathForProtectedRoute, safeReturnPath, unknownRouteFallback } from './routeGuards'
import { internalPaths } from './paths'

const combinations: Array<{ permissions: string[]; audience: 'job_seeker' | 'employer' | 'admin' | null; next?: string }> = [
  { permissions: [], audience: 'job_seeker' },
  { permissions: [], audience: 'employer' },
  { permissions: ['*'], audience: 'job_seeker', next: LEGACY_DASHBOARD_PATH },
  { permissions: ['*'], audience: 'employer', next: LEGACY_DASHBOARD_PATH },
  { permissions: ['access.manage'], audience: 'job_seeker', next: '/jobs/application' },
  { permissions: ['jobs.talent.register'], audience: null, next: LEGACY_DASHBOARD_PATH },
  { permissions: ['jobs.manage'], audience: null, next: LEGACY_DASHBOARD_PATH },
  { permissions: ['platform.view'], audience: null, next: LEGACY_DASHBOARD_PATH },
  { permissions: ['*'], audience: null, next: LEGACY_DASHBOARD_PATH },
]

describe('role destinations', () => {
  it('sends job seekers to the existing job-seeker page, not the dashboard', () => {
    const roles = roleFlagsFromPermissions(['jobs.talent.register', 'jobs.talent.manage', 'platform.view'])
    expect(destinationForRoles(roles, 'job_seeker', '/dashboard')).toBe(JOB_SEEKER_HOME)
    expect(destinationForRoles(roles, 'job_seeker', '/jobs/application')).toBe(JOB_SEEKER_HOME)
    expect(destinationForRoles(roles, null, '/dashboard')).toBe(JOB_SEEKER_HOME)
  })

  it('keeps recruitment-audience job seekers off the dashboard even without org talent permissions', () => {
    const roles = roleFlagsFromPermissions([])
    expect(destinationForRoles(roles, 'job_seeker', '/dashboard')).toBe(JOB_SEEKER_HOME)
    expect(destinationForRoles(roles, 'employer', '/dashboard')).toBe(EMPLOYER_HOME)
  })

  it('never sends a Job-Seeker audience to the legacy dashboard, including administrators', () => {
    const admin = roleFlagsFromPermissions(['*', 'access.manage'])
    expect(destinationForRoles(admin, 'job_seeker', LEGACY_DASHBOARD_PATH)).toBe(JOB_SEEKER_HOME)
    expect(destinationForRoles(admin, 'job_seeker', '/jobs/application')).toBe(JOB_SEEKER_HOME)
  })

  it('sends employers to the dedicated employer route, not the dashboard', () => {
    const roles = roleFlagsFromPermissions(['jobs.manage', 'platform.view'])
    expect(destinationForRoles(roles, 'employer', '/dashboard')).toBe(EMPLOYER_HOME)
    expect(destinationForRoles(roles, 'employer', '/employer')).toBe(EMPLOYER_HOME)
    expect(destinationForRoles(roleFlagsFromPermissions(['*']), 'employer', LEGACY_DASHBOARD_PATH)).toBe(EMPLOYER_HOME)
  })

  it('sends administrators without a recruitment audience to the admin users page, never the legacy dashboard', () => {
    const roles = roleFlagsFromPermissions(['*', 'access.manage'])
    expect(destinationForRoles(roles, null, '/dashboard')).toBe(ADMIN_HOME)
    expect(destinationForRoles(roles, 'admin', '/admin/audit')).toBe('/admin/audit')
    expect(ADMIN_HOME).toBe('/admin/users')
    expect(ADMIN_HOME).not.toBe(LEGACY_DASHBOARD_PATH)
  })

  it('never resolves any role/audience combination to the legacy dashboard', () => {
    for (const row of combinations) {
      const path = destinationForRoles(roleFlagsFromPermissions(row.permissions), row.audience, row.next)
      expect(path).not.toBe(LEGACY_DASHBOARD_PATH)
      expect(path.startsWith(`${LEGACY_DASHBOARD_PATH}/`)).toBe(false)
    }
  })

  it('parses recruitment audiences and paths', () => {
    expect(parseAudience('job_seeker')).toBe('job_seeker')
    expect(parseAudience('employer')).toBe('employer')
    expect(parseAudience('dashboard')).toBeNull()
    expect(audienceFromPath('/jobs/application')).toBe('job_seeker')
    expect(audienceFromPath('/employer')).toBe('employer')
    expect(audienceFromPath('/dashboard')).toBe('admin')
  })

  it('routes Job-Seeker and Employer entry from the public homepage choice', () => {
    expect(jobSeekerLandingPath(false)).toBe(JOB_SEEKER_AUTH_ENTER)
    expect(jobSeekerLandingPath(true)).toBe(JOB_SEEKER_HOME)
    expect(jobSeekerStartPath(false)).toBe(JOB_SEEKER_AUTH_ENTER)
    expect(jobSeekerStartPath(true)).toBe(JOB_SEEKER_HOME)
    expect(employerStartPath(false)).toBe(EMPLOYER_ENTER)
    expect(employerStartPath(true)).toBe(EMPLOYER_HOME)
    expect(jobSeekerLandingPath(false)).not.toContain('/dashboard')
    expect(jobSeekerStartPath(false)).not.toContain('/dashboard')
    expect(employerStartPath(false)).not.toContain('/dashboard')
    expect(registerHref('job_seeker', JOB_SEEKER_HOME)).toContain('audience=job_seeker')
    expect(registerHref('job_seeker', JOB_SEEKER_HOME)).toContain(encodeURIComponent(JOB_SEEKER_HOME))
  })

  it('keeps public header login and registration as distinct audience-aware actions', () => {
    expect(publicLoginHref(null, '/sections/jobs')).toBe(loginHref('job_seeker', JOB_SEEKER_HOME))
    expect(publicRegisterHref(null, '/sections/jobs')).toBe(registerHref('job_seeker', JOB_SEEKER_HOME))
    expect(publicLoginHref(null, '/sections/jobs')).not.toBe(publicRegisterHref(null, '/sections/jobs'))
    expect(publicLoginHref(null, '/sections/jobs')).not.toContain('/jobs/enter')
    expect(publicRegisterHref(null, '/sections/jobs')).not.toContain('/jobs/enter')
    expect(publicLoginHref(null, '/sections/jobs')).not.toContain('/dashboard')
    expect(publicRegisterHref(null, '/sections/jobs')).not.toContain('/dashboard')
    expect(publicLoginHref('employer', '/sections/jobs')).toBe(loginHref('employer', EMPLOYER_HOME))
    expect(publicRegisterHref('employer', '/sections/jobs')).toBe(registerHref('employer', EMPLOYER_HOME))
    expect(publicLoginHref(null, '/employer')).toBe(loginHref('employer', EMPLOYER_HOME))
    expect(publicRegisterHref(null, '/employer')).toBe(registerHref('employer', EMPLOYER_HOME))
  })

  it('logs Job-Seekers out to the entry page and Employers out to employer login', () => {
    expect(JOB_SEEKER_ENTER).toBe('/jobs/enter')
    expect(employerLogoutPath()).toBe(loginHref('employer', EMPLOYER_HOME))
    expect(employerLogoutPath()).not.toContain('/dashboard')
  })
})

describe('route guards', () => {
  it('does not default unauthenticated fallbacks to the user dashboard', () => {
    expect(unknownRouteFallback(false)).toBe('/')
    expect(unknownRouteFallback(true)).toBe('/account')
    expect(safeReturnPath('https://evil.example')).toBe('/')
    expect(safeReturnPath('//evil.example')).toBe('/')
  })

  it('preserves job-seeker audience when returning to a protected job-seeker page', () => {
    expect(loginPathForProtectedRoute('/jobs/application')).toBe(
      '/login?next=%2Fjobs%2Fapplication&audience=job_seeker',
    )
    expect(loginPathForProtectedRoute('/employer')).toContain('audience=employer')
    expect(loginPathForProtectedRoute('/dashboard', 'admin')).not.toContain('Dashboard')
  })
})

describe('marketplace account separation', () => {
  it('keeps marketplace login and registration off the Job-Seeker audience', () => {
    expect(isMarketplacePath('/market')).toBe(true)
    expect(isMarketplacePath('/seller/listings')).toBe(true)
    expect(isMarketplacePath('/seller/listings/new')).toBe(true)
    expect(isMarketplacePath('/account/products')).toBe(true)
    expect(isMarketplacePath('/account')).toBe(false)
    expect(isMarketplacePath('/jobs/application')).toBe(false)
    expect(publicLoginHref(null, '/market')).not.toContain('audience=job_seeker')
    expect(publicRegisterHref(null, '/market')).not.toContain('audience=job_seeker')
    expect(marketplaceLoginHref()).not.toContain('audience=')
    expect(marketplaceRegisterHref()).not.toContain('audience=')
    expect(marketplaceRegisterHref()).toContain(encodeURIComponent(internalPaths.newProduct))
    expect(publicLoginHref(null, '/')).toContain('audience=job_seeker')
    expect(registerHref('job_seeker', JOB_SEEKER_HOME)).toContain('audience=job_seeker')
  })

  it('returns marketplace users to their products instead of the Job-Seeker profile', () => {
    const jobSeeker = roleFlagsFromPermissions(['jobs.talent.register'])
    expect(destinationForRoles(jobSeeker, null, internalPaths.products)).toBe(internalPaths.products)
    expect(destinationForRoles(jobSeeker, null, internalPaths.newProduct)).toBe(internalPaths.newProduct)
    expect(destinationForRoles(jobSeeker, null, '/account/products/new')).toBe(internalPaths.newProduct)
    expect(canonicalSellerPath('/account/products')).toBe('/seller/listings')
    expect(destinationForRoles(jobSeeker, 'job_seeker', internalPaths.products)).toBe(JOB_SEEKER_HOME)
    expect(loginPathForProtectedRoute('/jobs/application')).toContain('audience=job_seeker')
    expect(loginPathForProtectedRoute(internalPaths.products)).not.toContain('audience=job_seeker')
  })

  it('sends seller login and registration to platform auth pages, then back to listings', () => {
    expect(marketplaceLoginHref(internalPaths.products)).toBe('/login?next=%2Fseller%2Flistings')
    expect(marketplaceRegisterHref(internalPaths.products)).toBe('/register?next=%2Fseller%2Flistings')
    expect(marketplaceLoginHref(internalPaths.products)).not.toContain('audience=')
    expect(marketplaceRegisterHref(internalPaths.products)).not.toContain('audience=')
    expect(shouldStayOnPlatformAuthPage(null, '/seller/listings')).toBe(true)
    expect(shouldStayOnPlatformAuthPage('job_seeker', '/seller/listings')).toBe(false)
    expect(shouldStayOnPlatformAuthPage('employer', '/employer')).toBe(false)
    expect(destinationForRoles(roleFlagsFromPermissions([]), null, '/seller/listings')).toBe('/seller/listings')
  })
})
