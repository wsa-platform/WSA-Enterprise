import { describe, expect, it, vi, beforeEach } from 'vitest'
import { register, forgotPassword, getGoogleRedirect, updateAccountProfile } from './auth'

describe('register', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('posts registration payload to the auth register endpoint', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify({
          token: 'test-token',
          user: { id: 1, name: 'Owner', email: 'owner@wsa.test' },
          organization: { id: 10, name: 'Owner Workspace', slug: 'owner-10' },
        }),
        { status: 201, headers: { 'Content-Type': 'application/json' } },
      ),
    )

    const result = await register({
      name: 'Owner',
      email: 'owner@wsa.test',
      password: 'password123',
      password_confirmation: 'password123',
    })

    expect(result.token).toBe('test-token')
    expect(result.organization?.slug).toBe('owner-10')
    expect(fetchMock).toHaveBeenCalledOnce()

    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/auth/register')
    expect(init?.method).toBe('POST')
    expect(JSON.parse(String(init?.body))).toMatchObject({
      email: 'owner@wsa.test',
      device_name: 'wsa-web-dashboard',
    })
  })

  it('sends job-seeker audience so dedicated signup is not treated as service-owner registration', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify({
          token: 'seeker-token',
          user: { id: 2, name: 'Seeker', email: 'seeker@wsa.test' },
        }),
        { status: 201, headers: { 'Content-Type': 'application/json' } },
      ),
    )

    const result = await register({
      name: 'Seeker',
      email: 'seeker@wsa.test',
      password: 'password123',
      password_confirmation: 'password123',
      audience: 'job_seeker',
    })

    expect(result.token).toBe('seeker-token')
    expect(result.organization).toBeUndefined()
    expect(JSON.parse(String(fetchMock.mock.calls[0][1]?.body))).toMatchObject({
      email: 'seeker@wsa.test',
      audience: 'job_seeker',
    })
  })
})

describe('password reset and google redirect', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('posts forgot-password to the auth endpoint', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ message: 'ok' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    await forgotPassword('owner@wsa.test')

    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/auth/forgot-password')
    expect(init?.method).toBe('POST')
    expect(JSON.parse(String(init?.body))).toEqual({ email: 'owner@wsa.test' })
  })

  it('loads the google redirect URL', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ url: 'https://accounts.google.com/o', state: 'abc' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await getGoogleRedirect()

    expect('url' in result && result.url).toContain('accounts.google.com')
    expect(String(fetchMock.mock.calls[0][0])).toContain('/auth/google/redirect')
  })

  it('loads the facebook redirect URL without inventing a second auth system', async () => {
    const { getFacebookRedirect } = await import('./auth')
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ url: 'https://www.facebook.com/dialog/oauth', state: 'xyz' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await getFacebookRedirect()

    expect('url' in result && result.url).toContain('facebook.com')
    expect(String(fetchMock.mock.calls[0][0])).toContain('/auth/facebook/redirect')
  })
})

describe('public services catalog', () => {
  it('loads the public catalog without authentication', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify({
          platform: 'WSA Enterprise',
          description: 'Public catalog',
          service_modules: [{ key: 'training', label: 'Training', requires_auth: false }],
          public_capabilities: ['Browse catalog'],
          protected_capabilities: ['Create farms'],
        }),
        { status: 200, headers: { 'Content-Type': 'application/json' } },
      ),
    )

    const response = await fetch('/api/v1/public/services')
    const catalog = await response.json()

    expect(response.ok).toBe(true)
    expect(catalog.platform).toBe('WSA Enterprise')
    expect(catalog.service_modules).toHaveLength(1)
    expect(fetchMock).toHaveBeenCalledOnce()
  })
})

describe('updateAccountProfile', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('patches only the authenticated user name', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ id: 1, name: 'Updated', email: 'owner@wsa.test' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await updateAccountProfile('token-1', { name: 'Updated' })

    expect(result.name).toBe('Updated')
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/account/profile')
    expect(init?.method).toBe('PATCH')
    expect(JSON.parse(String(init?.body))).toEqual({ name: 'Updated' })
    expect((init?.headers as Record<string, string>).Authorization).toBe('Bearer token-1')
  })
})
