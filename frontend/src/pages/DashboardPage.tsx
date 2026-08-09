import { useEffect, useState } from 'react'
import { getDashboard, getWorkflowSummary, type Dashboard, type WorkflowSummary } from '../api'
import { useAuth } from '../context/AuthContext'
import { Panel } from '../components/AppShell'

const statuses = ['todo', 'in_progress', 'blocked', 'done', 'cancelled']

function formatStatus(status: string) {
  return status.replace('_', ' ')
}

export function DashboardPage() {
  const { token, organizationId } = useAuth()
  const [dashboard, setDashboard] = useState<Dashboard | null>(null)
  const [summary, setSummary] = useState<WorkflowSummary | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const load = async () => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      const [nextDashboard, nextSummary] = await Promise.all([
        getDashboard(token, organizationId ?? undefined),
        getWorkflowSummary(token, organizationId ?? undefined),
      ])
      setDashboard(nextDashboard)
      setSummary(nextSummary)
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to load dashboard.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [token, organizationId])

  if (loading && !dashboard) return <p className="loading">Loading your workspace…</p>
  if (error) return <p className="error banner">{error}</p>
  if (!dashboard) return null

  return <>
    <section className="metrics" aria-label="Workspace metrics">
      <article className="metric violet"><span>Active projects</span><strong>{dashboard.metrics.active_projects}</strong></article>
      <article className="metric blue"><span>Open tasks</span><strong>{dashboard.metrics.open_tasks}</strong></article>
      <article className="metric green"><span>Farms</span><strong>{summary?.farms ?? 0}</strong></article>
      <article className="metric red"><span>Diagnosis cases</span><strong>{summary?.diagnosis_requests ?? 0}</strong></article>
    </section>
    <Panel eyebrow="PLATFORM" title="Agricultural overview">
      <div className="report-grid">
        <div><span>Published courses</span><strong>{summary?.training_courses ?? 0}</strong></div>
        <div><span>Library articles</span><strong>{summary?.library_items ?? 0}</strong></div>
        <div><span>Active enrollments</span><strong>{summary?.active_enrollments ?? 0}</strong></div>
        <div><span>Completed tasks</span><strong>{dashboard.metrics.completed_tasks}</strong></div>
      </div>
    </Panel>
    <Panel eyebrow="DELIVERY" title="Projects">
      <div className="project-grid">
        {dashboard.projects.map((project) => {
          const progress = project.tasks_count ? Math.round(project.completed_tasks_count / project.tasks_count * 100) : 0
          return <article className="project-card" key={project.id}>
            <div><span className="project-code">{project.code}</span><span className="status">{formatStatus(project.status)}</span></div>
            <h3>{project.name}</h3>
            <div className="progress"><span style={{ width: `${progress}%` }} /></div>
            <p>{project.completed_tasks_count} of {project.tasks_count} tasks complete <strong>{progress}%</strong></p>
          </article>
        })}
      </div>
    </Panel>
    <Panel eyebrow="PRIORITIES" title="Recent tasks" action={<span>{dashboard.recent_tasks.length} items</span>}>
      <div className="task-list">
        {dashboard.recent_tasks.map((task) => <article className="task-row" key={task.id}>
          <div className="task-title"><strong>{task.title}</strong><span>{task.project.code} · {task.assignee?.name ?? 'Unassigned'}</span></div>
          <span className={`priority ${task.priority}`}>{task.priority}</span>
          <time>{task.due_at ? new Date(task.due_at).toLocaleDateString() : 'No due date'}</time>
          <select aria-label={`Status for ${task.title}`} value={task.status} disabled>
            {statuses.map((status) => <option key={status} value={status}>{formatStatus(status)}</option>)}
          </select>
        </article>)}
      </div>
    </Panel>
  </>
}

export function useDashboardTitle(token: string, organizationId: number | null) {
  const [title, setTitle] = useState('Dashboard')
  useEffect(() => {
    void getDashboard(token, organizationId ?? undefined)
      .then((dashboard) => setTitle(dashboard.organization.name))
      .catch(() => setTitle('Dashboard'))
  }, [token, organizationId])
  return title
}
