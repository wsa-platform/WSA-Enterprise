import { useMemo, useState } from 'react'
import { assignRole, createUser, getRoles, getUsers, unwrapModuleRows, type Role, type UserWithRoles } from '../../api'
import { DataTable, PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function UsersPage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [form, setForm] = useState({ name: '', email: '', password: '' })
  const [message, setMessage] = useState('')
  const [assignTarget, setAssignTarget] = useState<UserWithRoles | null>(null)
  const [selectedRoleId, setSelectedRoleId] = useState<number | ''>('')

  const { data: usersPayload, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getUsers(token, organizationId ?? undefined)
  }, [token, organizationId, page])

  const { data: rolesPayload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getRoles(token, organizationId ?? undefined)
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

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="User management"
      description="Manage organization members, roles, and access."
    />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">CREATE</p><h2>Add user</h2></div></div>
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
              { key: 'roles', header: 'Roles', render: (user) => user.roles?.map((role) => role.name).join(', ') || 'Member' },
              {
                key: 'actions',
                header: 'Actions',
                render: (user) => (
                  <button type="button" className="link-button inline" onClick={() => setAssignTarget(user)}>Assign role</button>
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
        <div className="confirm-actions">
          <button type="button" className="refresh" onClick={() => setAssignTarget(null)}>Cancel</button>
          <button type="button" onClick={() => void handleAssign()}>Assign role</button>
        </div>
      </section>
    )}
  </>
}
