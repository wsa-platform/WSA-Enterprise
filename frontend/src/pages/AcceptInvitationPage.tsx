import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { acceptInvitation } from '../api'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'
import { completeAuthenticatedSession } from '../navigation/roleDestinations'

export function AcceptInvitationPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const tokenParam = params.get('token') ?? ''
  const { setSession, setOrganizationId } = useAuth()
  const [name, setName] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!tokenParam) {
      setError(t('auth.tokenMissing'))
      return
    }

    setLoading(true)
    setError('')
    try {
      const result = await acceptInvitation({
        token: tokenParam,
        name: name.trim() || undefined,
        password,
      })
      const destination = await completeAuthenticatedSession({
        token: result.token,
        user: result.user,
        setSession,
        setOrganizationId,
      })
      navigate(destination, { replace: true })
    } catch (requestError) {
      setError(translateApiError(requestError) || t('auth.acceptFailed'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="login-shell">
      <section className="login-card">
        <p className="eyebrow">{t('auth.brand')}</p>
        <h1>{t('auth.acceptInvitation')}</h1>
        <p className="muted">{t('auth.acceptSubtitle')}</p>
        <form onSubmit={handleSubmit}>
          <label>{t('common.name')}<input value={name} onChange={(event) => setName(event.target.value)} placeholder={t('auth.namePlaceholder')} /></label>
          <label>{t('common.password')}<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required minLength={8} /></label>
          {error && <p className="error">{error}</p>}
          <button disabled={loading || !tokenParam} type="submit">{loading ? t('auth.joining') : t('auth.acceptInvitation')}</button>
        </form>
      </section>
    </main>
  )
}
