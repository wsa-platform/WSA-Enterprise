import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  assignRole,
  createUser,
  getInvitations,
  getRoles,
  getUsers,
  inviteUser,
  removeUser,
  revokeInvitation,
  unassignRole,
  updateUser,
  unwrapModuleRows,
  type OrganizationInvitation,
  type Role,
  type UserWithRoles,
} from '../../api'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'

export function UsersPage() {
  const { t } = useTranslation()
  const { token, organizationId, user: currentUser } = useAuth()
  const { can } = usePermissions()
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [form, setForm] = useState({ name: '', email: '', password: '' })
  const [inviteForm, setInviteForm] = useState({ email: '', role: 'member' as 'admin' | 'member' })
  const [lastInvite, setLastInvite] = useState<OrganizationInvitation | null>(null)
  const [message, setMessage] = useState('')
  const [assignTarget, setAssignTarget] = useState<UserWithRoles | null>(null)
  const [editTarget, setEditTarget] = useState<UserWithRoles | null>(null)
  const [confirmRemove, setConfirmRemove] = useState<UserWithRoles | null>(null)
  const [selectedRoleId, setSelectedRoleId] = useState<number | ''>('')

  const { data: usersPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getUsers(token, organizationId ?? undefined)
  }, [token, organizationId, page])

  const { data: rolesPayload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getRoles(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: invitationsPayload, reload: reloadInvitations } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getInvitations(token, organizationId ?? undefined)
  }, [token, organizationId])

  const users = useMemo(() => {
    const rows = unwrapModuleRows(usersPayload ?? [])
    const term = search.trim().toLowerCase()
    return term
      ? rows.filter((user) => user.name.toLowerCase().includes(term) || user.email.toLowerCase().includes(term))
      : rows
  }, [usersPayload, search])

  if (!can('access.manage')) {
    return <ErrorBanner message={t('users.noPermission')} />
  }

  const roles = unwrapModuleRows(rolesPayload ?? []) as Role[]
  const invitations = unwrapModuleRows(invitationsPayload ?? []) as OrganizationInvitation[]
  const paginated = !Array.isArray(usersPayload)
  const pagination = paginated && usersPayload && !Array.isArray(usersPayload)
    ? { page: usersPayload.current_page, lastPage: usersPayload.last_page, total: usersPayload.total }
    : null

  const handleCreate = async () => {
    if (!token) return
    setMessage('')
    try {
      await createUser(token, form, organizationId ?? undefined)
      setForm({ name: '', email: '', password: '' })
      setMessage(t('users.created'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('users.createFailed'))
    }
  }

  const handleAssign = async () => {
    if (!token || !assignTarget || !selectedRoleId) return
    setMessage('')
    try {
      await assignRole(token, assignTarget.id, Number(selectedRoleId), organizationId ?? undefined)
      setAssignTarget(null)
      setSelectedRoleId('')
      setMessage(t('users.assigned'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('users.assignFailed'))
    }
  }

  const handleUpdate = async () => {
    if (!token || !editTarget) return
    setMessage('')
    try {
      await updateUser(token, editTarget.id, {
        name: editTarget.name,
        email: editTarget.email,
        membership_role: (editTarget.membership_role as 'admin' | 'member') ?? 'member',
        is_active: editTarget.is_active !== false,
      }, organizationId ?? undefined)
      setEditTarget(null)
      setMessage(t('users.updated'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('users.updateFailed'))
    }
  }

  const handleRemove = async () => {
    if (!token || !confirmRemove) return
    setMessage('')
    try {
      await removeUser(token, confirmRemove.id, organizationId ?? undefined)
      setConfirmRemove(null)
      setMessage(t('users.removed'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('users.removeFailed'))
    }
  }

  const handleUnassign = async (user: UserWithRoles, role: Role) => {
    if (!token) return
    setMessage('')
    try {
      await unassignRole(token, user.id, role.id, organizationId ?? undefined)
      setMessage(t('users.unassigned', { name: role.name }))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('users.unassignFailed'))
    }
  }

  const handleInvite = async () => {
    if (!token) return
    setMessage('')
    try {
      const invitation = await inviteUser(token, inviteForm, organizationId ?? undefined)
      setLastInvite(invitation)
      setInviteForm({ email: '', role: 'member' })
      setMessage(t('users.invitationSent'))
      await reloadInvitations()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('users.inviteFailed'))
    }
  }

  const handleRevokeInvite = async (invitation: OrganizationInvitation) => {
    if (!token) return
    setMessage('')
    try {
      await revokeInvitation(token, invitation.id, organizationId ?? undefined)
      setMessage(t('users.invitationRevoked'))
      await reloadInvitations()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('users.revokeFailed'))
    }
  }

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={t('users.title')}
      description={t('users.description')}
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.invite')}</p><h2>{t('users.invite')}</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleInvite() }}>
        <label>{t('common.email')}<input type="email" value={inviteForm.email} onChange={(event) => setInviteForm({ ...inviteForm, email: event.target.value })} required /></label>
        <label>
          {t('users.membershipRole')}
          <select value={inviteForm.role} onChange={(event) => setInviteForm({ ...inviteForm, role: event.target.value as 'admin' | 'member' })}>
            <option value="member">{t('common.member')}</option>
            <option value="admin">{t('common.admin')}</option>
          </select>
        </label>
        <button type="submit">{t('users.sendInvitation')}</button>
      </form>
      {lastInvite?.token && (
        <p className="muted">{t('users.acceptLink', { token: lastInvite.token })}</p>
      )}
    </section>

    {invitations.length > 0 && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.pending')}</p><h2>{t('users.pendingInvitations')}</h2></div></div>
        <DataTable
          rows={invitations}
          rowKey={(invitation) => invitation.id}
          columns={[
            { key: 'email', header: t('common.email'), render: (invitation) => invitation.email },
            { key: 'role', header: t('common.role'), render: (invitation) => invitation.role },
            { key: 'expires', header: t('common.expires'), render: (invitation) => new Date(invitation.expires_at).toLocaleString() },
            {
              key: 'actions',
              header: t('common.actions'),
              render: (invitation) => (
                <button type="button" className="link-button inline danger" onClick={() => void handleRevokeInvite(invitation)}>{t('common.revoke')}</button>
              ),
            },
          ]}
        />
      </section>
    )}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.createSection')}</p><h2>{t('users.addDirectly')}</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreate() }}>
        <label>{t('common.name')}<input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label>
        <label>{t('common.email')}<input type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required /></label>
        <label>{t('common.password')}<input type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} required minLength={8} /></label>
        <button type="submit">{t('users.createUser')}</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">{t('common.directory')}</p><h2>{t('users.directory')}</h2></div>
        <input className="search-input" value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('users.searchPlaceholder')} aria-label={t('users.searchAria')} />
      </div>
      {loading ? <p className="loading">{t('users.loadingUsers')}</p> : users.length === 0 ? (
        <EmptyState title={t('users.emptyTitle')} description={t('users.emptyDescription')} />
      ) : (
        <>
          <DataTable
            rows={users}
            rowKey={(user) => user.id}
            columns={[
              { key: 'name', header: t('common.name'), render: (user) => user.name },
              { key: 'email', header: t('common.email'), render: (user) => user.email },
              { key: 'status', header: t('common.status'), render: (user) => (
                <StatusBadge status={user.is_active === false ? 'inactive' : 'active'} />
              ) },
              { key: 'roles', header: t('users.roles'), render: (user) => user.roles?.map((role) => role.name).join(', ') || t('common.member') },
              {
                key: 'actions',
                header: t('common.actions'),
                render: (user) => (
                  <div className="inline-actions">
                    <button type="button" className="link-button inline" onClick={() => setEditTarget({ ...user })}>{t('common.edit')}</button>
                    <button type="button" className="link-button inline" onClick={() => setAssignTarget(user)}>{t('users.assignRole')}</button>
                    {user.id !== currentUser?.id && (
                      <button type="button" className="link-button inline danger" onClick={() => setConfirmRemove(user)}>{t('common.remove')}</button>
                    )}
                  </div>
                ),
              },
            ]}
          />
          {pagination && (
            <PaginationBar
              page={pagination.page}
              lastPage={pagination.lastPage}
              total={pagination.total}
              onPageChange={setPage}
            />
          )}
        </>
      )}
    </section>

    {assignTarget && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.assign')}</p><h2>{assignTarget.name}</h2></div></div>
        <label>
          {t('common.role')}
          <select value={selectedRoleId} onChange={(event) => setSelectedRoleId(event.target.value ? Number(event.target.value) : '')}>
            <option value="">{t('users.selectRole')}</option>
            {roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
          </select>
        </label>
        {assignTarget.roles && assignTarget.roles.length > 0 && (
          <div className="inline-actions">
            {assignTarget.roles.map((role) => (
              <button key={role.id} type="button" className="refresh" onClick={() => void handleUnassign(assignTarget, role)}>
                {t('users.unassign', { name: role.name })}
              </button>
            ))}
          </div>
        )}
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setAssignTarget(null)}>{t('common.cancel')}</button>
          <button type="button" onClick={() => void handleAssign()}>{t('users.assignRole')}</button>
        </div>
      </section>
    )}

    {editTarget && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.edit')}</p><h2>{editTarget.name}</h2></div></div>
        <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdate() }}>
          <label>{t('common.name')}<input value={editTarget.name} onChange={(event) => setEditTarget({ ...editTarget, name: event.target.value })} required /></label>
          <label>{t('common.email')}<input type="email" value={editTarget.email} onChange={(event) => setEditTarget({ ...editTarget, email: event.target.value })} required /></label>
          <label>
            {t('users.membershipRole')}
            <select
              value={editTarget.membership_role ?? 'member'}
              onChange={(event) => setEditTarget({ ...editTarget, membership_role: event.target.value })}
            >
              <option value="member">{t('common.member')}</option>
              <option value="admin">{t('common.admin')}</option>
            </select>
          </label>
          <label className="checkbox-label">
            <input
              type="checkbox"
              checked={editTarget.is_active !== false}
              onChange={(event) => setEditTarget({ ...editTarget, is_active: event.target.checked })}
              disabled={editTarget.id === currentUser?.id}
            />
            {t('users.activeInOrg')}
          </label>
          <div className="confirm-actions">
            <button type="button" className="refresh" onClick={() => setEditTarget(null)}>{t('common.cancel')}</button>
            <button type="submit">{t('users.saveChanges')}</button>
          </div>
        </form>
      </section>
    )}

    {confirmRemove && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">{t('common.confirmEyebrow')}</p><h2>{t('users.confirmRemoveTitle', { name: confirmRemove.name })}</h2></div></div>
        <p>{t('users.confirmRemoveMessage')}</p>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setConfirmRemove(null)}>{t('common.cancel')}</button>
          <button type="button" className="danger" onClick={() => void handleRemove()}>{t('users.removeUser')}</button>
        </div>
      </section>
    )}
  </>
}
