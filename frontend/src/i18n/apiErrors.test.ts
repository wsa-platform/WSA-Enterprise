import { describe, expect, it } from 'vitest'
import { ApiError } from '../api/client'
import { apiFieldErrorMessages, translateApiError } from './apiErrors'
import i18n from './config'

describe('translateApiError', () => {
  it('maps duplicate email and password confirmation instead of a generic validation message', () => {
    const error = new ApiError('email: taken', 422, {
      email: ['The email has already been taken.'],
      password: ['The password field confirmation does not match.'],
    })

    const message = translateApiError(error)

    expect(message).toContain(i18n.t('auth.errors.emailTaken'))
    expect(message).toContain(i18n.t('auth.errors.passwordMismatch'))
    expect(message).not.toBe(i18n.t('errors.validation'))
  })

  it('maps invalid email and short password', () => {
    const error = new ApiError('invalid', 422, {
      email: ['The email must be a valid email address.'],
      password: ['The password must be at least 8 characters.'],
    })

    expect(translateApiError(error)).toContain(i18n.t('auth.errors.invalidEmail'))
    expect(translateApiError(error)).toContain(i18n.t('auth.errors.passwordMin'))
    expect(apiFieldErrorMessages(error)).toEqual([
      i18n.t('auth.errors.invalidEmail'),
      i18n.t('auth.errors.passwordMin'),
    ])
  })

  it('maps disabled registration and network failures', () => {
    expect(translateApiError(new ApiError('Registration is disabled.', 403))).toBe(
      i18n.t('auth.errors.registrationDisabled'),
    )
    const network = new TypeError('Failed to fetch')
    expect(translateApiError(network)).toBe(i18n.t('errors.network'))
  })
})
