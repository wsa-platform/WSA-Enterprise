import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { getAccessSummary, getDashboard, getWorkflowSummary, type Dashboard } from '../api'
import { Panel } from '../components/AppShell'
import { PageHeader } from '../components/PageHeader'
import { ErrorBanner, EmptyState, SkeletonGrid, StatusBadge } from '../components/UiPrimitives'
import { usePermissions } from '../context/PermissionContext'
import { useAuth } from '../context/AuthContext'
import { useAsyncData } from '../hooks/useAsyncData'
import i18n from '../i18n/config'

export function DashboardPage() {
  const { t } = useTranslation()
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()

  const { data: dashboard, loading: dashboardLoading, error: dashboardError, reload: reloadDashboard } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getDashboard(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: summary, loading: summaryLoading, error: summaryError, reload: reloadSummary } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getWorkflowSummary(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: access, loading: accessLoading, error: accessError, reload: reloadAccess } = useAsyncData(async () => {
    if (!token) throw new Error(i18n.t('errors.notAuthenticated'))
    return getAccessSummary(token, organizationId ?? undefined)
  }, [token, organizationId])

  const loading = dashboardLoading || summaryLoading || accessLoading
  const error = dashboardError || summaryError || accessError

  const reloadAll = () => {
    void reloadDashboard()
    void reloadSummary()
    void reloadAccess()
  }

  if (loading && !dashboard) return <SkeletonGrid count={4} />
  if (error) return <ErrorBanner message={error} onRetry={reloadAll} />
  if (!dashboard || !summary || !access) {
    return <EmptyState title={t('dashboard.unavailable')} description={t('dashboard.notAuthenticated')} />
  }

  return <>
    <PageHeader
      eyebrow={t('common.enterprise')}
      title={t('dashboard.title')}
      description={t('dashboard.signedInAs', { name: context?.user.name ?? dashboard.organization.name })}
      actions={<button type="button" className="refresh" onClick={reloadAll}>{t('common.refresh')}</button>}
    />

    <section className="metrics" aria-label={t('dashboard.metricsLabel')}>
      <Metric label={t('dashboard.activeProjects')} value={dashboard.metrics.active_projects} tone="violet" />
      <Metric label={t('dashboard.openTasks')} value={dashboard.metrics.open_tasks} tone="blue" />
      <Metric label={t('dashboard.orgUsers')} value={access.users_count} tone="green" />
      <Metric label={t('dashboard.aiRequestsToday')} value={access.ai_requests?.today ?? 0} tone="red" />
    </section>

    <div className="dashboard-grid">
      <Panel eyebrow={t('common.enterprise')} title={t('dashboard.orgOverview')}>
        <div className="detail-grid">
          <div><span>{t('dashboard.organization')}</span><strong>{dashboard.organization.name}</strong></div>
          <div><span>{t('dashboard.yourRole')}</span><strong>{context?.roles[0]?.name ?? context?.membership_role ?? t('common.member')}</strong></div>
          <div><span>{t('dashboard.teams')}</span><strong>{access.teams_count ?? '—'}</strong></div>
          <div><span>{t('dashboard.rolesConfigured')}</span><strong>{access.roles_count ?? '—'}</strong></div>
        </div>
      </Panel>

      {can('ai.use') && access.ai_requests && (
        <Panel eyebrow="AI" title={t('dashboard.aiOverview')} action={<Link to="/ai/workspace">{t('common.openWorkspace')}</Link>}>
          <div className="report-grid">
            <div><span>{t('dashboard.pending')}</span><strong>{access.ai_requests.pending}</strong></div>
            <div><span>{t('dashboard.processing')}</span><strong>{access.ai_requests.processing}</strong></div>
            <div><span>{t('dashboard.completed')}</span><strong>{access.ai_requests.completed}</strong></div>
            <div><span>{t('dashboard.failed')}</span><strong>{access.ai_requests.failed}</strong></div>
          </div>
          {access.quota && (
            <p className="quota-line">
              {t('dashboard.quota')}: {access.quota.enabled
                ? t('dashboard.quotaUsed', { used: access.quota.used, limit: access.quota.limit ?? '∞', remaining: access.quota.remaining ?? 0 })
                : t('dashboard.quotaUnlimited')}
            </p>
          )}
        </Panel>
      )}

      <Panel eyebrow={t('common.platform')} title={t('dashboard.agriOverview')}>
        <div className="report-grid">
          <div><span>{t('dashboard.farms')}</span><strong>{summary.farms}</strong></div>
          <div><span>{t('dashboard.diagnosisCases')}</span><strong>{summary.diagnosis_requests}</strong></div>
          <div><span>{t('dashboard.publishedCourses')}</span><strong>{summary.training_courses}</strong></div>
          <div><span>{t('dashboard.libraryArticles')}</span><strong>{summary.library_items}</strong></div>
        </div>
      </Panel>

      <Panel eyebrow={t('common.system')} title={t('dashboard.systemStatus')}>
        <div className="detail-grid">
          <div><span>{t('dashboard.api')}</span><strong>{access.system.api}</strong></div>
          <div><span>{t('dashboard.queueDriver')}</span><strong>{access.system.queue}</strong></div>
          <div><span>{t('dashboard.auditEvents24h')}</span><strong>{access.audit_events_24h ?? '—'}</strong></div>
          <div><span>{t('dashboard.completedTasks')}</span><strong>{dashboard.metrics.completed_tasks}</strong></div>
        </div>
      </Panel>
    </div>

    {can('access.manage') && access.recent_audit && (
      <Panel eyebrow={t('common.activity')} title={t('dashboard.recentAudit')}>
        <ActivityList
          rows={access.recent_audit.map((entry) => ({
            id: entry.id,
            title: entry.action,
            subtitle: entry.user?.name ?? t('common.system'),
            meta: new Date(entry.created_at).toLocaleString(),
          }))}
        />
      </Panel>
    )}

    {can('ai.use') && access.recent_ai && (
      <Panel eyebrow={t('common.activity')} title={t('dashboard.recentAi')}>
        <ActivityList
          rows={access.recent_ai.map((entry) => ({
            id: entry.id,
            title: entry.request_type,
            subtitle: entry.status,
            meta: new Date(entry.created_at).toLocaleString(),
            link: `/ai/requests/${entry.id}`,
          }))}
        />
      </Panel>
    )}

    <Panel eyebrow={t('common.quickActions')} title={t('dashboard.quickActions')}>
      <div className="quick-actions">
        {can('access.manage') && <Link to="/admin/users">{t('dashboard.createUser')}</Link>}
        {can('access.manage') && <Link to="/admin/teams">{t('dashboard.createTeam')}</Link>}
        {can('ai.use') && <Link to="/ai/workspace">{t('dashboard.startAiRequest')}</Link>}
        {can('access.manage') && <Link to="/admin/audit">{t('dashboard.viewAuditLogs')}</Link>}
        {can('platform.view') && <Link to="/organization">{t('dashboard.orgSettings')}</Link>}
        <Link to="/account">{t('dashboard.openAccount')}</Link>
        {(can('market.view') || can('market.create') || can('market.manage_own')) && (
          <Link to="/account/products">{t('dashboard.myProducts')}</Link>
        )}
        {can('market.create') && (
          <Link to="/account/products/new">{t('dashboard.addProduct')}</Link>
        )}
        <Link to="/market">{t('dashboard.productMarket')}</Link>
      </div>
    </Panel>

    <ProjectsPanel dashboard={dashboard} />
  </>
}

