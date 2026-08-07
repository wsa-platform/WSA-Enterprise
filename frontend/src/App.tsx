import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import {
  getDashboard,
  login,
  logout,
  updateTaskStatus,
} from './api'
import type { Dashboard, User } from './api'
import './App.css'

const statuses = ['todo', 'in_progress', 'blocked', 'done', 'cancelled']

function formatStatus(status: string) {
  return status.replace('_', ' ')
}

function App() {
  const [token, setToken] = useState(() => localStorage.getItem('wsa_token') ?? '')
  const [user, setUser] = useState<User | null>(() => {
    const stored = localStorage.getItem('wsa_user')
    return stored ? JSON.parse(stored) as User : null
  })
  const [dashboard, setDashboard] = useState<Dashboard | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(Boolean(token))
  const [email, setEmail] = useState('admin@wsa.test')
  const [password, setPassword] = useState('password')

  const loadDashboard = async () => {
    if (!token) return
    setLoading(true)
    setError('')
    try {
      setDashboard(await getDashboard(token))
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to load dashboard.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void loadDashboard()
  }, [token])

  const handleLogin = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setLoading(true)
    setError('')
    try {
      const authenticated = await login(email, password)
      localStorage.setItem('wsa_token', authenticated.token)
      localStorage.setItem('wsa_user', JSON.stringify(authenticated.user))
      setUser(authenticated.user)
      setToken(authenticated.token)
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Sign in failed.')
      setLoading(false)
    }
  }

  const handleLogout = async () => {
    if (token) await logout(token).catch(() => undefined)
    localStorage.removeItem('wsa_token')
    localStorage.removeItem('wsa_user')
    setToken('')
    setUser(null)
    setDashboard(null)
  }

  const changeTaskStatus = async (taskId: number, status: string) => {
    try {
      await updateTaskStatus(token, taskId, status)
      await loadDashboard()
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Unable to update task.')
    }
  }

  if (!token || !user) {
    return (
      <main className="login-shell">
        <section className="login-card">
          <p className="eyebrow">WSA ENTERPRISE</p>
          <h1>Operations, in focus.</h1>
          <p className="muted">Sign in to monitor work across your organization.</p>
          <form onSubmit={handleLogin}>
            <label>Email<input value={email} onChange={(event) => setEmail(event.target.value)} type="email" required /></label>
            <label>Password<input value={password} onChange={(event) => setPassword(event.target.value)} type="password" required /></label>
            {error && <p className="error">{error}</p>}
            <button disabled={loading} type="submit">{loading ? 'Signing in…' : 'Sign in'}</button>
          </form>
          <p className="hint">Demo: admin@wsa.test / password</p>
        </section>
      </main>
    )
  }

  return (
    <div className="app-shell">
      <aside>
        <div className="brand"><span>W</span> WSA</div>
        <nav>
          <a className="active" href="#overview">Overview</a>
          <a href="#projects">Projects</a>
          <a href="#tasks">Tasks</a>
        </nav>
        <div className="account">
          <strong>{user.name}</strong>
          <span>{dashboard?.organization.name ?? 'Loading workspace…'}</span>
          <button className="link-button" onClick={handleLogout}>Sign out</button>
        </div>
      </aside>
      <main className="dashboard">
        <header>
          <div>
            <p className="eyebrow">WORKSPACE</p>
            <h1 id="overview">{dashboard?.organization.name ?? 'Dashboard'}</h1>
          </div>
          <button className="refresh" onClick={() => void loadDashboard()} disabled={loading}>Refresh</button>
        </header>
        {error && <p className="error banner">{error}</p>}
        {loading && !dashboard ? <p className="loading">Loading your workspace…</p> : dashboard && (
          <>
            <section className="metrics" aria-label="Workspace metrics">
              <Metric label="Active projects" value={dashboard.metrics.active_projects} tone="violet" />
              <Metric label="Open tasks" value={dashboard.metrics.open_tasks} tone="blue" />
              <Metric label="Completed" value={dashboard.metrics.completed_tasks} tone="green" />
              <Metric label="Overdue" value={dashboard.metrics.overdue_tasks} tone="red" />
            </section>
            <section className="panel" id="projects">
              <div className="panel-heading"><div><p className="eyebrow">DELIVERY</p><h2>Projects</h2></div></div>
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
            </section>
            <section className="panel" id="tasks">
              <div className="panel-heading"><div><p className="eyebrow">PRIORITIES</p><h2>Recent tasks</h2></div><span>{dashboard.recent_tasks.length} items</span></div>
              <div className="task-list">
                {dashboard.recent_tasks.map((task) => <article className="task-row" key={task.id}>
                  <div className="task-title"><strong>{task.title}</strong><span>{task.project.code} · {task.assignee?.name ?? 'Unassigned'}</span></div>
                  <span className={`priority ${task.priority}`}>{task.priority}</span>
                  <time>{task.due_at ? new Date(task.due_at).toLocaleDateString() : 'No due date'}</time>
                  <select aria-label={`Status for ${task.title}`} value={task.status} onChange={(event) => void changeTaskStatus(task.id, event.target.value)}>
                    {statuses.map((status) => <option key={status} value={status}>{formatStatus(status)}</option>)}
                  </select>
                </article>)}
              </div>
            </section>
          </>
        )}
      </main>
    </div>
  )
}

function Metric({ label, value, tone }: { label: string; value: number; tone: string }) {
  return <article className={`metric ${tone}`}><span>{label}</span><strong>{value}</strong></article>
}

export default App
