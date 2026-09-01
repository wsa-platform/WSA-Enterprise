import type { BreadcrumbItem } from '../components/PageHeader'
import { internalPaths, publicPaths } from './paths'

export type InternalNavItem = {
  to: string
  labelKey: string
  end?: boolean
  permission?: string
  anyPermission?: string[]
}

export type InternalNavSection = {
  titleKey: string
  items: InternalNavItem[]
}

export const INTERNAL_NAV_SECTIONS: InternalNavSection[] = [
  {
    titleKey: 'nav.overview',
    items: [
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
      { to: '/jobs/application', labelKey: 'nav.myJobApplication', anyPermission: ['jobs.talent.register', 'jobs.talent.manage'] },
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
      { to: '/business', labelKey: 'nav.business', permission: 'business.view' },
    ],
  },
  {
    titleKey: 'nav.account',
    items: [
      { to: internalPaths.account, labelKey: 'nav.myAccount', end: true },
      { to: internalPaths.profile, labelKey: 'nav.profile' },
      { to: publicPaths.market, labelKey: 'nav.productMarket' },
      { to: internalPaths.settings, labelKey: 'nav.settings', permission: 'platform.view' },
    ],
  },
]

export function isNavItemVisible(
  item: InternalNavItem,
  can: (permission: string) => boolean,
  loading: boolean,
): boolean {
  if (loading) return !item.permission && !item.anyPermission
  if (item.anyPermission?.some((permission) => can(permission))) return true
  if (item.anyPermission) return Boolean(item.permission && can(item.permission))
  if (!item.permission) return true
  return can(item.permission)
}

export function visibleNavSections(
  can: (permission: string) => boolean,
  loading: boolean,
): InternalNavSection[] {
  return INTERNAL_NAV_SECTIONS
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => isNavItemVisible(item, can, loading)),
    }))
    .filter((section) => section.items.length > 0)
}

export function flattenNavTargets(sections: InternalNavSection[] = INTERNAL_NAV_SECTIONS): string[] {
  return sections.flatMap((section) => section.items.map((item) => item.to))
}

export function buildInternalBreadcrumbs(
  pathname: string,
  t: (key: string) => string,
): BreadcrumbItem[] {
  const home = { label: t('nav.home'), to: internalPaths.account }
  const account = { label: t('nav.myAccount'), to: internalPaths.account }
  const products = { label: t('nav.myProducts'), to: internalPaths.products }

  const map: Record<string, BreadcrumbItem[]> = {
    '/organization': [home, { label: t('nav.organization') }],
    '/billing': [home, { label: t('nav.billing') }],
    '/admin/users': [home, { label: t('nav.users') }],
    '/admin/teams': [home, { label: t('nav.teams') }],
    '/admin/roles': [home, { label: t('nav.roles') }],
    '/admin/audit': [home, { label: t('nav.audit') }],
    '/admin/monitoring': [home, { label: t('nav.monitoring') }],
    '/admin/analytics': [home, { label: t('nav.analytics') }],
    '/admin/api-clients': [home, { label: t('nav.apiClients') }],
    '/ai/workspace': [home, { label: t('nav.aiWorkspace') }],
    '/ai/assistant': [home, { label: t('nav.aiAssistant') }],
    '/ai/vision': [home, { label: t('nav.aiVision') }],
    '/jobs': [home, { label: t('nav.jobs') }],
    '/jobs/talent': [home, { label: t('nav.jobs'), to: '/jobs' }, { label: t('nav.talentProfile') }],
    '/jobs/application': [home, { label: t('nav.jobs'), to: '/jobs' }, { label: t('nav.myJobApplication') }],
    [internalPaths.account]: [{ label: t('nav.myAccount') }],
    [internalPaths.profile]: [home, account, { label: t('nav.profile') }],
    [internalPaths.products]: [home, account, { label: t('nav.myProducts') }],
    [internalPaths.newProduct]: [home, account, products, { label: t('market.addProduct') }],
    '/communications': [home, { label: t('nav.communications') }],
    '/beekeeping': [home, { label: t('nav.beekeeping') }],
    '/marketing': [home, { label: t('nav.marketingDashboard') }],
    '/marketing/campaigns': [home, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingCampaigns') }],
    '/marketing/templates': [home, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingTemplates') }],
    '/marketing/segments': [home, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingSegments') }],
    '/marketing/consent': [home, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingConsent') }],
    '/notifications': [home, { label: t('nav.notifications') }],
    [internalPaths.settings]: [home, { label: t('nav.settings') }],
  }

  if (pathname.startsWith('/ai/requests/')) {
    return [home, { label: t('nav.aiWorkspace'), to: '/ai/workspace' }, { label: t('nav.requestDetail') }]
  }
  if (pathname.startsWith('/admin/teams/')) {
    return [home, { label: t('nav.teams'), to: '/admin/teams' }, { label: t('nav.teamDetail') }]
  }
  if (pathname === internalPaths.newProduct || pathname.startsWith('/seller/listings/new')) {
    return map[internalPaths.newProduct] ?? [home, account, products, { label: t('market.addProduct') }]
  }
  if (pathname.startsWith(`${internalPaths.products}/`) || pathname.startsWith('/seller/listings/')) {
    return [home, account, products, { label: t('market.editProduct') }]
  }
  if (pathname.startsWith('/marketing/campaigns/')) {
    return [home, { label: t('nav.marketing'), to: '/marketing' }, { label: t('nav.marketingCampaigns'), to: '/marketing/campaigns' }, { label: t('nav.marketingCampaignDetail') }]
  }

  return map[pathname] ?? [home, { label: pathname.replace('/', '') || t('nav.page') }]
}
