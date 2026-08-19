import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { logout } from '../api'
import { LanguageSelector } from './LanguageSelector'
import { OrgSwitcher } from './OrgSwitcher'
import { Breadcrumbs, type BreadcrumbItem } from './PageHeader'

type NavItem = {
  to: string
  labelKey: string
  end?: boolean
  permission?: string
  anyPermission?: string[]
}

const navSections: Array<{ titleKey: string; items: NavItem[] }> = [
  {
    titleKey: 'nav.overview',
    items: [
      { to: '/dashboard', labelKey: 'nav.dashboard', end: true, permission: 'platform.view' },
      { to: '/notifications', labelKey: 'nav.notifications', permission: 'platform.view' },
    ],
  },
  {
    titleKey: 'nav.enterprise',
    items: [
      { to: '/organization', labelKey: 'nav.organization', permission: 'platform.view' },
      { to: '/admin/analytics', labelKey: 'nav.analytics', permission: 'platform.view' },
      { to: '/billing', labelKey: 'nav.billing', permission: 'billing.view' },
      { to: '/admin/users', labelKey: 'nav.users', permission: 'access.manage' },
      { to: '/admin/teams', labelKey: 'nav.teams', permission: 'access.manage' },
      { to: '/admin/roles', labelKey: 'nav.roles', permission: 'access.manage' },
      { to: '/admin/api-clients', labelKey: 'nav.apiClients', permission: 'access.manage' },
      { to: '/admin/audit', labelKey: 'nav.audit', permission: 'access.manage' },
      { to: '/admin/monitoring', labelKey: 'nav.monitoring', anyPermission: ['monitoring.view', 'access.manage'] },
    ],
  },
  {
    titleKey: 'nav.ai',
    items: [
      { to: '/ai/workspace', labelKey: 'nav.aiWorkspace', permission: 'ai.use' },
      { to: '/ai/assistant', labelKey: 'nav.aiAssistant', anyPermission: ['ai.use', 'ai.assistant'] },
      { to: '/ai/vision', labelKey: 'nav.aiVision', anyPermission: ['ai.use', 'ai.vision'] },
    ],
  },
  {
    titleKey: 'nav.ecosystem',
    items: [
      { to: '/jobs', labelKey: 'nav.jobs', permission: 'jobs.view' },
      { to: '/jobs/talent', labelKey: 'nav.talentProfile', anyPermission: ['jobs.talent.register', 'jobs.talent.manage'] },
      { to: '/communications', labelKey: 'nav.communications', permission: 'platform.view' },
      { to: '/beekeeping', labelKey: 'nav.beekeeping', permission: 'beekeeping.view' },
    ],
  },
  {
    titleKey: 'nav.marketing',
    items: [
      { to: '/marketing', labelKey: 'nav.marketingDashboard', permission: 'marketing.admin' },
      { to: '/marketing/campaigns', labelKey: 'nav.marketingCampaigns', permission: 'marketing.view' },
      { to: '/marketing/templates', labelKey: 'nav.marketingTemplates', permission: 'marketing.view' },
      { to: '/marketing/segments', labelKey: 'nav.marketingSegments', permission: 'marketing.view' },
      { to: '/marketing/consent', labelKey: 'nav.marketingConsent', permission: 'marketing.view' },
    ],
  },
  {
    titleKey: 'nav.modules',
    items: [
      { to: '/farms', labelKey: 'nav.farms', permission: 'farm.view' },
      { to: '/crops', labelKey: 'nav.crops', permission: 'crop.view' },
      { to: '/soil', labelKey: 'nav.soil', permission: 'soil.view' },
      { to: '/diagnosis', labelKey: 'nav.diagnosis', permission: 'diagnosis.view' },
      { to: '/training', labelKey: 'nav.training', permission: 'training.view' },
      { to: '/library', labelKey: 'nav.library', permission: 'library.view' },
      { to: '/business', labelKey: 'nav.business', permission: 'business.view' },
    ],
  },
  {
    titleKey: 'nav.account',
    items: [
      { to: '/account', labelKey: 'nav.myAccount', end: true },
      { to: '/account/profile', labelKey: 'nav.profile' },
      { to: '/account/products', labelKey: 'nav.myProducts', anyPermission: ['market.view', 'market.create', 'market.manage_own'] },
      { to: '/settings', labelKey: 'nav.settings', permission: 'platform.view' },
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

function useBreadcrumbItems(pathname: string): BreadcrumbItem[] {
  const { t } = useTranslation()
  const dashboard = { label: t('nav.dashboard'), to: '/' }

  const map: Record<string, BreadcrumbItem[]> = {
    '/': [{ label: t('nav.dashboard') }],
    '/organization': [dashboard, { label: t('nav.organization') }],
    '/billing': [dashboard, { label: t('nav.billing') }],
    '/admin/users': [dashboard, { label: t('nav.users') }],
    '/admin/teams': [dashboard, { label: t('nav.teams') }],
    '/admin/roles': [dashboard, { label: t('nav.roles') }],
    '/admin/audit': [dashboard, { label: t('nav.audit') }],
    '/admin/monitoring': [dashboard, { label: t('nav.monitoring') }],
    '/admin/analytics': [dashboard, { label: t('nav.analytics') }],
    '/admin/api-clients': [dashboard, { label: t('nav.apiClients') }],
    '/ai/workspace': [dashboard, { label: t('nav.aiWorkspace') }],
    '/ai/assistant': [dashboard, { label: t('nav.aiAssistant') }],
    '/ai/vision': [dashboard, { label: t('nav.aiVision') }],
    '/jobs': [dashboard, { label: t('nav.jobs') }],
    '/jobs/talent': [dashboard, { label: t('nav.jobs'), to: '/jobs' }, { label: t('nav.talentProfile') }],
    '/account': [dashboard, { label: t('nav.myAccount') }],
    '/account/profile': [dashboard, { label: t('nav.myAccount'), to: '/account' }, { label: t('nav.profile') }],
    '/account/products': [dashboard, { label: t('nav.myAccount'), to: '/account' }, { label: t('nav.myProducts') }],
    '/communications': [dashboard, { label: t('nav.communications') }],
    '/beekeeping': [dashboard, { label: t('nav.beekeeping') }],
    '/marketing': [dashboard, { label: t('nav.marketingDashboard') }],
    '/marketing/campaigns': [dashboard, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingCampaigns') }],
    '/marketing/templates': [dashboard, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingTemplates') }],
    '/marketing/segments': [dashboard, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingSegments') }],
    '/marketing/consent': [dashboard, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingConsent') }],
    '/notifications': [dashboard, { label: t('nav.notifications') }],
    '/settings': [dashboard, { label: t('nav.settings') }],
  }

  if (pathname.startsWith('/ai/requests/')) {
    return [dashboard, { label: t('nav.aiWorkspace'), to: '/ai/workspace' }, { label: t('nav.requestDetail') }]
  }
  if (pathname.startsWith('/admin/teams/')) {
    return [dashboard, { label: t('nav.teams'), to: '/admin/teams' }, { label: t('nav.teamDetail') }]
  }
  if (pathname.startsWith('/account/products/')) {
    return [dashboard, { label: t('nav.myAccount'), to: '/account' }, { label: t('nav.myProducts'), to: '/account/products' }, { label: t('market.editProduct') }]
  }
  if (pathname.startsWith('/seller/listings/')) {
    return [dashboard, { label: t('nav.myAccount'), to: '/account' }, { label: t('nav.myProducts'), to: '/account/products' }, { label: t('market.editProduct') }]
  }
  if (pathname.startsWith('/marketing/campaigns/')) {
    return [dashboard, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingCampaigns'), to: '/marketing/campaigns' }, { label: t('nav.marketingCampaignDetail') }]
  }

  return map[pathname] ?? [dashboard, { label: pathname.replace('/', '') || t('nav.page') }]
}

export function AppShell({
  workspaceName,
  onRefresh,
}: {
  workspaceName: string
  onRefresh?: () => void
}) {
  const { t } = useTranslation()
  const { user, token, clearSession } = useAuth()
  const { context, loading: permissionsLoading } = usePermissions()
  const location = useLocation()
  const [mobileOpen, setMobileOpen] = useState(false)
  const sections = useVisibleNav()
  const breadcrumbs = useBreadcrumbItems(location.pathname)
  const roleLabel = context?.roles[0]?.name ?? context?.membership_role ?? t('common.member')

  const handleLogout = async () => {
    if (token) await logout(token).catch(() => undefined)
    clearSession()
  }

  return (
    <div className="app-shell">
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
          <button className="link-button" type="button" onClick={() => void handleLogout()}>{t('common.signOut')}</button>
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
            <button type="button" className="mobile-toggle" onClick={() => setMobileOpen((open) => !open)} aria-label={t('nav.toggleNav')}>
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
