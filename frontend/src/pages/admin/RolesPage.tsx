import { useState } from 'react'
import { useTranslation } from 'react-i18next'
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
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'

export function RolesPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()
  const [message, setMessage] = useState('')
  const [roleForm, setRoleForm] = useState({ name: '', description: '', permission_ids: [] as number[] })
  const [permissionForm, setPermissionForm] = useState({ name: '', description: '' })
  const [editRole, setEditRole] = useState<Role | null>(null)
  const [confirmDeleteRole, setConfirmDeleteRole] = useState<Role | null>(null)
  const [confirmDeletePermission, setConfirmDeletePermission] = useState<Permission | null>(null)

  const { data: rolesPayload, loading: rolesLoading, error: rolesError, reload: reloadRoles } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getRoles(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: permissionsPayload, loading: permissionsLoading, error: permissionsError, reload: reloadPermissions } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getPermissions(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message={t('roles.noPermission')} />
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
      setMessage(t('roles.roleCreated'))
      await reloadRoles()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('roles.roleCreateFailed'))
    }
  }

  const handleCreatePermission = async () => {
    if (!token) return
    setMessage('')
    try {
      await createPermission(token, permissionForm, organizationId ?? undefined)
      setPermissionForm({ name: '', description: '' })
      setMessage(t('roles.permissionCreated'))
      await reloadPermissions()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('roles.permissionCreateFailed'))
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
      setMessage(t('roles.roleUpdated'))
      await reloadRoles()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('roles.roleUpdateFailed'))
    }
  }

  const handleDeleteRole = async () => {
    if (!token || !confirmDeleteRole) return
    setMessage('')
    try {
      await deleteRole(token, confirmDeleteRole.id, organizationId ?? undefined)
      setConfirmDeleteRole(null)
      setMessage(t('roles.roleDeleted'))
      await reloadRoles()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('roles.roleDeleteFailed'))
    }
  }

  const handleDeletePermission = async () => {
    if (!token || !confirmDeletePermission) return
    setMessage('')
    try {
      await deletePermission(token, confirmDeletePermission.id, organizationId ?? undefined)
      setConfirmDeletePermission(null)
      setMessage(t('roles.permissionDeleted'))
      await reloadPermissions()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('roles.permissionDeleteFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={t('roles.title')}
      description={t('roles.descriptionUx')}
    />

    {error && <ErrorBanner message={error} onRetry={() => { void reloadRoles(); void reloadPermissions() }} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.yourAccess')}</p><h2>{t('roles.currentPermissions')}</h2></div></div>
      <p className="permission-tags">
        {(context?.permissions ?? []).map((permission) => (
          <span className="permission-tag" key={permission}>{permission}</span>
        ))}
      </p>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.createSection')}</p><h2>{t('roles.newRole')}</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreateRole() }}>
        <label>{t('common.name')}<input value={roleForm.name} onChange={(event) => setRoleForm({ ...roleForm, name: event.target.value })} required /></label>
        <label>{t('common.description')}<input value={roleForm.description} onChange={(event) => setRoleForm({ ...roleForm, description: event.target.value })} /></label>
        <fieldset>
          <legend>{t('roles.permissionIds')}</legend>
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
        <button type="submit">{t('roles.createRole')}</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('roles.rolesSection')}</p><h2>{t('roles.orgRoles')}</h2></div></div>
      {rolesLoading ? <p className="loading">{t('roles.loadingRoles')}</p> : roles.length === 0 ? (
        <EmptyState title={t('roles.emptyRolesTitle')} description={t('roles.emptyRolesDescription')} />
      ) : (
        <DataTable
          rows={roles}
          rowKey={(role) => role.id}
          columns={[
            { key: 'name', header: t('common.name'), render: (role) => role.name },
            { key: 'slug', header: t('common.slug'), render: (role) => role.slug ?? '—' },
            { key: 'permissions', header: t('roles.permissionIds'), render: (role) => role.permissions?.map((item) => item.name).join(', ') || '—' },
            {
              key: 'actions',
              header: t('common.actions'),
              render: (role) => isSystemRole(role) ? (
                <span className="muted">{t('roles.systemRole')}</span>
              ) : (
                <div className="inline-actions">
                  <button type="button" className="link-button inline" onClick={() => setEditRole({ ...role, permissions: role.permissions ?? [] })}>{t('common.edit')}</button>
                  <button type="button" className="link-button inline danger" onClick={() => setConfirmDeleteRole(role)}>{t('common.delete')}</button>
                </div>
              ),
            },
          ]}
        />
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.createSection')}</p><h2>{t('roles.newPermission')}</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreatePermission() }}>
        <label>{t('common.name')}<input value={permissionForm.name} onChange={(event) => setPermissionForm({ ...permissionForm, name: event.target.value })} required /></label>
        <label>{t('common.description')}<input value={permissionForm.description} onChange={(event) => setPermissionForm({ ...permissionForm, description: event.target.value })} /></label>
        <button type="submit">{t('roles.createPermission')}</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('roles.permissionsSection')}</p><h2>{t('roles.permissionCatalog')}</h2></div></div>
      {permissionsLoading ? <p className="loading">{t('roles.loadingPermissions')}</p> : permissions.length === 0 ? (
        <EmptyState title={t('roles.emptyPermissionsTitle')} description={t('roles.emptyPermissionsDescription')} />
      ) : (
        <DataTable
          rows={permissions}
          rowKey={(permission) => permission.id}
          columns={[
            { key: 'name', header: t('common.permission'), render: (permission) => permission.name },
            { key: 'description', header: t('common.description'), render: (permission) => permission.description ?? '—' },
            {
              key: 'actions',
              header: t('common.actions'),
              render: (permission) => {
                const isCatalog = ['platform.view', 'access.manage', 'billing.view'].includes(permission.name)
                return isCatalog ? (
                  <span className="muted">{t('common.catalog')}</span>
                ) : (
                  <button type="button" className="link-button inline danger" onClick={() => setConfirmDeletePermission(permission)}>{t('common.delete')}</button>
                )
              },
            },
          ]}
        />
      )}
    </section>

    {editRole && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.edit')}</p><h2>{editRole.name}</h2></div></div>
        <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdateRole() }}>
          <label>{t('common.name')}<input value={editRole.name} onChange={(event) => setEditRole({ ...editRole, name: event.target.value })} required /></label>
          <label>{t('common.description')}<input value={editRole.description ?? ''} onChange={(event) => setEditRole({ ...editRole, description: event.target.value })} /></label>
          <fieldset>
            <legend>{t('roles.permissionIds')}</legend>
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
            <button type="button" className="refresh" onClick={() => setEditRole(null)}>{t('common.cancel')}</button>
            <button type="submit">{t('roles.saveRole')}</button>
          </div>
        </form>
      </section>
    )}

    {confirmDeleteRole && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.confirmEyebrow')}</p><h2>{t('roles.confirmDeleteRoleTitle', { name: confirmDeleteRole.name })}</h2></div></div>
        <p>{t('roles.deleteRoleBlocked')}</p>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setConfirmDeleteRole(null)}>{t('common.cancel')}</button>
          <button type="button" className="danger" onClick={() => void handleDeleteRole()}>{t('roles.deleteRole')}</button>
        </div>
      </section>
    )}

    {confirmDeletePermission && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.confirmEyebrow')}</p><h2>{t('roles.confirmDeletePermissionTitle', { name: confirmDeletePermission.name })}</h2></div></div>
        <p>{t('roles.deletePermissionBlocked')}</p>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setConfirmDeletePermission(null)}>{t('common.cancel')}</button>
          <button type="button" className="danger" onClick={() => void handleDeletePermission()}>{t('roles.deletePermission')}</button>
        </div>
      </section>
    )}
  </>
}
