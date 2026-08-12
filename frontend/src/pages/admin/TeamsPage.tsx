import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { createTeam, getTeams, type TeamSummary } from '../../api'
import { unwrapModuleRows } from '../../api/client'
import { DataTable } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import i18n from '../../i18n/config'

export function TeamsPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can } = usePermissions()
  const [form, setForm] = useState({ name: '', description: '' })
  const [message, setMessage] = useState('')

  const { data, loading, error, reload } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getTeams(token, organizationId ?? undefined)
  }, [token, organizationId])

  if (!can('access.manage')) {
    return <ErrorBanner message={t('teams.noPermission')} />
  }

  const teams = unwrapModuleRows(data ?? []) as TeamSummary[]

  const handleCreate = async () => {
    if (!token) return
    setMessage('')
    try {
      await createTeam(token, form, organizationId ?? undefined)
      setForm({ name: '', description: '' })
      setMessage(t('teams.created'))
      await reload()
    } catch (requestError) {
      setMessage(translateApiError(requestError) || t('teams.createFailed'))
    }
  }

  return <>
    <PageHeader eyebrow={t('common.enterprise')} title={t('teams.title')} description={t('teams.subGroupsDescription')} />

    {error && <ErrorBanner message={error} onRetry={reload} />}
    {message && <p className="notice">{message}</p>}

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.createSection')}</p><h2>{t('teams.newTeam')}</h2></div></div>
      <form className="record-form" onSubmit={(event) => { event.preventDefault(); void handleCreate() }}>
        <label>{t('common.name')}<input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label>
        <label>{t('common.description')}<input value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} /></label>
        <button type="submit">{t('teams.createTeam')}</button>
      </form>
    </section>

    <section className="panel">
      <div className="panel-heading"><div><p className="eyebrow">{t('common.directory')}</p><h2>{t('teams.teamList')}</h2></div></div>
      {loading ? <p className="loading">{t('teams.loadingTeams')}</p> : teams.length === 0 ? (
        <EmptyState title={t('teams.emptyTitle')} description={t('teams.emptyOrganizeDescription')} />
      ) : (
        <DataTable
          rows={teams}
          rowKey={(team) => team.id}
          columns={[
            { key: 'name', header: t('common.name'), render: (team) => <Link to={`/admin/teams/${team.id}`}>{team.name}</Link> },
            { key: 'slug', header: t('common.slug'), render: (team) => team.slug },
            { key: 'members', header: t('teams.members'), render: (team) => team.members_count ?? 0 },
          ]}
        />
      )}
    </section>
  </>
}
