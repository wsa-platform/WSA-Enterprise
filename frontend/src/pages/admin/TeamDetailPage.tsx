import { useState } from 'react'
import { useTranslation } from 'react-i18next'
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
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'

export function TeamDetailPage() {
  const { t } = useTranslation()
  const { teamId } = useParams()
  const id = Number(teamId)
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [selectedUserId, setSelectedUserId] = useState<number | ''>('')
  const [removeUserId, setRemoveUserId] = useState<number | null>(null)
  const [message, setMessage] = useState('')

  const { data: team, loading, error, reload } = useAsyncData(async () => {
    if (!token || !id) throw new Error(i18n.t('teams.invalidTeam'))
    return getTeam(token, id, organizationId ?? undefined)
  }, [token, organizationId, id])

  const { data: usersPayload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getUsers(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message={t('teams.noPermission')} />
  }

  const users = unwrapModuleRows(usersPayload ?? []) as UserWithRoles[]

  const handleAdd = async () => {
    if (!token || !selectedUserId) return
    setMessage('')
    try {
      await addTeamMember(token, id, Number(selectedUserId), organizationId ?? undefined)
      setSelectedUserId('')
      setMessage(t('teams.memberAdded'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('teams.addMemberFailed'))
    }
  }

  const handleRemove = async () => {
    if (!token || !removeUserId) return
    setMessage('')
    try {
      await removeTeamMember(token, id, removeUserId, organizationId ?? undefined)
      setRemoveUserId(null)
      setMessage(t('teams.memberRemoved'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('teams.removeMemberFailed'))
    }
  }

  if (loading && !team) return <p className="loading">{t('teams.loadingTeam')}</p>
  if (error) return <ErrorBanner message={error} onRetry={reload} />
  if (!team) return null

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={team.name}
      description={team.description ?? t('teams.orgTeam')}
      breadcrumbs={[{ label: t('nav.dashboard'), to: '/' }, { label: t('nav.teams'), to: '/admin/teams' }, { label: team.name }]}
    />

    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.members')}</p><h2>{t('teams.teamMembers')}</h2></div></div>
      {team.members.length === 0 ? (
        <EmptyState title={t('teams.noMembersTitle')} description={t('teams.noMembersDescription')} />
      ) : (
        <DataTable
          rows={team.members}
          rowKey={(member) => member.id}
          columns={[
            { key: 'name', header: t('common.name'), render: (member) => member.name },
            { key: 'email', header: t('common.email'), render: (member) => member.email },
            { key: 'role', header: t('teams.teamRole'), render: (member) => member.pivot?.role ?? t('common.member') },
            {
              key: 'actions',
              header: t('common.actions'),
              render: (member) => (
                <button type="button" className="link-button inline danger-link" onClick={() => setRemoveUserId(member.id)}>{t('common.remove')}</button>
              ),
            },
          ]}
        />
      )}
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.add')}</p><h2>{t('teams.addMember')}</h2></div></div>
      <label>
        {t('teams.orgUser')}
        <select value={selectedUserId} onChange={(event) => setSelectedUserId(event.target.value ? Number(event.target.value) : '')}>
          <option value="">{t('teams.selectUser')}</option>
          {users.map((user) => <option key={user.id} value={user.id}>{user.name} ({user.email})</option>)}
        </select>
      </label>
      <button type="button" onClick={() => void handleAdd()}>{t('teams.addMember')}</button>
      <p className="muted"><Link to="/admin/teams">{t('teams.backToTeams')}</Link></p>
    </section>

    <ConfirmDialog
      open={removeUserId !== null}
      title={t('teams.removeTeamMember')}
      message={t('teams.removeTeamMemberMessage')}
      confirmLabel={t('teams.removeMember')}
      onCancel={() => setRemoveUserId(null)}
      onConfirm={() => void handleRemove()}
    />
  </>
}
