import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import { login, sendPhoneOtp, verifyPhoneOtp } from '../api'
import { DEMO_HINT, getLoginDefaults, isDemoLoginEnabled } from '../config/loginDemo'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'
import { authQuery, safeReturnPath } from '../navigation/routeGuards'
import {
  completeAuthenticatedSession,
  JOB_SEEKER_HOME,
  isUserDashboardPath,
  parseAudience,
  persistAudience,
} from '../navigation/roleDestinations'
import { AuthProviders } from './auth/AuthProviders'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const [params] = useSearchParams()
  const expired = (location.state as { expired?: boolean } | null)?.expired === true
  const audience = parseAudience(params.get('audience'))
  const defaultNext = audience === 'job_seeker' ? JOB_SEEKER_HOME : audience === 'employer' ? '/employer' : '/'
  const requestedNext = safeReturnPath(params.get('next'), defaultNext)
  const nextPath = isUserDashboardPath(requestedNext) ? defaultNext : requestedNext
  const { setSession, setOrganizationId } = useAuth()
  const loginDefaults = audience ? { email: '', password: '' } : getLoginDefaults()
  const [email, setEmail] = useState(loginDefaults.email)
  const [password, setPassword] = useState(loginDefaults.password)
  const [phone, setPhone] = useState('')
  const [otp, setOtp] = useState('')
  const [phoneName, setPhoneName] = useState('')
  const [phoneStep, setPhoneStep] = useState<'idle' | 'code'>('idle')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const title = audience === 'job_seeker'
    ? t('auth.entry.jobSeeker')
    : audience === 'employer'
      ? t('auth.employer.loginTitle')
      : t('auth.loginTitle')
  const subtitle = audience === 'job_seeker'
    ? t('auth.jobSeeker.loginSubtitle')
    : audience === 'employer'
      ? t('auth.employer.loginSubtitle')
      : t('auth.loginSubtitle')
  const registerTo = `/register${authQuery({ audience, next: nextPath })}`

  const finishLogin = async (token: string, user: { id: number; name: string; email: string }) => {
    persistAudience(audience)
    const destination = await completeAuthenticatedSession({
      token,
      user,
      audience,
      next: nextPath,
      setSession,
      setOrganizationId,
    })
    navigate(destination, { replace: true })
  }

  const handleLogin = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')
    try {
      const authenticated = await login(email, password, audience)
      await finishLogin(authenticated.token, authenticated.user)
    } catch (requestError) {
      setError(translateApiError(requestError) || t('auth.signInFailed'))
    } finally {
      setLoading(false)
    }
  }

  const handleSendOtp = async () => {
    setLoading(true)
    setError('')
    try {
      await sendPhoneOtp(phone.trim())
      setPhoneStep('code')
    } catch (requestError) {
      setError(translateApiError(requestError) || t('website.auth.unavailable'))
    } finally {
      setLoading(false)
    }
  }

  const handleVerifyOtp = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')
    try {
      const authenticated = await verifyPhoneOtp({
        phone: phone.trim(),
        code: otp.trim(),
        name: phoneName.trim() || undefined,
      })
      await finishLogin(authenticated.token, authenticated.user)
    } catch (requestError) {
      setError(translateApiError(requestError) || t('auth.signInFailed'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="public-site">
      <header className="public-header">
        <div className="public-header-inner">
          <Link to="/" className="public-brand">
            <span className="public-brand-mark" aria-hidden="true">W</span>
            <span>{t('website.brand')}</span>
          </Link>
          <div className="public-header-actions">
            <PublicLanguageMenu />
            <Link to={registerTo} className="gs-btn gs-btn-primary">
              {t('auth.jobSeeker.createAccount')}
            </Link>
          </div>
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card">
          <Link to="/jobs/enter" className="public-auth-back">← {t('auth.entry.backToChoices')}</Link>
          <p className="eyebrow">{t('auth.brand')}</p>
          <h1>{title}</h1>
          <p className="muted" dir="auto">{subtitle}</p>
          {audience === 'job_seeker' && (
            <div className="auth-mode-toggle" role="tablist" aria-label={t('auth.jobSeeker.modeLabel')}>
              <span className="auth-mode-toggle-active">{t('auth.jobSeeker.signIn')}</span>
              <Link to={registerTo}>{t('auth.jobSeeker.createAccount')}</Link>
            </div>
          )}
          {expired && <p className="banner">{t('auth.sessionExpired')}</p>}
          <AuthProviders
            loading={loading}
            nextPath={nextPath}
            audience={audience}
            onError={setError}
            onLoading={setLoading}
          />
          {audience !== 'job_seeker' && (
            <button type="button" className="public-auth-provider" disabled={loading} onClick={() => setPhoneStep(phoneStep === 'idle' ? 'code' : 'idle')}>
              {t('website.auth.phone')}
            </button>
          )}
          {phoneStep !== 'idle' && audience !== 'job_seeker' && (
            <form onSubmit={(event) => void handleVerifyOtp(event)}>
              <label>{t('auth.phoneLabel')}<input value={phone} onChange={(event) => setPhone(event.target.value)} required autoComplete="tel" /></label>
              <label>{t('auth.otpLabel')}<input value={otp} onChange={(event) => setOtp(event.target.value)} autoComplete="one-time-code" /></label>
              <label>{t('auth.phoneName')}<input value={phoneName} onChange={(event) => setPhoneName(event.target.value)} autoComplete="name" /></label>
              <button type="button" disabled={loading || !phone.trim()} onClick={() => void handleSendOtp()}>{t('auth.phoneSend')}</button>
              <button disabled={loading || !otp.trim()} type="submit">{t('auth.phoneVerify')}</button>
            </form>
          )}
          <p className="public-auth-divider">{t('website.auth.orEmail')}</p>
          <form onSubmit={(event) => void handleLogin(event)}>
            <label>{t('common.email')}<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required autoComplete="email" /></label>
            <label>{t('common.password')}<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required autoComplete="current-password" /></label>
            {error && <p className="error" role="alert">{error}</p>}
            <button disabled={loading} type="submit">{loading ? t('auth.signingIn') : t('common.signIn')}</button>
          </form>
          <p className="hint">
            <Link to={`/forgot-password${authQuery({ audience, next: nextPath })}`}>{t('auth.forgotPassword')}</Link>
          </p>
          <p className="hint">
            {t('auth.needAccount')} <Link to={registerTo}>{t('auth.createAccount')}</Link>
          </p>
          {!audience && isDemoLoginEnabled() && <p className="hint">{DEMO_HINT}</p>}
        </section>
      </main>
    </div>
  )
}
