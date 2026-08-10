import { getPermissions, getRoles, type Permission, type Role } from '../../api'
import { unwrapModuleRows } from '../../api/client'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function RolesPage() {
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()

  const { data: rolesPayload, loading: rolesLoading, error: rolesError, reload: reloadRoles } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getRoles(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: permissionsPayload, loading: permissionsLoading, error: permissionsError, reload: reloadPermissions } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getPermissions(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message="You do not have permission to view roles and permissions." />
  }

  const roles = unwrapModuleRows(rolesPayload ?? []) as Role[]
  const permissions = unwrapModuleRows(permissionsPayload ?? []) as Permission[]
  const error = rolesError || permissionsError

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="Roles & permissions"
      description="Frontend checks are UX-only. Backend authorization remains authoritative."
    />

    {error && <ErrorBanner message={error} onRetry={() => { void reloadRoles(); void reloadPermissions() }} />}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">YOUR ACCESS</p><h2>Current permissions</h2></div></div>
      <p className="permission-tags">
        {(context?.permissions ?? []).map((permission) => (
          <span className="permission-tag" key={permission}>{permission}</span>
        ))}
      </p>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">ROLES</p><h2>Organization roles</h2></div></div>
      {rolesLoading ? <p className="loading">Loading roles…</p> : (
        <DataTable
          rows={roles}
          rowKey={(role) => role.id}
          columns={[
            { key: 'name', header: 'Name', render: (role) => role.name },
            { key: 'slug', header: 'Slug', render: (role) => role.slug ?? '—' },
            { key: 'permissions', header: 'Permissions', render: (role) => role.permissions?.map((item) => item.name).join(', ') || '—' },
          ]}
        />
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">PERMISSIONS</p><h2>Permission catalog</h2></div></div>
      {permissionsLoading ? <p className="loading">Loading permissions…</p> : (
        <DataTable
          rows={permissions}
          rowKey={(permission) => permission.id}
          columns={[
            { key: 'name', header: 'Permission', render: (permission) => permission.name },
            { key: 'description', header: 'Description', render: (permission) => permission.description ?? '—' },
          ]}
        />
      )}
    </section>
  </>
}
