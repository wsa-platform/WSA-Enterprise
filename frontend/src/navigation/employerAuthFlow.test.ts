import { describe, expect, it } from 'vitest'
import i18n from '../i18n/config'
import {
  EMPLOYER_HOME,
  employerCreateAccountHref,
  employerSignInHref,
  employerWorkspaceGate,
  registerHref,
  shouldOpenEmployerRegisterForm,
} from './roleDestinations'

describe('employer auth flow', () => {
  it('sends create-employer-account to platform registration, not the blocked page', () => {
    const href = employerCreateAccountHref()
    expect(href).toBe(registerHref('employer', EMPLOYER_HOME))
    expect(href.startsWith('/register')).toBe(true)
    expect(href).toContain('audience=employer')
    expect(href).toContain(encodeURIComponent(EMPLOYER_HOME))
    expect(href).not.toContain('/employer/enter')
    expect(employerSignInHref().startsWith('/login')).toBe(true)
  })

  it('keeps the registration form open for guests and non-employer accounts', () => {
    expect(shouldOpenEmployerRegisterForm(false)).toBe(true)
    expect(shouldOpenEmployerRegisterForm(true)).toBe(false)
  })

  it('gates the employer workspace by backend recruitment role', () => {
    expect(employerWorkspaceGate({ is_job_seeker: true, is_employer: false })).toBe('blocked')
    expect(employerWorkspaceGate({ is_job_seeker: false, is_employer: true })).toBe('workspace')
    expect(employerWorkspaceGate({ is_job_seeker: false, is_employer: false })).toBe('activate')
    expect(employerWorkspaceGate(null)).toBe('activate')
  })

  it('uses the required Arabic mutual-exclusion copy', async () => {
    await i18n.changeLanguage('ar')
    expect(i18n.t('auth.employer.blockedJobSeeker')).toBe(
      'هذا الحساب مسجل بالفعل كطالب وظيفة ولا يمكن استخدامه كصاحب عمل.',
    )
    expect(i18n.t('auth.employer.blockedEmployer')).toBe(
      'هذا الحساب مسجل بالفعل كصاحب عمل ولا يمكن استخدامه كطالب وظيفة.',
    )
    expect(i18n.t('auth.employer.back')).toBe('العودة')
    expect(i18n.t('auth.employer.platformRegistration')).toBe('التسجيل في المنصة')
    expect(i18n.t('auth.employer.employmentServiceRegistration')).toBe(
      'التسجيل كصاحب عمل في خدمة التوظيف',
    )
  })
})
