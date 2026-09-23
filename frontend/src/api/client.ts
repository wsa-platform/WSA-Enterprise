import { getCurrentLanguage } from '../i18n/config'
import type { EnvelopeResponse, PaginatedResponse } from './types'

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>
  requestId?: string
  quota?: { limit: number; used: number }

  constructor(
    message: string,
    status: number,
    errors?: Record<string, string[]>,
    requestId?: string,
    quota?: { limit: number; used: number },
  ) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
    this.requestId = requestId
    this.quota = quota
  }

  get isUnauthorized() {
    return this.status === 401
  }

  get isForbidden() {
    return this.status === 403
  }

  get isNotFound() {
    return this.status === 404
  }

  get isConflict() {
    return this.status === 409
  }

  get isRateLimited() {
    return this.status === 429
  }

  get isCsrfMismatch() {
    return this.status === 419
  }

  get isServerError() {
    return this.status >= 500
  }
}

export const apiUrl = import.meta.env.VITE_API_URL ?? '/api/v1'

/** Official Sanctum SPA CSRF cookie endpoint (not under /api/v1). */
export const SANCTUM_CSRF_COOKIE_PATH = '/sanctum/csrf-cookie'

const STATE_CHANGING_METHODS = new Set(['POST', 'PUT', 'PATCH', 'DELETE'])

let unauthorizedHandler: (() => void) | null = null
let csrfCookieInFlight: Promise<void> | null = null
let seededXsrfToken: string | null = null

export function setUnauthorizedHandler(handler: (() => void) | null) {
  unauthorizedHandler = handler
}

export function resetSpaCsrfState() {
  csrfCookieInFlight = null
  seededXsrfToken = null
}

/** Test helper — Node/vitest has no document.cookie. Unused in production. */
export function seedSpaXsrfToken(token: string | null) {
  seededXsrfToken = token
}

/** True when this browser client talks to the same origin (Vite proxy / first-party SPA). */
export function isSameOriginApi(): boolean {
  if (apiUrl.startsWith('/')) {
    return true
  }
  if (typeof window === 'undefined') {
    return false
  }
  try {
    return new URL(apiUrl, window.location.origin).origin === window.location.origin
  } catch {
    return false
  }
}

export function isStateChangingMethod(method?: string): boolean {
  return STATE_CHANGING_METHODS.has((method ?? 'GET').toUpperCase())
}

export function readXsrfToken(): string | null {
  if (seededXsrfToken) {
    return seededXsrfToken
  }
  if (typeof document === 'undefined' || !document.cookie) {
    return null
  }

  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/)
  if (!match?.[1]) {
    return null
  }

  try {
    return decodeURIComponent(match[1])
  } catch {
    return match[1]
  }
}

export async function ensureSpaCsrfCookie(force = false): Promise<void> {
  if (!isSameOriginApi()) {
    return
  }
  if (!force && readXsrfToken()) {
    return
  }
  if (csrfCookieInFlight) {
    return csrfCookieInFlight
  }

  csrfCookieInFlight = fetch(SANCTUM_CSRF_COOKIE_PATH, {
    method: 'GET',
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  }).then((response) => {
    if (!response.ok && response.status !== 204) {
      throw new ApiError('Unable to initialize CSRF protection.', response.status)
    }
  }).finally(() => {
    csrfCookieInFlight = null
  })

  return csrfCookieInFlight
}

export function buildHeaders(token?: string, organizationId?: number, body?: unknown) {
  return {
    Accept: 'application/json',
    'Accept-Language': getCurrentLanguage(),
    ...(body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(organizationId ? { 'X-Organization-Id': String(organizationId) } : {}),
  }
}

function mergeHeaders(base: Record<string, string>, extra?: HeadersInit): Record<string, string> {
  const merged: Record<string, string> = { ...base }
  if (!extra) {
    return merged
  }
  if (extra instanceof Headers) {
    extra.forEach((value, key) => {
      merged[key] = value
    })
    return merged
  }
  if (Array.isArray(extra)) {
    for (const [key, value] of extra) {
      merged[key] = value
    }
    return merged
  }
  return { ...merged, ...extra }
}

async function parseErrorPayload(response: Response) {
  return await response.json().catch(() => null) as {
    message?: string
    errors?: Record<string, string[]>
    quota?: { limit: number; used: number }
  } | null
}

function throwForFailedResponse(
  response: Response,
  payload: Awaited<ReturnType<typeof parseErrorPayload>>,
  path: string,
  token?: string,
): never {
  const requestId = response.headers.get('X-Request-Id') ?? undefined
  const gatewayFailure = !payload && (response.status === 500 || response.status === 502 || response.status === 503)

  if (response.status === 401 && token && path !== '/auth/logout') {
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
      requestId,
      payload.quota,
    )
  }

  throw new ApiError(
    payload?.message ?? 'Unable to complete the request.',
    gatewayFailure ? 502 : response.status,
    undefined,
    requestId,
    payload?.quota,
  )
}

export async function request<T>(
  path: string,
  options: RequestInit = {},
  token?: string,
  organizationId?: number,
): Promise<T> {
  const body = options.body
  const method = (options.method ?? 'GET').toUpperCase()
  const spa = isSameOriginApi()
  const stateChanging = isStateChangingMethod(method)

  if (spa && stateChanging) {
    await ensureSpaCsrfCookie()
  }

  const send = async (): Promise<Response> => {
    const headers = mergeHeaders(buildHeaders(token, organizationId, body), options.headers)
    if (spa) {
      const xsrf = readXsrfToken()
      if (xsrf && stateChanging) {
        headers['X-XSRF-TOKEN'] = xsrf
      }
    }

    try {
      return await fetch(`${apiUrl}${path}`, {
        ...options,
        method,
        credentials: spa ? 'same-origin' : (options.credentials ?? 'omit'),
        headers,
      })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Network request failed.'
      throw new ApiError(message, 0)
    }
  }

  let response = await send()

  if (spa && stateChanging && response.status === 419) {
    resetSpaCsrfState()
    await ensureSpaCsrfCookie(true)
    response = await send()
  }

  if (!response.ok) {
    throwForFailedResponse(response, await parseErrorPayload(response), path, token)
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
