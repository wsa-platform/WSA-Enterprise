import { useState, type ReactNode } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { logout } from '../api'
import { OrgSwitcher } from './OrgSwitcher'
import { Breadcrumbs, type BreadcrumbItem } from './PageHeader'

type NavItem = {
  to: string
  label: string
  end?: boolean
  permission?: string
  anyPermission?: string[]
}

const navSections: Array<{ title: string; items: NavItem[] }> = [
  {
    title: 'Overview',
    items: [
      { to: '/', label: 'Dashboard', end: true, permission: 'platform.view' },
      { to: '/notifications', label: 'Notifications', permission: 'platform.view' },
    ],
  },
  {
    title: 'Enterprise',
    items: [
      { to: '/organization', label: 'Organization', permission: 'platform.view' },
      { to: '/billing', label: 'Billing', permission: 'billing.view' },
      { to: '/admin/users', label: 'Users', permission: 'access.manage' },
      { to: '/admin/teams', label: 'Teams', permission: 'access.manage' },
      { to: '/admin/roles', label: 'Roles & Permissions', permission: 'access.manage' },
      { to: '/admin/audit', label: 'Audit Logs', permission: 'access.manage' },
    ],
  },
  {
    title: 'AI',
    items: [
      { to: '/ai/workspace', label: 'AI Workspace', permission: 'ai.use' },
    ],
  },
  {
    title: 'Modules',
    items: [
      { to: '/farms', label: 'Farms', permission: 'farm.view' },
      { to: '/crops', label: 'Crops', permission: 'crop.view' },
      { to: '/soil', label: 'Soil', permission: 'soil.view' },
      { to: '/diagnosis', label: 'Diagnosis', permission: 'diagnosis.view' },
      { to: '/training', label: 'Training', permission: 'training.view' },
      { to: '/library', label: 'Library', permission: 'library.view' },
      { to: '/business', label: 'Business', permission: 'business.view' },
    ],
  },
  {
    title: 'Account',
    items: [
      { to: '/settings', label: 'Settings', permission: 'platform.view' },
    ],
  },
]

function useVisibleNav() {
  const { can, loading } = usePermissions()

  if (loading) {
    return navSections.map((section) => ({ ...section, items: section.items.filter((item) => !item.permission) }))
  }

  return navSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => {
        if (item.anyPermission?.some((permission) => can(permission))) return true
        if (!item.permission) return true
        return can(item.permission)
      }),
    }))
    .filter((section) => section.items.length > 0)
}

function breadcrumbItems(pathname: string): BreadcrumbItem[] {
  const map: Record<string, BreadcrumbItem[]> = {
    '/': [{ label: 'Dashboard' }],
    '/organization': [{ label: 'Dashboard', to: '/' }, { label: 'Organization' }],
    '/billing': [{ label: 'Dashboard', to: '/' }, { label: 'Billing' }],
    '/admin/users': [{ label: 'Dashboard', to: '/' }, { label: 'Users' }],
    '/admin/teams': [{ label: 'Dashboard', to: '/' }, { label: 'Teams' }],
    '/admin/roles': [{ label: 'Dashboard', to: '/' }, { label: 'Roles & Permissions' }],
    '/admin/audit': [{ label: 'Dashboard', to: '/' }, { label: 'Audit Logs' }],
    '/ai/workspace': [{ label: 'Dashboard', to: '/' }, { label: 'AI Workspace' }],
    '/notifications': [{ label: 'Dashboard', to: '/' }, { label: 'Notifications' }],
    '/settings': [{ label: 'Dashboard', to: '/' }, { label: 'Settings' }],
  }

  if (pathname.startsWith('/ai/requests/')) {
    return [{ label: 'Dashboard', to: '/' }, { label: 'AI Workspace', to: '/ai/workspace' }, { label: 'Request detail' }]
  }
  if (pathname.startsWith('/admin/teams/')) {
    return [{ label: 'Dashboard', to: '/' }, { label: 'Teams', to: '/admin/teams' }, { label: 'Team detail' }]
  }

  return map[pathname] ?? [{ label: 'Dashboard', to: '/' }, { label: pathname.replace('/', '') || 'Page' }]
}

export function AppShell({
  workspaceName,
  onRefresh,
}: {
  workspaceName: string
  onRefresh?: () => void
}) {
  const { user, token, clearSession } = useAuth()
  const { context, loading: permissionsLoading } = usePermissions()
  const location = useLocation()
  const [mobileOpen, setMobileOpen] = useState(false)
  const sections = useVisibleNav()
  const roleLabel = context?.roles[0]?.name ?? context?.membership_role ?? 'Member'

  const handleLogout = async () => {
    if (token) await logout(token).catch(() => undefined)
    clearSession()
  }

  return (
    <div className="app-shell">
      <aside className={mobileOpen ? 'mobile-open' : undefined}>
        <div className="brand"><span>W</span> WSA</div>
        <nav aria-label="Primary">
          {sections.map((section) => (
            <div className="nav-section" key={section.title}>
              <p className="nav-section-title">{section.title}</p>
              {section.items.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.end}
                  className={({ isActive }) => isActive ? 'active' : undefined}
                  onClick={() => setMobileOpen(false)}
                >
                  {item.label}
                </NavLink>
              ))}
            </div>
          ))}
        </nav>
        <div className="account">
          <strong>{user?.name}</strong>
          <span>{workspaceName}</span>
          <span className="role-chip">{permissionsLoading ? 'Loading role…' : roleLabel}</span>
          <button className="link-button" type="button" onClick={() => void handleLogout()}>Sign out</button>
        </div>
      </aside>
      <main className="dashboard">
        <header className="shell-header">
          <div>
            <Breadcrumbs items={breadcrumbItems(location.pathname)} />
            <p className="eyebrow">WORKSPACE</p>
            <h1>{workspaceName}</h1>
          </div>
          <div className="header-actions">
            <button type="button" className="mobile-toggle" onClick={() => setMobileOpen((open) => !open)} aria-label="Toggle navigation">
              Menu
            </button>
            <OrgSwitcher />
            {onRefresh && <button className="refresh" type="button" onClick={onRefresh}>Refresh</button>}
          </div>
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
