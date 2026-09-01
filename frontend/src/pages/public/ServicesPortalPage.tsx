import { useTranslation } from 'react-i18next'
import { Link, Navigate, useParams } from 'react-router-dom'
import { PublicLayout } from '../../public/PublicLayout'
import {
  isServicesPortalPageId,
  servicesPortalMenuItem,
} from '../../public/servicesPortalMenu'

/** Dedicated public page for a services portal destination. */
export function ServicesPortalPage() {
  const { t } = useTranslation()
  const { serviceId } = useParams<{ serviceId: string }>()

  if (!isServicesPortalPageId(serviceId)) {
    return <Navigate to="/" replace />
  }

  const item = servicesPortalMenuItem(serviceId)

  return (
    <PublicLayout>
      <section className="gs-section gs-section-cream" aria-labelledby="services-portal-title">
        <div className="gs-container">
          <nav className="public-breadcrumb" aria-label={t('website.breadcrumb')}>
            <Link to="/">{t('website.nav.home')}</Link>
            <span aria-hidden="true"> / </span>
            <span>{t('website.nav.servicesPortal')}</span>
            <span aria-hidden="true"> / </span>
            <span>{t(item.labelKey)}</span>
          </nav>
          <div className="gs-crops-placeholder">
            <div className="gs-cat-icon-wrap" style={{ ['--icon-bg' as string]: 'oklch(0.88 0.06 145)' }}>
              <span aria-hidden="true">{item.icon}</span>
            </div>
            <h1 id="services-portal-title">{t(item.labelKey)}</h1>
            <p>{t('website.servicesPortal.placeholderBody')}</p>
          </div>
        </div>
      </section>
    </PublicLayout>
  )
}
