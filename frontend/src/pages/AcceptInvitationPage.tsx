import { useState, type FormEvent } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { acceptInvitation } from '../api'
import { useAuth } from '../context/AuthContext'

export function AcceptInvitationPage() {
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
      setError('Invitation token is missing.')
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
      setSession(result.token, result.user)
      setOrganizationId(result.organization.id)
      navigate('/')
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to accept invitation.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="login-shell">
      <section className="login-card">
        <p className="eyebrow">WSA ENTERPRISE</p>
        <h1>Accept invitation</h1>
        <p className="muted">Complete your account to join the organization workspace.</p>
        <form onSubmit={handleSubmit}>
          <label>Name<input value={name} onChange={(event) => setName(event.target.value)} placeholder="Required for new accounts" /></label>
          <label>Password<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required minLength={8} /></label>
          {error && <p className="error">{error}</p>}
          <button disabled={loading || !tokenParam} type="submit">{loading ? 'Joining…' : 'Accept invitation'}</button>
        </form>
      </section>
    </main>
  )
}
