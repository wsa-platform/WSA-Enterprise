import type { EnvelopeResponse, PaginatedResponse } from './types'

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  get isUnauthorized() {
    return this.status === 401
  }

  get isForbidden() {
    return this.status === 403
  }
}

export const apiUrl = import.meta.env.VITE_API_URL ?? '/api/v1'

let unauthorizedHandler: (() => void) | null = null

export function setUnauthorizedHandler(handler: (() => void) | null) {
  unauthorizedHandler = handler
}

export function buildHeaders(token?: string, organizationId?: number, body?: unknown) {
  return {
    Accept: 'application/json',
    ...(body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(organizationId ? { 'X-Organization-Id': String(organizationId) } : {}),
  }
}

export async function request<T>(
  path: string,
  options: RequestInit = {},
  token?: string,
  organizationId?: number,
): Promise<T> {
  const body = options.body
  const response = await fetch(`${apiUrl}${path}`, {
    ...options,
    headers: {
      ...buildHeaders(token, organizationId, body),
      ...options.headers,
    },
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null) as {
      message?: string
      errors?: Record<string, string[]>
    } | null

    if (response.status === 401 && token) {
      unauthorizedHandler?.()
    }

    if (payload?.errors) {
      const details = Object.entries(payload.errors).flatMap(([field, messages]) =>
        messages.map((message) => `${field}: ${message}`),
      )
      throw new ApiError(
        details.join(' · ') || payload.message || 'Validation failed.',
        response.status,
        payload.errors,
      )
    }

    throw new ApiError(payload?.message ?? 'Unable to complete the request.', response.status)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}

export function unwrapEnvelope<T>(payload: T | EnvelopeResponse<T>): T {
  if (payload && typeof payload === 'object' && 'data' in payload) {
    return (payload as EnvelopeResponse<T>).data
  }

  return payload as T
}

export function unwrapModuleRows<T>(payload: T[] | PaginatedResponse<T>): T[] {
  return Array.isArray(payload) ? payload : payload.data ?? []
}

export function modulePaginationMeta<T>(payload: T[] | PaginatedResponse<T>) {
  if (Array.isArray(payload)) return null

  return {
    currentPage: payload.current_page,
    lastPage: payload.last_page,
    total: payload.total,
  }
}

export async function requestWithRetry<T>(
  fn: () => Promise<T>,
  retries = 1,
  delayMs = 400,
): Promise<T> {
  try {
    return await fn()
  } catch (error) {
    if (retries <= 0 || !(error instanceof ApiError) || error.status < 500) {
      throw error
    }

    await new Promise((resolve) => setTimeout(resolve, delayMs))
    return requestWithRetry(fn, retries - 1, delayMs)
  }
}
