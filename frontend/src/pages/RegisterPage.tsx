import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { getGoogleRedirect, register } from '../api'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function RegisterPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const requestedNext = params.get('next')
  const nextPath = requestedNext && requestedNext.startsWith('/') && !requestedNext.startsWith('//')
    ? requestedNext
    : '/dashboard'
  const { setSession, setOrganizationId } = useAuth()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleGoogle = async () => {
    setLoading(true)
    setError('')
    try {
      const result = await getGoogleRedirect()
      if ('error' in result || !('url' in result)) {
        setError(t('website.auth.unavailable'))
        return
      }
      sessionStorage.setItem('wsa.auth.next', nextPath)
      window.location.assign(result.url)
    } catch (requestError) {
      setError(translateApiError(requestError) || t('website.auth.unavailable'))
    } finally {
      setLoading(false)
    }
  }

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
      setSession(result.token, result.user)
      if (result.organization) {
        setOrganizationId(result.organization.id)
      }
      navigate(nextPath)
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
            <Link to="/login" className="gs-btn gs-btn-ghost">
              {t('website.nav.login')}
            </Link>
          </div>
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card">
          <Link to="/" className="public-auth-back">← {t('website.nav.home')}</Link>
          <p className="eyebrow">{t('auth.brand')}</p>
          <h1>{t('auth.registerTitle')}</h1>
          <p className="muted">{t('auth.registerSubtitle')}</p>
          <div className="public-auth-providers">
            <button type="button" className="public-auth-provider" disabled={loading} onClick={() => void handleGoogle()}>
              {t('website.auth.google')}
            </button>
          </div>
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
            {t('auth.alreadyHaveAccount')} <Link to="/login">{t('common.signIn')}</Link>
          </p>
        </section>
      </main>
    </div>
  )
}
