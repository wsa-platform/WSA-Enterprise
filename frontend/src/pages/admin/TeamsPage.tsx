import { useState } from 'react'
import { Link } from 'react-router-dom'
import { createTeam, getTeams, type TeamSummary } from '../../api'
import { unwrapModuleRows } from '../../api/client'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'

export function TeamsPage() {
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [form, setForm] = useState({ name: '', description: '' })
  const [message, setMessage] = useState('')

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getTeams(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message="You do not have permission to manage teams." />
  }

  const teams = unwrapModuleRows(data ?? []) as TeamSummary[]

  const handleCreate = async () => {
    if (!token) return
    setMessage('')
    try {
      await createTeam(token, form, organizationId ?? undefined)
      setForm({ name: '', description: '' })
      setMessage('Team created successfully.')
      await reload()
    } catch (requestError) {
      setMessage(requestError instanceof Error ? requestError.message : 'Unable to create team.')
    }
  }

  return <>
    <PageHeader eyebrow="ENTERPRISE" title="Teams" description="Teams are sub-groups within your organization." />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">CREATE</p><h2>New team</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreate() }}>
        <label>Name<input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label>
        <label>Description<input value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} /></label>
        <button type="submit">Create team</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">DIRECTORY</p><h2>Team list</h2></div></div>
      {loading ? <p className="loading">Loading teams…</p> : teams.length === 0 ? (
        <EmptyState title="No teams yet" description="Create a team to organize members." />
      ) : (
        <DataTable
          rows={teams}
          rowKey={(team) => team.id}
          columns={[
            { key: 'name', header: 'Name', render: (team) => <Link to={`/admin/teams/${team.id}`}>{team.name}</Link> },
            { key: 'slug', header: 'Slug', render: (team) => team.slug },
            { key: 'members', header: 'Members', render: (team) => team.members_count ?? 0 },
          ]}
        />
      )}
    </section>
  </>
}
