import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { logout } from '../api'
import { buildInternalBreadcrumbs, visibleNavSections } from '../navigation/internalNav'
import { internalPaths } from '../navigation/paths'
import { LanguageSelector } from './LanguageSelector'
import { OrgSwitcher } from './OrgSwitcher'
import { Breadcrumbs } from './PageHeader'

export function AppShell({
  workspaceName,
  onRefresh,
}: {
  workspaceName: string
  onRefresh?: () => void
}) {
  const { t } = useTranslation()
  const { user, token, clearSession } = useAuth()
  const { can, context, loading: permissionsLoading } = usePermissions()
  const location = useLocation()
  const navigate = useNavigate()
  const [mobileOpen, setMobileOpen] = useState(false)
  const sections = visibleNavSections(can, permissionsLoading)
  const breadcrumbs = buildInternalBreadcrumbs(location.pathname, t)
  const roleLabel = context?.roles[0]?.name ?? context?.membership_role ?? t('common.member')

  const handleLogout = async () => {
    if (token) await logout(token).catch(() => undefined)
    clearSession()
    navigate(internalPaths.login, { replace: true })
  }

  return (
    <div className="app-shell">
      {mobileOpen && (
        <button
          type="button"
          className="mobile-nav-backdrop"
          aria-label={t('nav.closeNav')}
          onClick={() => setMobileOpen(false)}
        />
      )}
      <aside className={mobileOpen ? 'mobile-open' : undefined}>
        <div className="brand"><span>W</span> WSA</div>
        <nav aria-label={t('nav.primary')}>
          {sections.map((section) => (
            <div className="nav-section" key={section.titleKey}>
              <p className="nav-section-title">{t(section.titleKey)}</p>
              {section.items.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.end}
                  className={({ isActive }) => isActive ? 'active' : undefined}
                  onClick={() => setMobileOpen(false)}
                >
                  {t(item.labelKey)}
                </NavLink>
              ))}
            </div>
          ))}
        </nav>
        <div className="account">
          <strong>{user?.name}</strong>
          <span>{workspaceName}</span>
          <span className="role-chip">{permissionsLoading ? t('nav.loadingRole') : roleLabel}</span>
          <button className="link-button sign-out" type="button" onClick={() => void handleLogout()}>{t('common.signOut')}</button>
        </div>
      </aside>
      <main className="dashboard">
        <header className="shell-header">
          <div>
            <Breadcrumbs items={breadcrumbs} />
            <p className="eyebrow">{t('common.workspace')}</p>
            <h1>{workspaceName}</h1>
          </div>
          <div className="header-actions">
            <LanguageSelector />
            <button type="button" className="mobile-toggle" onClick={() => setMobileOpen((open) => !open)} aria-expanded={mobileOpen} aria-label={t('nav.toggleNav')}>
              {t('common.menu')}
            </button>
            <OrgSwitcher />
            {onRefresh && <button className="refresh" type="button" onClick={onRefresh}>{t('common.refresh')}</button>}
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

export function RecordList({ rows, emptyLabel }: { rows: unknown[]; emptyLabel?: string }) {
  const { t } = useTranslation()
  const label = emptyLabel ?? t('common.noRecords')

  if (rows.length === 0) return <p className="muted">{label}</p>

  return (
    <div className="module-results">
      {rows.slice(0, 12).map((row, index) => (
        <article className="record-card" key={index}>{renderRecordCard(row, t('common.record'))}</article>
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

function renderRecordCard(row: unknown, fallbackLabel: string) {
  if (!row || typeof row !== 'object') return <pre>{JSON.stringify(row, null, 2)}</pre>
  const record = row as Record<string, unknown>
  const title = String(record.title_ar ?? record.title ?? record.name ?? record.reference ?? record.code ?? fallbackLabel)
  const subtitle = String(record.summary_ar ?? record.summary ?? record.description ?? record.notes ?? record.status ?? '')
  const meta = [record.code, record.status, record.locale, record.provider, record.confidence_score].filter(Boolean).map(String).join(' · ')
  return <>
    <strong dir="auto">{title}</strong>
    {subtitle && <p dir="auto">{subtitle}</p>}
    {meta && <span>{meta}</span>}
  </>
}
