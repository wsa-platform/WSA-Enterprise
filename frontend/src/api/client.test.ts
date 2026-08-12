import { describe, expect, it } from 'vitest'
import {
  ApiError,
  buildHeaders,
  modulePaginationMeta,
  requestWithRetry,
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
