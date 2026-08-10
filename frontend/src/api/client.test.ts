import { describe, expect, it } from 'vitest'
import { ApiError } from './client'

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
})
