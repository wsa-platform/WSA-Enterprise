import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { register } from '../api'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'
import { authQuery, safeReturnPath } from '../navigation/routeGuards'
import {
  completeAuthenticatedSession,
  JOB_SEEKER_HOME,
  parseAudience,
} from '../navigation/roleDestinations'
import { AuthProviders } from './auth/AuthProviders'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function RegisterPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const audience = parseAudience(params.get('audience'))
  const defaultNext = audience === 'job_seeker' ? JOB_SEEKER_HOME : audience === 'employer' ? '/employer' : '/'
  const nextPath = safeReturnPath(params.get('next'), defaultNext)
  const { setSession, setOrganizationId } = useAuth()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const loginTo = `/login${authQuery({ audience, next: nextPath })}`

  const title = audience === 'job_seeker'
    ? t('auth.entry.jobSeeker')
    : audience === 'employer'
      ? t('auth.employer.registerTitle')
      : t('auth.registerTitle')
  const subtitle = audience === 'job_seeker'
    ? t('auth.jobSeeker.registerSubtitle')
    : audience === 'employer'
      ? t('auth.employer.registerSubtitle')
      : t('auth.registerSubtitle')

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')

    try {
      const result = await register({
        name: name.trim(),
        email: email.trim(),
        password,
        password_confirmation: passwordConfirmation,
      })
      const destination = await completeAuthenticatedSession({
        token: result.token,
        user: result.user,
        audience,
        next: nextPath,
        setSession,
        setOrganizationId,
      })
      navigate(destination, { replace: true })
    } catch (requestError) {
      setError(translateApiError(requestError) || t('auth.registerFailed'))
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
            <Link to={loginTo} className="gs-btn gs-btn-ghost">
              {t('auth.jobSeeker.signIn')}
            </Link>
          </div>
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card">
          <Link to="/jobs/enter" className="public-auth-back">← {t('auth.entry.backToChoices')}</Link>
          <p className="eyebrow">{t('auth.brand')}</p>
          <h1>{title}</h1>
          <p className="muted">{subtitle}</p>
          {audience === 'job_seeker' && (
            <div className="auth-mode-toggle" role="tablist" aria-label={t('auth.jobSeeker.modeLabel')}>
              <Link to={loginTo}>{t('auth.jobSeeker.signIn')}</Link>
              <span className="auth-mode-toggle-active">{t('auth.jobSeeker.createAccount')}</span>
            </div>
          )}
          <AuthProviders
            loading={loading}
            nextPath={nextPath}
            audience={audience}
            onError={setError}
            onLoading={setLoading}
          />
          <p className="public-auth-divider">{t('website.auth.orEmail')}</p>
          <form onSubmit={handleSubmit}>
            <label>{t('common.name')}<input value={name} onChange={(event) => setName(event.target.value)} required autoComplete="name" /></label>
            <label>{t('common.email')}<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required autoComplete="email" /></label>
            <label>{t('common.password')}<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required minLength={8} autoComplete="new-password" /></label>
            <label>{t('auth.confirmPassword')}<input value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)} type="password" required minLength={8} autoComplete="new-password" /></label>
            {error && <p className="error" role="alert">{error}</p>}
            <button disabled={loading} type="submit">{loading ? t('auth.registering') : t('auth.createAccount')}</button>
          </form>
          <p className="hint">
            {t('auth.alreadyHaveAccount')} <Link to={loginTo}>{t('common.signIn')}</Link>
          </p>
        </section>
      </main>
    </div>
  )
}