function Metric({ label, value, tone }: { label: string; value: number; tone: string }) {
  return <article className={`metric ${tone}`}><span>{label}</span><strong>{value}</strong></article>
}

function ActivityList({ rows }: { rows: Array<{ id: number; title: string; subtitle: string; meta: string; link?: string }> }) {
  const { t } = useTranslation()
  if (rows.length === 0) return <p className="muted">{t('dashboard.noRecentActivity')}</p>
  return (
    <div className="activity-list">
      {rows.map((row) => (
        <article className="activity-row" key={row.id}>
          <div>
            <strong>{row.link ? <Link to={row.link}>{row.title}</Link> : row.title}</strong>
            <span>{row.subtitle}</span>
          </div>
          <StatusBadge status={row.subtitle.includes('.') ? row.subtitle.split('.').pop() ?? row.subtitle : row.subtitle} />
          <time>{row.meta}</time>
        </article>
      ))}
    </div>
  )
}

function ProjectsPanel({ dashboard }: { dashboard: Dashboard }) {
  const { t } = useTranslation()
  return (
    <Panel eyebrow={t('common.delivery')} title={t('dashboard.projects')}>
      <div className="project-grid">
        {dashboard.projects.map((project) => {
          const progress = project.tasks_count ? Math.round(project.completed_tasks_count / project.tasks_count * 100) : 0
          return (
            <article className="project-card" key={project.id}>
              <div><span className="project-code">{project.code}</span><span className="status">{project.status.replace('_', ' ')}</span></div>
              <h3>{project.name}</h3>
              <div className="progress"><span style={{ width: `${progress}%` }} /></div>
              <p>{t('dashboard.tasksComplete', { completed: project.completed_tasks_count, total: project.tasks_count })} <strong>{progress}%</strong></p>
            </article>
          )
        })}
      </div>
    </Panel>
  )
}

export function useDashboardTitle(token: string, organizationId: number | null) {
  const { data } = useAsyncData(async () => getDashboard(token, organizationId ?? undefined), [token, organizationId])
  return data?.organization.name ?? i18n.t('nav.dashboard')
}
