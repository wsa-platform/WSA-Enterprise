import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useSearchParams } from 'react-router-dom'
import { resetPassword } from '../api'
import { translateApiError } from '../i18n/apiErrors'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function ResetPasswordPage() {
  const { t } = useTranslation()
  const [params] = useSearchParams()
  const [email, setEmail] = useState(params.get('email') ?? '')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [loading, setLoading] = useState(false)
  const token = params.get('token') ?? ''

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')
    setNotice('')
    try {
      await resetPassword({
        token,
        email: email.trim(),
        password,
        password_confirmation: passwordConfirmation,
      })
      setNotice(t('auth.resetSuccess'))
    } catch (requestError) {
      setError(translateApiError(requestError) || t('auth.resetFailed'))
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
            <Link to="/login" className="gs-btn gs-btn-ghost">{t('website.nav.login')}</Link>
          </div>
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card">
          <Link to="/login" className="public-auth-back">← {t('auth.backToLogin')}</Link>
          <p className="eyebrow">{t('auth.brand')}</p>
          <h1>{t('auth.resetTitle')}</h1>
          <form onSubmit={(event) => void handleSubmit(event)}>
            <label>{t('common.email')}<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required autoComplete="email" /></label>
            <label>{t('common.password')}<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required minLength={8} autoComplete="new-password" /></label>
            <label>{t('auth.confirmPassword')}<input value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)} type="password" required minLength={8} autoComplete="new-password" /></label>
            {error && <p className="error" role="alert">{error}</p>}
            {notice && <p className="notice">{notice}</p>}
            <button disabled={loading || !token} type="submit">{loading ? t('common.saving') : t('auth.resetSubmit')}</button>
          </form>
        </section>
      </main>
    </div>
  )
}
