import { getAccessSummary, getOrganizations } from '../api'
import { PageHeader } from '../components/PageHeader'
import { ErrorBanner, SkeletonGrid } from '../components/UiPrimitives'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { useAsyncData } from '../hooks/useAsyncData'

export function OrganizationPage() {
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()

  const { data: organizations, loading: orgLoading } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getOrganizations(token)
  }, [token])

  const { data: summary, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getAccessSummary(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('platform.view')) {
    return <ErrorBanner message="You do not have permission to view organization settings." />
  }

  const current = organizations?.find((org) => org.id === organizationId)

  if ((loading || orgLoading) && !summary) return <SkeletonGrid count={2} />
  if (error) return <ErrorBanner message={error} onRetry={reload} />
  if (!summary || !current) return null

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="Organization"
      description="Organization profile and workspace overview."
    />

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">PROFILE</p><h2>{current.name}</h2></div></div>
      <div className="detail-grid">
        <div><span>Slug</span><strong>{current.slug}</strong></div>
        <div><span>Your membership role</span><strong>{context?.membership_role ?? 'member'}</strong></div>
        <div><span>Assigned RBAC role</span><strong>{context?.roles[0]?.name ?? '—'}</strong></div>
        <div><span>Users</span><strong>{summary.users_count}</strong></div>
      </div>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">SETTINGS</p><h2>Organization settings API</h2></div></div>
      <p className="notice">
        Organization-level settings storage exists on the backend (`organization_settings`), but a management API is not yet exposed.
        Settings changes must be performed through backend administration until a dedicated endpoint is added.
      </p>
    </section>
  </>
}
