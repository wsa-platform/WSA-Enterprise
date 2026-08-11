import { useState, type FormEvent } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { getOrganizations, login } from '../api'
import { DEMO_HINT, getLoginDefaults, isDemoLoginEnabled } from '../config/loginDemo'
import { useAuth } from '../context/AuthContext'

export function LoginPage() {
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
      navigate('/')
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Sign in failed.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="login-shell">
      <section className="login-card">
        <p className="eyebrow">WSA ENTERPRISE</p>
        <h1>Integrated agricultural platform.</h1>
        <p className="muted" dir="auto">Sign in to access farms, diagnosis, training, library, and AI services.</p>
        {expired && <p className="banner">Your session has expired. Please sign in again.</p>}
        <form onSubmit={handleLogin}>
          <label>Email<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required /></label>
          <label>Password<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required /></label>
          {error && <p className="error">{error}</p>}
          <button disabled={loading} type="submit">{loading ? 'Signing in…' : 'Sign in'}</button>
        </form>
        {isDemoLoginEnabled() && <p className="hint">{DEMO_HINT}</p>}
      </section>
    </main>
  )
}
