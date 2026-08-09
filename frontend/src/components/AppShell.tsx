import type { ReactNode } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { logout } from '../api'

const navItems = [
  { to: '/', label: 'Dashboard', end: true },
  { to: '/farms', label: 'Farms' },
  { to: '/crops', label: 'Crops' },
  { to: '/soil', label: 'Soil' },
  { to: '/diagnosis', label: 'Diagnosis' },
  { to: '/training', label: 'Training' },
  { to: '/library', label: 'Library' },
  { to: '/ai', label: 'AI Services' },
]

export function AppShell({ workspaceName, onRefresh }: { workspaceName: string; onRefresh?: () => void }) {
  const { user, token, clearSession } = useAuth()

  const handleLogout = async () => {
    if (token) await logout(token).catch(() => undefined)
    clearSession()
  }

  return (
    <div className="app-shell">
      <aside>
        <div className="brand"><span>W</span> WSA</div>
        <nav>
          {navItems.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.end} className={({ isActive }) => isActive ? 'active' : undefined}>
              {item.label}
            </NavLink>
          ))}
        </nav>
        <div className="account">
          <strong>{user?.name}</strong>
          <span>{workspaceName}</span>
          <button className="link-button" type="button" onClick={() => void handleLogout()}>Sign out</button>
        </div>
      </aside>
      <main className="dashboard">
        <header>
          <div>
            <p className="eyebrow">WORKSPACE</p>
            <h1>{workspaceName}</h1>
          </div>
          {onRefresh && <button className="refresh" type="button" onClick={onRefresh}>Refresh</button>}
        </header>
        <Outlet />
      </main>
    </div>
  )
}

export function Panel({ eyebrow, title, children, action }: { eyebrow: string; title: string; children: ReactNode; action?: ReactNode }) {
  return (
    <section className="panel">
      <div className="panel-heading">
        <div><p className="eyebrow">{eyebrow}</p><h2>{title}</h2></div>
        {action}
      </div>
      {children}
    </section>
  )
}

export function RecordList({ rows, emptyLabel = 'No records found.' }: { rows: unknown[]; emptyLabel?: string }) {
  if (rows.length === 0) return <p className="muted">{emptyLabel}</p>

  return (
    <div className="module-results">
      {rows.slice(0, 12).map((row, index) => (
        <article className="record-card" key={index}>{renderRecordCard(row)}</article>
      ))}
    </div>
  )
}

export function ModuleTabs({ tabs, activePath, onSelect }: { tabs: Array<{ label: string; path: string }>; activePath: string; onSelect: (path: string) => void }) {
  return (
    <div className="module-tabs">
      {tabs.map((tab) => (
        <button key={tab.path} type="button" className={activePath === tab.path ? 'selected' : ''} onClick={() => onSelect(tab.path)}>
          {tab.label}
        </button>
      ))}
    </div>
  )
}

function renderRecordCard(row: unknown) {
  if (!row || typeof row !== 'object') return <pre>{JSON.stringify(row, null, 2)}</pre>
  const record = row as Record<string, unknown>
  const title = String(record.title_ar ?? record.title ?? record.name ?? record.reference ?? record.code ?? 'Record')
  const subtitle = String(record.summary_ar ?? record.summary ?? record.description ?? record.notes ?? record.status ?? '')
  const meta = [record.code, record.status, record.locale, record.provider, record.confidence_score].filter(Boolean).map(String).join(' · ')
  return <>
    <strong dir="auto">{title}</strong>
    {subtitle && <p dir="auto">{subtitle}</p>}
    {meta && <span>{meta}</span>}
  </>
}
