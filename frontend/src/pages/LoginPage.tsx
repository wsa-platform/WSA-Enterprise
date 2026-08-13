import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { getOrganizations, login } from '../api'
import { DEMO_HINT, getLoginDefaults, isDemoLoginEnabled } from '../config/loginDemo'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const expired = (location.state as { expired?: boolean } | null)?.expired === true
  const { setSession, setOrganizationId } = useAuth()
  const loginDefaults = getLoginDefaults()
  const [email, setEmail] = useState(loginDefaults.email)
  const [password, setPassword] = useState(loginDefaults.password)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleLogin = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')
    try {
      const authenticated = await login(email, password)
      setSession(authenticated.token, authenticated.user)
      const organizations = await getOrganizations(authenticated.token)
      if (organizations[0]) setOrganizationId(organizations[0].id)
      navigate('/dashboard')
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
            <Link to="/register" className="gs-btn gs-btn-primary">
              {t('website.nav.register')}
            </Link>
          </div>
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card">
          <Link to="/" className="public-auth-back">← {t('website.nav.home')}</Link>
          <p className="eyebrow">{t('auth.brand')}</p>
          <h1>{t('auth.loginTitle')}</h1>
          <p className="muted" dir="auto">{t('auth.loginSubtitle')}</p>
          {expired && <p className="banner">{t('auth.sessionExpired')}</p>}
          <div className="public-auth-providers">
            <button type="button" className="public-auth-provider" disabled aria-disabled="true">
              {t('website.auth.googleSoon')}
            </button>
            <button type="button" className="public-auth-provider" disabled aria-disabled="true">
              {t('website.auth.phoneSoon')}
            </button>
          </div>
          <p className="public-auth-divider">{t('website.auth.orEmail')}</p>
          <form onSubmit={handleLogin}>
            <label>{t('common.email')}<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required autoComplete="email" /></label>
            <label>{t('common.password')}<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required autoComplete="current-password" /></label>
            {error && <p className="error" role="alert">{error}</p>}
            <button disabled={loading} type="submit">{loading ? t('auth.signingIn') : t('common.signIn')}</button>
          </form>
          <p className="hint">
            {t('auth.needAccount')} <Link to="/register">{t('auth.createAccount')}</Link>
          </p>
          {isDemoLoginEnabled() && <p className="hint">{DEMO_HINT}</p>}
        </section>
      </main>
    </div>
  )
}
