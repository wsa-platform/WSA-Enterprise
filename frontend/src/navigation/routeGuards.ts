export function safeReturnPath(value: string | null | undefined): string {
  return value && value.startsWith('/') && !value.startsWith('//') ? value : '/dashboard'
}

export function unknownRouteFallback(isAuthenticated: boolean): string {
  return isAuthenticated ? '/dashboard' : '/'
}

export function loginPathForProtectedRoute(pathnameAndSearch: string): string {
  return `/login?next=${encodeURIComponent(safeReturnPath(pathnameAndSearch))}`
}
