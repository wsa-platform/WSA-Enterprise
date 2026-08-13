import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router-dom'
import { register } from '../api'
import { useAuth } from '../context/AuthContext'
import { translateApiError } from '../i18n/apiErrors'

export function RegisterPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { setSession, setOrganizationId } = useAuth()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

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
      navigate('/')
    } catch (requestError) {
      setError(translateApiError(requestError) || t('auth.registerFailed'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="login-shell">
      <section className="login-card">
        <p className="eyebrow">{t('auth.brand')}</p>
        <h1>{t('auth.registerTitle')}</h1>
        <p className="muted">{t('auth.registerSubtitle')}</p>
        <form onSubmit={handleSubmit}>
          <label>{t('common.name')}<input value={name} onChange={(event) => setName(event.target.value)} required /></label>
          <label>{t('common.email')}<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required /></label>
          <label>{t('common.password')}<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required minLength={8} /></label>
          <label>{t('auth.confirmPassword')}<input value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)} type="password" required minLength={8} /></label>
          {error && <p className="error">{error}</p>}
          <button disabled={loading} type="submit">{loading ? t('auth.registering') : t('auth.createAccount')}</button>
        </form>
        <p className="hint">
          {t('auth.alreadyHaveAccount')} <Link to="/login">{t('common.signIn')}</Link>
        </p>
      </section>
    </main>
  )
}
