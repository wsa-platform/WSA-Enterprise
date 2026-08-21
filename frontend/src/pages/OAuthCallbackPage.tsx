import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { completeFacebookCallback, completeGoogleCallback } from '../api'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'
import { safeReturnPath } from '../navigation/routeGuards'
import {
  AUTH_NEXT_STORAGE_KEY,
  AUTH_PROVIDER_STORAGE_KEY,
  completeAuthenticatedSession,
  JOB_SEEKER_HOME,
  isUserDashboardPath,
  parseAudience,
  readStoredAudience,
} from '../navigation/roleDestinations'
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
        const provider = sessionStorage.getItem(AUTH_PROVIDER_STORAGE_KEY) === 'facebook' ? 'facebook' : 'google'
        const authenticated = provider === 'facebook'
          ? await completeFacebookCallback(code, state)
          : await completeGoogleCallback(code, state)
        const audience = parseAudience(params.get('audience')) ?? readStoredAudience()
        const stored = sessionStorage.getItem(AUTH_NEXT_STORAGE_KEY)
        sessionStorage.removeItem(AUTH_NEXT_STORAGE_KEY)
        sessionStorage.removeItem(AUTH_PROVIDER_STORAGE_KEY)
        const defaultNext = audience === 'job_seeker' ? JOB_SEEKER_HOME : audience === 'employer' ? '/employer' : '/'
        const storedNext = safeReturnPath(stored, defaultNext)
        const next = isUserDashboardPath(storedNext) ? defaultNext : storedNext
        const destination = await completeAuthenticatedSession({
          token: authenticated.token,
          user: authenticated.user,
          audience,
          next,
          setSession,
          setOrganizationId,
        })
        navigate(destination, { replace: true })
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
              <Link to="/jobs/enter">{t('auth.backToLogin')}</Link>
            </>
          ) : (
            <p className="muted">{t('common.loading')}</p>
          )}
        </section>
      </main>
    </div>
  )
}
