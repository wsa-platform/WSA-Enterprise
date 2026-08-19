import i18n from './config'
import type { ApiError } from '../api/client'

export function translateApiError(error: unknown): string {
  if (!(error instanceof Error)) {
    return i18n.t('errors.generic')
  }

  if (error.name === 'ApiError') {
    const apiError = error as ApiError
    if (apiError.isUnauthorized) return i18n.t('errors.unauthorized')
    if (apiError.isForbidden) return i18n.t('errors.forbidden')
    if (apiError.isNotFound) return i18n.t('errors.notFound')
    if (apiError.isRateLimited) return i18n.t('errors.rateLimited')
    if (apiError.errors) return i18n.t('errors.validation')
  }

  return error.message || i18n.t('errors.generic')
}

export function apiFieldErrorMessages(error: unknown): string[] {
  if (!(error instanceof Error) || error.name !== 'ApiError') return []
  const apiError = error as ApiError
  if (!apiError.errors) return []
  return Object.values(apiError.errors).flat()
}
