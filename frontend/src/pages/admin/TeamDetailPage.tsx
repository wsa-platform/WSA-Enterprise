import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { addTeamMember, getTeam, getUsers, removeTeamMember, type UserWithRoles } from '../../api'
import { unwrapModuleRows } from '../../api/client'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { ConfirmDialog } from '../../components/ConfirmDialog'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function TeamDetailPage() {
  const { teamId } = useParams()
  const id = Number(teamId)
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [selectedUserId, setSelectedUserId] = useState<number | ''>('')
  const [removeUserId, setRemoveUserId] = useState<number | null>(null)
  const [message, setMessage] = useState('')

  const { data: team, loading, error, reload } = useAsyncData(async () => {
    if (!token || !id) throw new Error('Invalid team.')
    return getTeam(token, id, organizationId ?? undefined)
  }, [token, organizationId, id])

  const { data: usersPayload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getUsers(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message="You do not have permission to manage teams." />
  }

  const users = unwrapModuleRows(usersPayload ?? []) as UserWithRoles[]

  const handleAdd = async () => {
    if (!token || !selectedUserId) return
    setMessage('')
    try {
      await addTeamMember(token, id, Number(selectedUserId), organizationId ?? undefined)
      setSelectedUserId('')
      setMessage('Member added.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to add member.')
    }
  }

  const handleRemove = async () => {
    if (!token || !removeUserId) return
    setMessage('')
    try {
      await removeTeamMember(token, id, removeUserId, organizationId ?? undefined)
      setRemoveUserId(null)
      setMessage('Member removed.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to remove member.')
    }
  }

  if (loading && !team) return <p className="loading">Loading team…</p>
  if (error) return <ErrorBanner message={error} onRetry={reload} />
  if (!team) return null

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title={team.name}
      description={team.description ?? 'Organization team'}
      breadcrumbs={[{ label: 'Dashboard', to: '/' }, { label: 'Teams', to: '/admin/teams' }, { label: team.name }]}
    />

    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">MEMBERS</p><h2>Team members</h2></div></div>
      {team.members.length === 0 ? (
        <EmptyState title="No members" description="Add organization users to this team." />
      ) : (
        <DataTable
          rows={team.members}
          rowKey={(member) => member.id}
          columns={[
            { key: 'name', header: 'Name', render: (member) => member.name },
            { key: 'email', header: 'Email', render: (member) => member.email },
            { key: 'role', header: 'Team role', render: (member) => member.pivot?.role ?? 'member' },
            {
              key: 'actions',
              header: 'Actions',
              render: (member) => (
                <button type="button" className="link-button inline danger-link" onClick={() => setRemoveUserId(member.id)}>Remove</button>
              ),
            },
          ]}
        />
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">ADD</p><h2>Add member</h2></div></div>
      <label>
        Organization user
        <select value={selectedUserId} onChange={(event) => setSelectedUserId(event.target.value ? Number(event.target.value) : '')}>
          <option value="">Select user</option>
          {users.map((user) => <option key={user.id} value={user.id}>{user.name} ({user.email})</option>)}
        </select>
      </label>
      <button type="button" onClick={() => void handleAdd()}>Add member</button>
      <p className="muted"><Link to="/admin/teams">Back to teams</Link></p>
    </section>

    <ConfirmDialog
      open={removeUserId !== null}
      title="Remove team member"
      message="This removes the user from the team. It does not delete the user from the organization."
      confirmLabel="Remove member"
      onCancel={() => setRemoveUserId(null)}
      onConfirm={() => void handleRemove()}
    />
  </>
}
