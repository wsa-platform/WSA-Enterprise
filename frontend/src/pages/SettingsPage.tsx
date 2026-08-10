import { PageHeader } from '../components/PageHeader'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'

export function SettingsPage() {
  const { user } = useAuth()
  const { can, context } = usePermissions()

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
      <div className="panel-heading"><div><p className="eyebrow">SECURITY</p><h2>Session</h2></div></div>
      <p className="muted">Use Sign out in the sidebar to end your session. Password changes are managed by organization administrators.</p>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">PREFERENCES</p><h2>UI preferences</h2></div></div>
      <p className="muted">Theme and notification preferences will appear here when preference APIs are available.</p>
    </section>
  </>
}
