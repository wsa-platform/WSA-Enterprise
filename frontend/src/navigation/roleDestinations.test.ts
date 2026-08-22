import { describe, expect, it } from 'vitest'
import {
  ADMIN_HOME,
  EMPLOYER_HOME,
  JOB_SEEKER_ENTER,
  JOB_SEEKER_HOME,
  LEGACY_DASHBOARD_PATH,
  audienceFromPath,
  destinationForRoles,
  employerLogoutPath,
  employerStartPath,
  jobSeekerLandingPath,
  jobSeekerStartPath,
  loginHref,
  parseAudience,
  registerHref,
  roleFlagsFromPermissions,
} from './roleDestinations'
import { loginPathForProtectedRoute, safeReturnPath, unknownRouteFallback } from './routeGuards'

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
    expect(jobSeekerLandingPath(false)).toBe(JOB_SEEKER_ENTER)
    expect(jobSeekerLandingPath(true)).toBe(JOB_SEEKER_HOME)
    expect(jobSeekerStartPath(false)).toBe(loginHref('job_seeker', JOB_SEEKER_HOME))
    expect(jobSeekerStartPath(true)).toBe(JOB_SEEKER_HOME)
    expect(employerStartPath(false)).toBe(loginHref('employer', EMPLOYER_HOME))
    expect(employerStartPath(true)).toBe(EMPLOYER_HOME)
    expect(jobSeekerLandingPath(false)).not.toContain('/dashboard')
    expect(jobSeekerStartPath(false)).not.toContain('/dashboard')
    expect(employerStartPath(false)).not.toContain('/dashboard')
    expect(registerHref('job_seeker', JOB_SEEKER_HOME)).toContain('audience=job_seeker')
    expect(registerHref('job_seeker', JOB_SEEKER_HOME)).toContain(encodeURIComponent(JOB_SEEKER_HOME))
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
