import { describe, expect, it } from 'vitest'
import { isSystemRole, SYSTEM_ROLE_SLUGS } from './users'
import type { Role } from './types'

describe('admin API helpers', () => {
  it('identifies system enterprise roles by slug', () => {
    expect(isSystemRole({ id: 1, name: 'Owner', slug: 'owner' })).toBe(true)
    expect(isSystemRole({ id: 2, name: 'Custom', slug: null })).toBe(false)
    expect(isSystemRole({ id: 3, name: 'Custom', slug: 'reports-analyst' })).toBe(false)
  })

  it('documents seeded system role slugs', () => {
    expect(SYSTEM_ROLE_SLUGS).toContain('owner')
    expect(SYSTEM_ROLE_SLUGS).toContain('viewer')
  })

  it('maps role slugs for UI guards', () => {
    const customRole: Role = { id: 10, name: 'Analyst', slug: 'analyst' }
    expect(SYSTEM_ROLE_SLUGS.includes(customRole.slug as typeof SYSTEM_ROLE_SLUGS[number])).toBe(false)
  })
})
