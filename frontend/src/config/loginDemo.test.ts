import { afterEach, describe, expect, it, vi } from 'vitest'
import { DEMO_HINT, getLoginDefaults, isDemoLoginEnabled } from './loginDemo'

describe('loginDemo', () => {
  afterEach(() => {
    vi.unstubAllEnvs()
  })

  it('enables demo login in dev when the flag is unset', () => {
    vi.stubEnv('DEV', 'true')
    vi.stubEnv('VITE_SHOW_DEMO_LOGIN', '')

    expect(isDemoLoginEnabled()).toBe(true)
  })

  it('disables demo login when the flag is false', () => {
    vi.stubEnv('DEV', 'true')
    vi.stubEnv('VITE_SHOW_DEMO_LOGIN', 'false')

    expect(isDemoLoginEnabled()).toBe(false)
  })

  it('enables demo login when the flag is true in production mode', () => {
    vi.stubEnv('DEV', '')
    vi.stubEnv('VITE_SHOW_DEMO_LOGIN', 'true')

    expect(isDemoLoginEnabled()).toBe(true)
  })

  it('returns empty credentials when demo login is disabled', () => {
    vi.stubEnv('VITE_SHOW_DEMO_LOGIN', 'false')

    expect(getLoginDefaults()).toEqual({ email: '', password: '' })
  })

  it('returns demo credentials when demo login is enabled', () => {
    vi.stubEnv('VITE_SHOW_DEMO_LOGIN', 'true')

    expect(getLoginDefaults()).toEqual({ email: 'admin@wsa.test', password: 'password' })
  })

  it('documents the demo hint for local builds', () => {
    expect(DEMO_HINT).toContain('admin@wsa.test')
  })
})
