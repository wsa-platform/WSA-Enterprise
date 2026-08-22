import { useEffect, useState, type CSSProperties } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, Navigate, useParams } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { PublicLayout } from '../../public/PublicLayout'
import { JobEntryChoices } from '../jobs/JobsEnterPage'
import {
  getSectionById,
  HIDDEN_PUBLIC_MODULES,
  LEGACY_SECTION_REDIRECTS,
  MODULE_DESCRIPTION_KEYS,
  SECTION_MODULE_MAP,
} from '../../public/sections'

type CatalogModule = { key: string; label: string; requires_auth: boolean }

type PublicCatalog = {
  service_modules: CatalogModule[]
}

type BrowseItem = {
  id: number
  title?: string
  title_ar?: string
  summary?: string
  description?: string
  code?: string
}

export function SectionPage() {
  const { sectionId } = useParams<{ sectionId: string }>()
  const { t, i18n } = useTranslation()
  const { token } = useAuth()
  const section = getSectionById(sectionId)
  const [catalog, setCatalog] = useState<PublicCatalog | null>(null)
  const [browseItems, setBrowseItems] = useState<BrowseItem[]>([])
  const [browseError, setBrowseError] = useState('')

  const orgSlug = import.meta.env.VITE_PUBLIC_ORG_SLUG as string | undefined
  const locale = i18n.language?.slice(0, 2) ?? 'en'

  useEffect(() => {
    fetch('/api/v1/public/services')
      .then(async (response) => {
        if (!response.ok) throw new Error('catalog')
        return response.json() as Promise<PublicCatalog>
      })
      .then(setCatalog)
      .catch(() => setCatalog({ service_modules: [] }))
  }, [])

  useEffect(() => {
    if (!section?.catalogModule || !orgSlug) {
      setBrowseItems([])
      return
    }

    const endpoint =
      section.catalogModule === 'training'
        ? `/api/v1/public/training/courses?organization=${encodeURIComponent(orgSlug)}&locale=${locale}&per_page=8`
        : section.catalogModule === 'library'
          ? `/api/v1/public/library/items?organization=${encodeURIComponent(orgSlug)}&locale=${locale}&per_page=8`
          : null

    if (!endpoint) {
      setBrowseItems([])
      return
    }

    fetch(endpoint)
      .then(async (response) => {
        if (!response.ok) throw new Error('browse')
        const payload = (await response.json()) as { data?: BrowseItem[] }
        setBrowseItems(payload.data ?? [])
        setBrowseError('')
      })
      .catch(() => {
        setBrowseItems([])
        setBrowseError(t('website.browse.unavailable'))
      })
  }, [section, orgSlug, locale, t])

  if (sectionId && LEGACY_SECTION_REDIRECTS[sectionId]) {
    return <Navigate to={`/sections/${LEGACY_SECTION_REDIRECTS[sectionId]}`} replace />
  }

  if (!section) {
    return <Navigate to="/" replace />
  }

  const moduleKeys = SECTION_MODULE_MAP[section.id] ?? []
  const modules =
    catalog?.service_modules.filter(
      (module) => moduleKeys.includes(module.key) && !HIDDEN_PUBLIC_MODULES.has(module.key),
    ) ?? []

  return (
    <PublicLayout>
      <nav className="public-breadcrumb" aria-label={t('website.breadcrumb')}>
        <Link to="/">{t('website.nav.home')}</Link>
        <span aria-hidden="true"> / </span>
        <span>{t(section.titleKey)}</span>
      </nav>

      <section
        className="public-page-hero"
        aria-labelledby="section-title"
        style={{ '--section-accent': section.accent } as CSSProperties}
      >
        <div
          className="gs-hero-bg"
          style={{ backgroundImage: `url(${section.image})` }}
          role="img"
          aria-label={t(section.imageAltKey)}
        />
        <div className="gs-container public-page-hero-content">
          <h1 id="section-title">{t(section.titleKey)}</h1>
          <p>{t(section.descriptionKey)}</p>
        </div>
      </section>

      <div className="public-page-content">
        <div className="public-features">
          {section.featureKeys.map((key) => (
            <article key={key} className="public-feature">
              <h3>{t(`${key}.title`)}</h3>
              <p>{t(`${key}.body`)}</p>
            </article>
          ))}
        </div>

        {section.highlightKeys.length > 0 && (
          <>
            <h2 className="public-services-heading">{t('website.services.highlights')}</h2>
            <div className="public-services-grid">
              {section.highlightKeys.map((key) => (
                <article key={key} className="public-service-card">
                  <h4>{t(`${key}.title`)}</h4>
                  <p>{t(`${key}.body`)}</p>
                </article>
              ))}
            </div>
          </>
        )}

        {modules.length > 0 && (
          <>
            <h2 className="public-services-heading">{t('website.services.heading')}</h2>
            <div className="public-services-grid">
              {modules.map((module) => {
                const requiresAuth = module.requires_auth

                return (
                  <article key={module.key} className="public-service-card">
                    <span className={`public-service-badge ${requiresAuth ? 'auth' : 'public'}`}>
                      {requiresAuth
                        ? t('website.access.signInRequired')
                        : t('website.access.public')}
                    </span>
                    <h4>{module.label}</h4>
                    <p>{t(MODULE_DESCRIPTION_KEYS[module.key] ?? 'website.services.genericDesc')}</p>
                    {!token && requiresAuth && (
                      <Link to={section.id === 'jobs' ? '/jobs/enter' : '/login'} className="gs-btn gs-btn-primary">
                        {t('website.nav.login')}
                      </Link>
                    )}
                  </article>
                )
              })}
            </div>
          </>
        )}

        {browseItems.length > 0 && (
          <>
            <h2 className="public-services-heading">{t('website.browse.title')}</h2>
            <ul className="public-browse-list">
              {browseItems.map((item) => {
                const title =
                  locale === 'ar' && item.title_ar ? item.title_ar : item.title ?? item.code ?? `#${item.id}`
                const summary = item.summary ?? item.description ?? ''
                return (
                  <li key={item.id} className="public-browse-item">
                    <div>
                      <h4>{title}</h4>
                      {summary && <p>{summary}</p>}
                    </div>
                    {!token && (
                      <Link to="/login" className="gs-btn gs-btn-ghost">
                        {t('website.access.signInRequired')}
                      </Link>
                    )}
                  </li>
                )
              })}
            </ul>
          </>
        )}

        {browseError && <p className="muted">{browseError}</p>}

        {!orgSlug && (section.catalogModule === 'training' || section.catalogModule === 'library') && (
          <p className="muted">{t('website.browse.orgHint')}</p>
        )}

        {section.id === 'jobs' && <JobEntryChoices />}

        {!token && section.id !== 'jobs' && (
          <div className="public-cta-banner">
            <div>
              <h3>{t('website.cta.joinTitle')}</h3>
              <p>{t('website.cta.joinBody')}</p>
            </div>
            <Link to="/register" className="gs-btn gs-btn-primary">
              {t('website.nav.register')}
            </Link>
          </div>
        )}
      </div>
    </PublicLayout>
  )
}
