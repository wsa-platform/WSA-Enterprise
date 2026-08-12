import { useMemo, useState } from 'react'
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

export function UsersPage() {
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
    if (!token) throw new Error('Not authenticated.')
    return getUsers(token, organizationId ?? undefined)
  }, [token, organizationId, page])

  const { data: rolesPayload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getRoles(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: invitationsPayload, reload: reloadInvitations } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
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
    return <ErrorBanner message="You do not have permission to manage users." />
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
      setMessage('User created successfully.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to create user.')
    }
  }

  const handleAssign = async () => {
    if (!token || !assignTarget || !selectedRoleId) return
    setMessage('')
    try {
      await assignRole(token, assignTarget.id, Number(selectedRoleId), organizationId ?? undefined)
      setAssignTarget(null)
      setSelectedRoleId('')
      setMessage('Role assigned successfully.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to assign role.')
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
      setMessage('User updated successfully.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to update user.')
    }
  }

  const handleRemove = async () => {
    if (!token || !confirmRemove) return
    setMessage('')
    try {
      await removeUser(token, confirmRemove.id, organizationId ?? undefined)
      setConfirmRemove(null)
      setMessage('User removed from organization.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to remove user.')
    }
  }

  const handleUnassign = async (user: UserWithRoles, role: Role) => {
    if (!token) return
    setMessage('')
    try {
      await unassignRole(token, user.id, role.id, organizationId ?? undefined)
      setMessage(`Role "${role.name}" unassigned.`)
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to unassign role.')
    }
  }

  const handleInvite = async () => {
    if (!token) return
    setMessage('')
    try {
      const invitation = await inviteUser(token, inviteForm, organizationId ?? undefined)
      setLastInvite(invitation)
      setInviteForm({ email: '', role: 'member' })
      setMessage('Invitation sent.')
      await reloadInvitations()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to send invitation.')
    }
  }

  const handleRevokeInvite = async (invitation: OrganizationInvitation) => {
    if (!token) return
    setMessage('')
    try {
      await revokeInvitation(token, invitation.id, organizationId ?? undefined)
      setMessage('Invitation revoked.')
      await reloadInvitations()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to revoke invitation.')
    }
  }

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="User management"
      description="Manage organization members, roles, and access."
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">INVITE</p><h2>Invite user</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleInvite() }}>
        <label>Email<input type="email" value={inviteForm.email} onChange={(event) => setInviteForm({ ...inviteForm, email: event.target.value })} required /></label>
        <label>
          Membership role
          <select value={inviteForm.role} onChange={(event) => setInviteForm({ ...inviteForm, role: event.target.value as 'admin' | 'member' })}>
            <option value="member">Member</option>
            <option value="admin">Admin</option>
          </select>
        </label>
        <button type="submit">Send invitation</button>
      </form>
      {lastInvite?.token && (
        <p className="muted">Accept link: /accept-invitation?token={lastInvite.token}</p>
      )}
    </section>

    {invitations.length > 0 && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">PENDING</p><h2>Invitations</h2></div></div>
        <DataTable
          rows={invitations}
          rowKey={(invitation) => invitation.id}
          columns={[
            { key: 'email', header: 'Email', render: (invitation) => invitation.email },
            { key: 'role', header: 'Role', render: (invitation) => invitation.role },
            { key: 'expires', header: 'Expires', render: (invitation) => new Date(invitation.expires_at).toLocaleString() },
            {
              key: 'actions',
              header: 'Actions',
              render: (invitation) => (
                <button type="button" className="link-button inline danger" onClick={() => void handleRevokeInvite(invitation)}>Revoke</button>
              ),
            },
          ]}
        />
      </section>
    )}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">CREATE</p><h2>Add user directly</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreate() }}>
        <label>Name<input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label>
        <label>Email<input type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required /></label>
        <label>Password<input type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} required minLength={8} /></label>
        <button type="submit">Create user</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">DIRECTORY</p><h2>Users</h2></div>
        <input className="search-input" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search users" aria-label="Search users" />
      </div>
      {loading ? <p className="loading">Loading users…</p> : users.length === 0 ? (
        <EmptyState title="No users found" description="Create a user or adjust your search." />
      ) : (
        <>
          <DataTable
            rows={users}
            rowKey={(user) => user.id}
            columns={[
              { key: 'name', header: 'Name', render: (user) => user.name },
              { key: 'email', header: 'Email', render: (user) => user.email },
              { key: 'status', header: 'Status', render: (user) => (
                <StatusBadge status={user.is_active === false ? 'inactive' : 'active'} />
              ) },
              { key: 'roles', header: 'Roles', render: (user) => user.roles?.map((role) => role.name).join(', ') || 'Member' },
              {
                key: 'actions',
                header: 'Actions',
                render: (user) => (
                  <div className="inline-actions">
                    <button type="button" className="link-button inline" onClick={() => setEditTarget({ ...user })}>Edit</button>
                    <button type="button" className="link-button inline" onClick={() => setAssignTarget(user)}>Assign role</button>
                    {user.id !== currentUser?.id && (
                      <button type="button" className="link-button inline danger" onClick={() => setConfirmRemove(user)}>Remove</button>
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
        <div className="panel-heading"><div><p className="eyebrow">ASSIGN</p><h2>{assignTarget.name}</h2></div></div>
        <label>
          Role
          <select value={selectedRoleId} onChange={(event) => setSelectedRoleId(event.target.value ? Number(event.target.value) : '')}>
            <option value="">Select role</option>
            {roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
          </select>
        </label>
        {assignTarget.roles && assignTarget.roles.length > 0 && (
          <div className="inline-actions">
            {assignTarget.roles.map((role) => (
              <button key={role.id} type="button" className="refresh" onClick={() => void handleUnassign(assignTarget, role)}>
                Unassign {role.name}
              </button>
            ))}
          </div>
        )}
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setAssignTarget(null)}>Cancel</button>
          <button type="button" onClick={() => void handleAssign()}>Assign role</button>
        </div>
      </section>
    )}

    {editTarget && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">EDIT</p><h2>{editTarget.name}</h2></div></div>
        <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleUpdate() }}>
          <label>Name<input value={editTarget.name} onChange={(event) => setEditTarget({ ...editTarget, name: event.target.value })} required /></label>
          <label>Email<input type="email" value={editTarget.email} onChange={(event) => setEditTarget({ ...editTarget, email: event.target.value })} required /></label>
          <label>
            Membership role
            <select
              value={editTarget.membership_role ?? 'member'}
              onChange={(event) => setEditTarget({ ...editTarget, membership_role: event.target.value })}
            >
              <option value="member">Member</option>
              <option value="admin">Admin</option>
            </select>
          </label>
          <label className="checkbox-label">
            <input
              type="checkbox"
              checked={editTarget.is_active !== false}
              onChange={(event) => setEditTarget({ ...editTarget, is_active: event.target.checked })}
              disabled={editTarget.id === currentUser?.id}
            />
            Active in organization
          </label>
          <div className="confirm-actions">
            <button type="button" className="refresh" onClick={() => setEditTarget(null)}>Cancel</button>
            <button type="submit">Save changes</button>
          </div>
        </form>
      </section>
    )}

    {confirmRemove && (
      <section className="panel">
        <div className="panel-heading"><div><p className="eyebrow">CONFIRM</p><h2>Remove {confirmRemove.name}?</h2></div></div>
        <p>This removes the user from the organization. Their global account is preserved.</p>
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setConfirmRemove(null)}>Cancel</button>
          <button type="button" className="danger" onClick={() => void handleRemove()}>Remove user</button>
        </div>
      </section>
    )}
  </>
}
