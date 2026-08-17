import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { completeGoogleCallback, getOrganizations } from '../api'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function OAuthCallbackPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const { setSession, setOrganizationId } = useAuth()
  const [error, setError] = useState('')

  useEffect(() => {
    const code = params.get('code')
    const state = params.get('state')
    if (!code || !state) {
      setError(t('auth.googleFailed'))
      return
    }

    void (async () => {
      try {
        const authenticated = await completeGoogleCallback(code, state)
        setSession(authenticated.token, authenticated.user)
        const organizations = await getOrganizations(authenticated.token)
        if (organizations[0]) setOrganizationId(organizations[0].id)
        const stored = sessionStorage.getItem('wsa.auth.next') || '/dashboard'
        sessionStorage.removeItem('wsa.auth.next')
        const next = stored.startsWith('/') && !stored.startsWith('//') ? stored : '/dashboard'
        navigate(next, { replace: true })
      } catch (requestError) {
        setError(translateApiError(requestError) || t('auth.googleFailed'))
      }
    })()
  }, [params, navigate, setOrganizationId, setSession, t])

  return (
    <div className="public-site">
      <header className="public-header">
        <div className="public-header-inner">
          <Link to="/" className="public-brand">
            <span className="public-brand-mark" aria-hidden="true">W</span>
            <span>{t('website.brand')}</span>
          </Link>
          <PublicLanguageMenu />
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card">
          <h1>{t('auth.completingSignIn')}</h1>
          {error ? (
            <>
              <p className="error" role="alert">{error}</p>
              <Link to="/login">{t('auth.backToLogin')}</Link>
            </>
          ) : (
            <p className="muted">{t('common.loading')}</p>
          )}
        </section>
      </main>
    </div>
  )
}
