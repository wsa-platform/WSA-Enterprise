import i18n from './config'
import type { ApiError } from '../api/client'

function isNetworkFailure(error: Error): boolean {
  const message = error.message.toLowerCase()
  return error.name === 'TypeError'
    || message.includes('failed to fetch')
    || message.includes('networkerror')
    || message.includes('network request failed')
    || message.includes('load failed')
}

function translateFieldErrors(errors: Record<string, string[]>): string[] {
  const messages: string[] = []

  for (const [field, list] of Object.entries(errors)) {
    for (const raw of list) {
      const text = raw.toLowerCase()
      if (field === 'email' && (text.includes('taken') || text.includes('already') || text.includes('unique'))) {
        messages.push(i18n.t('auth.errors.emailTaken'))
        continue
      }
      if (field === 'email' && (text.includes('valid') || text.includes('email'))) {
        messages.push(i18n.t('auth.errors.invalidEmail'))
        continue
      }
      if ((field === 'password' || field === 'password_confirmation') && (text.includes('confirm') || text.includes('match'))) {
        messages.push(i18n.t('auth.errors.passwordMismatch'))
        continue
      }
      if (field === 'password' && (text.includes('at least') || text.includes('min') || text.includes('8'))) {
        messages.push(i18n.t('auth.errors.passwordMin'))
        continue
      }
      if (field === 'name' && (text.includes('required') || text.includes('empty'))) {
        messages.push(i18n.t('auth.errors.nameRequired'))
        continue
      }
      if (raw.trim()) messages.push(raw.trim())
    }
  }

  return [...new Set(messages)]
}

export function translateApiError(error: unknown): string {
  if (!(error instanceof Error)) {
    return i18n.t('errors.generic')
  }

  if (isNetworkFailure(error)) {
    return i18n.t('errors.network')
  }

  if (error.name === 'ApiError') {
    const apiError = error as ApiError
    if (apiError.isUnauthorized) return i18n.t('errors.unauthorized')
    if (apiError.isForbidden) {
      if ((apiError.message || '').toLowerCase().includes('registration is disabled')) {
        return i18n.t('auth.errors.registrationDisabled')
      }
      return apiError.message || i18n.t('errors.forbidden')
    }
    if (apiError.isNotFound) return i18n.t('errors.notFound')
    if (apiError.isConflict) return i18n.t('errors.conflict')
    if (apiError.isRateLimited) return i18n.t('errors.rateLimited')
    if (apiError.errors) {
      const mapped = translateFieldErrors(apiError.errors)
      if (mapped.length > 0) return mapped.join(' ')
      return apiError.message || i18n.t('errors.validation')
    }
  }

  return error.message || i18n.t('errors.generic')
}

export function apiFieldErrorMessages(error: unknown): string[] {
  if (!(error instanceof Error) || error.name !== 'ApiError') return []
  const apiError = error as ApiError
  if (!apiError.errors) return []
  const mapped = translateFieldErrors(apiError.errors)
  return mapped.length > 0 ? mapped : Object.values(apiError.errors).flat()
}
