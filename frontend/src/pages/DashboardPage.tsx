import { Link } from 'react-router-dom'
import { getAccessSummary, getDashboard, getWorkflowSummary, type Dashboard } from '../api'
import { Panel } from '../components/AppShell'
import { PageHeader } from '../components/PageHeader'
import { ErrorBanner, SkeletonGrid, StatusBadge } from '../components/UiPrimitives'
import { usePermissions } from '../context/PermissionContext'
import { useAuth } from '../context/AuthContext'
import { useAsyncData } from '../hooks/useAsyncData'

export function DashboardPage() {
  const { token, organizationId } = useAuth()
  const { can, context } = usePermissions()

  const { data: dashboard, loading: dashboardLoading, error: dashboardError, reload: reloadDashboard } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getDashboard(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: summary, loading: summaryLoading, error: summaryError, reload: reloadSummary } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
    return getWorkflowSummary(token, organizationId ?? undefined)
  }, [token, organizationId])

  const { data: access, loading: accessLoading, error: accessError, reload: reloadAccess } = useAsyncData(async () => {
    if (!token) throw new Error('Not authenticated.')
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
  if (!dashboard || !summary || !access) return null

  return <>
    <PageHeader
      eyebrow="ENTERPRISE"
      title="Dashboard"
      description={`Signed in as ${context?.user.name ?? dashboard.organization.name}`}
      actions={<button type="button" className="refresh" onClick={reloadAll}>Refresh</button>}
    />

    <section className="metrics" aria-label="Workspace metrics">
      <Metric label="Active projects" value={dashboard.metrics.active_projects} tone="violet" />
      <Metric label="Open tasks" value={dashboard.metrics.open_tasks} tone="blue" />
      <Metric label="Organization users" value={access.users_count} tone="green" />
      <Metric label="AI requests today" value={access.ai_requests?.today ?? 0} tone="red" />
    </section>

    <div className="dashboard-grid">
      <Panel eyebrow="ORGANIZATION" title="Overview">
        <div className="detail-grid">
          <div><span>Organization</span><strong>{dashboard.organization.name}</strong></div>
          <div><span>Your role</span><strong>{context?.roles[0]?.name ?? context?.membership_role ?? 'Member'}</strong></div>
          <div><span>Teams</span><strong>{access.teams_count ?? '—'}</strong></div>
          <div><span>Roles configured</span><strong>{access.roles_count ?? '—'}</strong></div>
        </div>
      </Panel>

      {can('ai.use') && access.ai_requests && (
        <Panel eyebrow="AI" title="AI overview" action={<Link to="/ai/workspace">Open workspace</Link>}>
          <div className="report-grid">
            <div><span>Pending</span><strong>{access.ai_requests.pending}</strong></div>
            <div><span>Processing</span><strong>{access.ai_requests.processing}</strong></div>
            <div><span>Completed</span><strong>{access.ai_requests.completed}</strong></div>
            <div><span>Failed</span><strong>{access.ai_requests.failed}</strong></div>
          </div>
          {access.quota && (
            <p className="quota-line">
              Quota: {access.quota.enabled
                ? `${access.quota.used} / ${access.quota.limit ?? '∞'} used (${access.quota.remaining ?? 0} remaining)`
                : 'Unlimited (quota disabled)'}
            </p>
          )}
        </Panel>
      )}

      <Panel eyebrow="PLATFORM" title="Agricultural overview">
        <div className="report-grid">
          <div><span>Farms</span><strong>{summary.farms}</strong></div>
          <div><span>Diagnosis cases</span><strong>{summary.diagnosis_requests}</strong></div>
          <div><span>Published courses</span><strong>{summary.training_courses}</strong></div>
          <div><span>Library articles</span><strong>{summary.library_items}</strong></div>
        </div>
      </Panel>

      <Panel eyebrow="SYSTEM" title="System status">
        <div className="detail-grid">
          <div><span>API</span><strong>{access.system.api}</strong></div>
          <div><span>Queue driver</span><strong>{access.system.queue}</strong></div>
          <div><span>Audit events (24h)</span><strong>{access.audit_events_24h ?? '—'}</strong></div>
          <div><span>Completed tasks</span><strong>{dashboard.metrics.completed_tasks}</strong></div>
        </div>
      </Panel>
    </div>

    {can('access.manage') && access.recent_audit && (
      <Panel eyebrow="ACTIVITY" title="Recent audit events">
        <ActivityList
          rows={access.recent_audit.map((entry) => ({
            id: entry.id,
            title: entry.action,
            subtitle: entry.user?.name ?? 'System',
            meta: new Date(entry.created_at).toLocaleString(),
          }))}
        />
      </Panel>
    )}

    {can('ai.use') && access.recent_ai && (
      <Panel eyebrow="ACTIVITY" title="Recent AI requests">
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

    <Panel eyebrow="QUICK ACTIONS" title="Shortcuts">
      <div className="quick-actions">
        {can('access.manage') && <Link to="/admin/users">Create user</Link>}
        {can('access.manage') && <Link to="/admin/teams">Create team</Link>}
        {can('ai.use') && <Link to="/ai/workspace">Start AI request</Link>}
        {can('access.manage') && <Link to="/admin/audit">View audit logs</Link>}
        {can('platform.view') && <Link to="/organization">Organization settings</Link>}
      </div>
    </Panel>

    <ProjectsPanel dashboard={dashboard} />
  </>
}

function Metric({ label, value, tone }: { label: string; value: number; tone: string }) {
  return <article className={`metric ${tone}`}><span>{label}</span><strong>{value}</strong></article>
}

function ActivityList({ rows }: { rows: Array<{ id: number; title: string; subtitle: string; meta: string; link?: string }> }) {
  if (rows.length === 0) return <p className="muted">No recent activity.</p>
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
  return (
    <Panel eyebrow="DELIVERY" title="Projects">
      <div className="project-grid">
        {dashboard.projects.map((project) => {
          const progress = project.tasks_count ? Math.round(project.completed_tasks_count / project.tasks_count * 100) : 0
          return (
            <article className="project-card" key={project.id}>
              <div><span className="project-code">{project.code}</span><span className="status">{project.status.replace('_', ' ')}</span></div>
              <h3>{project.name}</h3>
              <div className="progress"><span style={{ width: `${progress}%` }} /></div>
              <p>{project.completed_tasks_count} of {project.tasks_count} tasks complete <strong>{progress}%</strong></p>
            </article>
          )
        })}
      </div>
    </Panel>
  )
}

export function useDashboardTitle(token: string, organizationId: number | null) {
  const { data } = useAsyncData(async () => getDashboard(token, organizationId ?? undefined), [token, organizationId])
  return data?.organization.name ?? 'Dashboard'
}
