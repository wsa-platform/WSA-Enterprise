import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest'
import {
  ApiError,
  buildHeaders,
  ensureSpaCsrfCookie,
  isStateChangingMethod,
  modulePaginationMeta,
  readXsrfToken,
  request,
  requestWithRetry,
  resetSpaCsrfState,
  SANCTUM_CSRF_COOKIE_PATH,
  seedSpaXsrfToken,
  unwrapEnvelope,
  unwrapModuleRows,
} from './client'

describe('ApiError', () => {
  it('identifies rate limit responses', () => {
    const error = new ApiError('Quota exceeded', 429, undefined, 'req-123', { limit: 10, used: 10 })
    expect(error.isRateLimited).toBe(true)
    expect(error.requestId).toBe('req-123')
    expect(error.quota?.limit).toBe(10)
  })

  it('identifies forbidden responses', () => {
    const error = new ApiError('Forbidden', 403)
    expect(error.isForbidden).toBe(true)
    expect(error.isNotFound).toBe(false)
  })

  it('identifies unauthorized and not-found responses', () => {
    expect(new ApiError('Unauthorized', 401).isUnauthorized).toBe(true)
    expect(new ApiError('Missing', 404).isNotFound).toBe(true)
  })

  it('identifies CSRF mismatches and server errors', () => {
    expect(new ApiError('CSRF token mismatch.', 419).isCsrfMismatch).toBe(true)
    expect(new ApiError('Boom', 500).isServerError).toBe(true)
    expect(new ApiError('No', 422).isServerError).toBe(false)
  })
})

describe('buildHeaders', () => {
  it('includes auth and organization headers when provided', () => {
    expect(buildHeaders('token-1', 42, { name: 'Farm' })).toEqual({
      Accept: 'application/json',
      'Accept-Language': 'en',
      Authorization: 'Bearer token-1',
      'Content-Type': 'application/json',
      'X-Organization-Id': '42',
    })
  })

  it('omits content type when there is no request body', () => {
    expect(buildHeaders(undefined, undefined, undefined)).toEqual({
      Accept: 'application/json',
      'Accept-Language': 'en',
    })
  })

  it('omits Content-Type for FormData so the browser sets the multipart boundary', () => {
    const headers = buildHeaders('token-1', 7, new FormData())
    expect(headers['Content-Type']).toBeUndefined()
    expect(headers.Authorization).toBe('Bearer token-1')
    expect(headers['X-Organization-Id']).toBe('7')
  })
})

describe('response helpers', () => {
  it('unwraps envelope payloads', () => {
    expect(unwrapEnvelope({ data: { id: 1 } })).toEqual({ id: 1 })
    expect(unwrapEnvelope({ id: 1 })).toEqual({ id: 1 })
  })

  it('unwraps module rows and pagination metadata', () => {
    const rows = [{ id: 1 }]
    const paginated = { data: rows, current_page: 1, last_page: 2, total: 3 }

    expect(unwrapModuleRows(rows)).toEqual(rows)
    expect(unwrapModuleRows(paginated)).toEqual(rows)
    expect(modulePaginationMeta(rows)).toBeNull()
    expect(modulePaginationMeta(paginated)).toEqual({
      currentPage: 1,
      lastPage: 2,
      total: 3,
    })
  })
})

