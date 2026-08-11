import { useState } from 'react'
import {
  createPermission,
  createRole,
  deletePermission,
  deleteRole,
  getPermissions,
  getRoles,
  isSystemRole,
  updateRole,
  unwrapModuleRows,
  type Permission,
  type Role,
} from '../../api'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function RolesPage() {
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()
  const [message, setMessage] = useState('')
  const [roleForm, setRoleForm] = useState({ name: '', description: '', permission_ids: [] as number[] })
  const [permissionForm, setPermissionForm] = useState({ name: '', description: '' })
  const [editRole, setEditRole] = useState<Role | null>(null)
  const [confirmDeleteRole, setConfirmDeleteRole] = useState<Role | null>(null)
  const [confirmDeletePermission, setConfirmDeletePermission] = useState<Permission | null>(null)

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

  const togglePermission = (permissionId: number, selected: number[]) =>
    selected.includes(permissionId)
      ? selected.filter((id) => id !== permissionId)
      : [...selected, permissionId]

  const handleCreateRole = async () => {
    if (!token) return
    setMessage('')
    try {
      await createRole(token, roleForm, organizationId ?? undefined)
      setRoleForm({ name: '', description: '', permission_ids: [] })
      setMessage('Role created successfully.')
      await reloadRoles()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to create role.')
    }
  }

  const handleCreatePermission = async () => {
    if (!token) return
    setMessage('')
    try {
      await createPermission(token, permissionForm, organizationId ?? undefined)
      setPermissionForm({ name: '', description: '' })
      setMessage('Permission created successfully.')
      await reloadPermissions()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to create permission.')
    }
  }

  const handleUpdateRole = async () => {
    if (!token || !editRole) return
    setMessage('')
    try {
      await updateRole(token, editRole.id, {
        name: editRole.name,
        description: editRole.description ?? undefined,
        permission_ids: editRole.permissions?.map((item) => item.id) ?? [],
      }, organizationId ?? undefined)
      setEditRole(null)
      setMessage('Role updated successfully.')
      await reloadRoles()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to update role.')
    }
  }

  const handleDeleteRole = async () => {
    if (!token || !confirmDeleteRole) return
    setMessage('')
    try {
      await deleteRole(token, confirmDeleteRole.id, organizationId ?? undefined)
      setConfirmDeleteRole(null)
      setMessage('Role deleted.')
      await reloadRoles()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to delete role.')
    }
  }

  const handleDeletePermission = async () => {
    if (!token || !confirmDeletePermission) return
    setMessage('')
    try {
      await deletePermission(token, confirmDeletePermission.id, organizationId ?? undefined)
      setConfirmDeletePermission(null)
      setMessage('Permission deleted.')
      await reloadPermissions()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to delete permission.')
    }
  }

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="Roles & permissions"
      description="Frontend checks are UX-only. Backend authorization remains authoritative."
    />

    {error && <ErrorBanner message={error} onRetry={() => { void reloadRoles(); void reloadPermissions() }} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">YOUR ACCESS</p><h2>Current permissions</h2></div></div>
      <p className="permission-tags">
        {(context?.permissions ?? []).map((permission) => (
          <span className="permission-tag" key={permission}>{permission}</span>
        ))}
      </p>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">CREATE</p><h2>New role</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreateRole() }}>
        <label>Name<input value={roleForm.name} onChange={(event) => setRoleForm({ ...roleForm, name: event.target.value })} required /></label>
        <label>Description<input value={roleForm.description} onChange={(event) => setRoleForm({ ...roleForm, description: event.target.value })} /></label>
        <fieldset>
          <legend>Permissions</legend>
          {permissions.map((permission) => (
            <label className="checkbox-label" key={permission.id}>
              <input
                type="checkbox"
                checked={roleForm.permission_ids.includes(permission.id)}
                onChange={() => setRoleForm({
                  ...roleForm,
                  permission_ids: togglePermission(permission.id, roleForm.permission_ids),
                })}
              />
              {permission.name}
            </label>
          ))}
        </fieldset>
        <button type="submit">Create role</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">ROLES</p><h2>Organization roles</h2></div></div>
      {rolesLoading ? <p className="loading">Loading roles…</p> : roles.length === 0 ? (
        <EmptyState title="No roles" description="Create a custom role or wait for enterprise seeding." />
      ) : (
        <DataTable
          rows={roles}
          rowKey={(role) => role.id}
          columns={[
            { key: 'name', header: 'Name', render: (role) => role.name },
            { key: 'slug', header: 'Slug', render: (role) => role.slug ?? '—' },
            { key: 'permissions', header: 'Permissions', render: (role) => role.permissions?.map((item) => item.name).join(', ') || '—' },
            {
              key: 'actions',
              header: 'Actions',
              render: (role) => isSystemRole(role) ? (
                <span className="muted">System role</span>
              ) : (
                <div className="inline-actions">
                  <button type="button" className="link-button inline" onClick={() => setEditRole({ ...role, permissions: role.permissions ?? [] })}>Edit</button>
                  <button type="button" className="link-button inline danger" onClick={() => setConfirmDeleteRole(role)}>Delete</button>
                </div>
              ),
            },
          ]}
        />
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">CREATE</p><h2>New permission</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreatePermission() }}>
        <label>Name<input value={permissionForm.name} onChange={(event) => setPermissionForm({ ...permissionForm, name: event.target.value })} required /></label>
        <label>Description<input value={permissionForm.description} onChange={(event) => setPermissionForm({ ...permissionForm, description: event.target.value })} /></label>
        <button type="submit">Create permission</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">PERMISSIONS</p><h2>Permission catalog</h2></div></div>
      {permissionsLoading ? <p className="loading">Loading permissions…</p> : permissions.length === 0 ? (
        <EmptyState title="No permissions" description="Permissions are seeded when the organization is provisioned." />
      ) : (
        <DataTable
          rows={permissions}
          rowKey={(permission) => permission.id}
          columns={[
            { key: 'name', header: 'Permission', render: (permission) => permission.name },
            { key: 'description', header: 'Description', render: (permission) => permission.description ?? '—' },
            {
              key: 'actions',
              header: 'Actions',
              render: (permission) => {
                const isCatalog = ['platform.view', 'access.manage', 'billing.view'].includes(permission.name)
                return isCatalog ? (
                  <span className="muted">Catalog</span>
                ) : (
                  <button type="button" className="link-button inline danger" onClick={() => setConfirmDeletePermission(permission)}>Delete</button>
                )
              },
            },
          ]}
        />
      )}
    </section>

    {editRole && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">EDIT</p><h2>{editRole.name}</h2></div></div>
        <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdateRole() }}>
          <label>Name<input value={editRole.name} onChange={(event) => setEditRole({ ...editRole, name: event.target.value })} required /></label>
          <label>Description<input value={editRole.description ?? ''} onChange={(event) => setEditRole({ ...editRole, description: event.target.value })} /></label>
          <fieldset>
            <legend>Permissions</legend>
            {permissions.map((permission) => (
              <label className="checkbox-label" key={permission.id}>
                <input
                  type="checkbox"
                  checked={(editRole.permissions ?? []).some((item) => item.id === permission.id)}
                  onChange={() => setEditRole({
                    ...editRole,
                    permissions: (editRole.permissions ?? []).some((item) => item.id === permission.id)
                      ? (editRole.permissions ?? []).filter((item) => item.id !== permission.id)
                      : [...(editRole.permissions ?? []), permission],
                  })}
                />
                {permission.name}
              </label>
            ))}
          </fieldset>
          <div className="confirm-actions">
            <button type="button" className="refresh" onClick={() => setEditRole(null)}>Cancel</button>
            <button type="submit">Save role</button>
          </div>
        </form>
      </section>
    )}

    {confirmDeleteRole && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">CONFIRM</p><h2>Delete role {confirmDeleteRole.name}?</h2></div></div>
        <p>This action cannot be undone. Roles assigned to users cannot be deleted.</p>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setConfirmDeleteRole(null)}>Cancel</button>
          <button type="button" className="danger" onClick={() => void handleDeleteRole()}>Delete role</button>
        </div>
      </section>
    )}

    {confirmDeletePermission && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">CONFIRM</p><h2>Delete permission {confirmDeletePermission.name}?</h2></div></div>
        <p>Permissions assigned to roles cannot be deleted.</p>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setConfirmDeletePermission(null)}>Cancel</button>
          <button type="button" className="danger" onClick={() => void handleDeletePermission()}>Delete permission</button>
        </div>
      </section>
    )}
  </>
}
