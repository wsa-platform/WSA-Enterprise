import { audienceFromPath, parseAudience, type AuthAudience } from './roleDestinations'

export function safeReturnPath(value: string | null | undefined, fallback = '/'): string {
  return value && value.startsWith('/') && !value.startsWith('//') ? value : fallback
}

export function unknownRouteFallback(isAuthenticated: boolean): string {
  return isAuthenticated ? '/account' : '/'
}

export function loginPathForProtectedRoute(
  pathnameAndSearch: string,
  audience?: AuthAudience | null,
): string {
  const next = safeReturnPath(pathnameAndSearch)
  const resolvedAudience = audience ?? audienceFromPath(next.split('?')[0] ?? next)
  const params = new URLSearchParams()
  params.set('next', next)
  if (resolvedAudience) params.set('audience', resolvedAudience)
  return `/login?${params.toString()}`
}

export function authQuery(options: {
  audience?: AuthAudience | string | null
  next?: string | null
  mode?: 'login' | 'register'
}): string {
  const params = new URLSearchParams()
  const audience = parseAudience(options.audience ?? null)
  if (audience) params.set('audience', audience)
  if (options.next) params.set('next', safeReturnPath(options.next))
  if (options.mode) params.set('mode', options.mode)
  const query = params.toString()
  return query ? `?${query}` : ''
}
