import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { forgotPassword } from '../api'
import { translateApiError } from '../i18n/apiErrors'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function ForgotPasswordPage() {
  const { t } = useTranslation()
  const [email, setEmail] = useState('')
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')
    setNotice('')
    try {
      await forgotPassword(email.trim())
      setNotice(t('auth.forgotSuccess'))
    } catch (requestError) {
      setError(translateApiError(requestError) || t('auth.forgotFailed'))
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
          <h1>{t('auth.forgotPasswordTitle')}</h1>
          <p className="muted">{t('auth.forgotPasswordSubtitle')}</p>
          <form onSubmit={(event) => void handleSubmit(event)}>
            <label>{t('common.email')}<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required autoComplete="email" /></label>
            {error && <p className="error" role="alert">{error}</p>}
            {notice && <p className="notice">{notice}</p>}
            <button disabled={loading} type="submit">{loading ? t('common.saving') : t('auth.sendResetLink')}</button>
          </form>
        </section>
      </main>
    </div>
  )
}
