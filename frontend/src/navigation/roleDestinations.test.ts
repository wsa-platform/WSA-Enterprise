import { describe, expect, it } from 'vitest'
import {
  ADMIN_HOME,
  EMPLOYER_HOME,
  JOB_SEEKER_HOME,
  audienceFromPath,
  destinationForRoles,
  parseAudience,
  roleFlagsFromPermissions,
} from './roleDestinations'
import { loginPathForProtectedRoute, safeReturnPath, unknownRouteFallback } from './routeGuards'

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

  it('sends employers to the dedicated employer route, not the dashboard', () => {
    const roles = roleFlagsFromPermissions(['jobs.manage', 'platform.view'])
    expect(destinationForRoles(roles, 'employer', '/dashboard')).toBe(EMPLOYER_HOME)
    expect(destinationForRoles(roles, 'employer', '/employer')).toBe(EMPLOYER_HOME)
  })

  it('keeps administrators on the admin workspace', () => {
    const roles = roleFlagsFromPermissions(['*', 'access.manage'])
    expect(destinationForRoles(roles, null, '/dashboard')).toBe(ADMIN_HOME)
    expect(destinationForRoles(roles, 'job_seeker', '/jobs/application')).toBe(ADMIN_HOME)
  })

  it('parses recruitment audiences and paths', () => {
    expect(parseAudience('job_seeker')).toBe('job_seeker')
    expect(parseAudience('employer')).toBe('employer')
    expect(parseAudience('dashboard')).toBeNull()
    expect(audienceFromPath('/jobs/application')).toBe('job_seeker')
    expect(audienceFromPath('/employer')).toBe('employer')
    expect(audienceFromPath('/dashboard')).toBe('admin')
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
  })
})
