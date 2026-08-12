import { PageHeader } from '../components/PageHeader'
import { ErrorBanner } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'
import { getAuthSessions, revokeAuthSession, type AuthSession } from '../api'

export function SettingsPage() {
  const { token } = useAuth()
  const { can, context } = usePermissions()
  const { user } = useAuth()

  const { data: sessions, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getAuthSessions(token)
  }, [token])

  const handleRevoke = async (session: AuthSession) => {
    if (!token || session.is_current) return
    await revokeAuthSession(token, session.id)
    await reload()
  }

  return <>
    <PageHeader eyebrow="ACCOUNT" title="Settings" description="Profile and workspace preferences." />

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">PROFILE</p><h2>Your account</h2></div></div>
      <div className="detail-grid">
        <div><span>Name</span><strong>{user?.name}</strong></div>
        <div><span>Email</span><strong>{user?.email}</strong></div>
      </div>
    </section>

    {can('platform.view') && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">ORGANIZATION</p><h2>Workspace access</h2></div></div>
        <div className="detail-grid">
          <div><span>Organization ID</span><strong>{context?.organization_id ?? '—'}</strong></div>
          <div><span>Membership role</span><strong>{context?.membership_role ?? '—'}</strong></div>
        </div>
      </section>
    )}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">SECURITY</p><h2>Active sessions</h2></div></div>
      {error && <ErrorBanner message={error} onRetry={reload} />}
      {loading ? <p className="loading">Loading sessions…</p> : (
        <div className="detail-grid">
          {(sessions ?? []).map((session) => (
            <div key={session.id}>
              <span>{session.name}{session.is_current ? ' (current)' : ''}</span>
              <strong>
                {session.last_used_at ? new Date(session.last_used_at).toLocaleString() : 'Never used'}
                {!session.is_current && (
                  <button type="button" className="link-button inline" onClick={() => void handleRevoke(session)} style={{ marginLeft: '0.75rem' }}>
                    Revoke
                  </button>
                )}
              </strong>
            </div>
          ))}
        </div>
      )}
      <p className="muted">Use Sign out in the sidebar to end your current session.</p>
    </section>
  </>
}