describe('SPA CSRF contract', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    resetSpaCsrfState()
  })

  afterEach(() => {
    resetSpaCsrfState()
  })

  it('reads and URL-decodes the XSRF-TOKEN cookie', () => {
    const previous = (globalThis as { document?: unknown }).document
    Object.defineProperty(globalThis, 'document', {
      configurable: true,
      value: { cookie: 'XSRF-TOKEN=' + encodeURIComponent('token/value+') },
    })
    expect(readXsrfToken()).toBe('token/value+')
    expect(isStateChangingMethod('POST')).toBe(true)
    expect(isStateChangingMethod('GET')).toBe(false)
    Object.defineProperty(globalThis, 'document', { configurable: true, value: previous })
  })

  it('initializes Sanctum CSRF before the first state-changing request', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockImplementation(async (input) => {
      const url = String(input)
      if (url.includes(SANCTUM_CSRF_COOKIE_PATH)) {
        seedSpaXsrfToken('fresh-xsrf')
        return new Response(null, { status: 204 })
      }
      return new Response(JSON.stringify({ ok: true }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      })
    })

    await request('/auth/login', { method: 'POST', body: JSON.stringify({ email: 'a@b.c' }) })

    expect(fetchMock.mock.calls.map((call) => String(call[0]))).toEqual([
      SANCTUM_CSRF_COOKIE_PATH,
      expect.stringContaining('/auth/login'),
    ])
    expect((fetchMock.mock.calls[1][1]?.headers as Record<string, string>)['X-XSRF-TOKEN']).toBe('fresh-xsrf')
    expect(fetchMock.mock.calls[1][1]?.credentials).toBe('same-origin')
  })

  it('skips CSRF initialization when a valid XSRF cookie already exists', async () => {
    seedSpaXsrfToken('existing-xsrf')
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ ok: true }), { status: 200, headers: { 'Content-Type': 'application/json' } }),
    )

    await ensureSpaCsrfCookie()
    await request('/auth/login', { method: 'POST', body: '{}' })

    expect(fetchMock).toHaveBeenCalledOnce()
    expect(String(fetchMock.mock.calls[0][0])).toContain('/auth/login')
  })

  it('refreshes CSRF once after 419 and does not loop', async () => {
    let loginAttempts = 0
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockImplementation(async (input) => {
      const url = String(input)
      if (url.includes(SANCTUM_CSRF_COOKIE_PATH)) {
        seedSpaXsrfToken('retry-xsrf')
        return new Response(null, { status: 204 })
      }
      loginAttempts += 1
      if (loginAttempts === 1) {
        return new Response(JSON.stringify({ message: 'CSRF token mismatch.' }), {
          status: 419,
          headers: { 'Content-Type': 'application/json' },
        })
      }
      return new Response(JSON.stringify({ token: 'ok' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      })
    })

    const result = await request<{ token: string }>('/auth/login', { method: 'POST', body: '{}' })

    expect(result.token).toBe('ok')
    const csrfCalls = fetchMock.mock.calls.filter((call) => String(call[0]).includes(SANCTUM_CSRF_COOKIE_PATH))
    const loginCalls = fetchMock.mock.calls.filter((call) => String(call[0]).includes('/auth/login'))
    expect(csrfCalls).toHaveLength(2)
    expect(loginCalls).toHaveLength(2)
  })

  it('does not send CSRF headers on GET requests', async () => {
    seedSpaXsrfToken('existing-xsrf')
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ status: 'ok' }), { status: 200, headers: { 'Content-Type': 'application/json' } }),
    )

    await request('/health')

    expect(fetchMock).toHaveBeenCalledOnce()
    expect((fetchMock.mock.calls[0][1]?.headers as Record<string, string>)['X-XSRF-TOKEN']).toBeUndefined()
    expect(String(fetchMock.mock.calls[0][0])).not.toContain(SANCTUM_CSRF_COOKIE_PATH)
  })

  it('sends CSRF on FormData POST uploads without forcing JSON Content-Type', async () => {
    seedSpaXsrfToken('upload-xsrf')
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ ok: true }), { status: 200, headers: { 'Content-Type': 'application/json' } }),
    )

    await request('/jobs/talent/me/cv', { method: 'POST', body: new FormData() }, 'token-1', 3)

    const init = fetchMock.mock.calls[0][1] as RequestInit
    const headers = init.headers as Record<string, string>
    expect(headers['X-XSRF-TOKEN']).toBe('upload-xsrf')
    expect(headers['Content-Type']).toBeUndefined()
    expect(headers.Authorization).toBe('Bearer token-1')
    expect(init.body).toBeInstanceOf(FormData)
  })
})

describe('requestWithRetry', () => {
  it('retries server errors and stops on client errors', async () => {
    let attempts = 0

    await expect(
      requestWithRetry(async () => {
        attempts += 1
        throw new ApiError('Server error', 500)
      }, 1),
    ).rejects.toBeInstanceOf(ApiError)

    expect(attempts).toBe(2)

    await expect(
      requestWithRetry(async () => {
        throw new ApiError('Bad request', 400)
      }, 2),
    ).rejects.toMatchObject({ status: 400 })
  })
})
