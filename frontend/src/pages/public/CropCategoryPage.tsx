import { useTranslation } from 'react-i18next'
import { Link, Navigate, useParams } from 'react-router-dom'
import { PublicLayout } from '../../public/PublicLayout'
import { cropsCategoryItem, isCropsCategoryId } from '../../public/cropsMenu'

/** Minimal public placeholder for a crops submenu destination. */
export function CropCategoryPage() {
  const { t } = useTranslation()
  const { cropCategoryId } = useParams<{ cropCategoryId: string }>()

  if (!isCropsCategoryId(cropCategoryId)) {
    return <Navigate to="/" replace />
  }

  const item = cropsCategoryItem(cropCategoryId)

  return (
    <PublicLayout>
      <section className="gs-section gs-section-cream" aria-labelledby="crop-category-title">
        <div className="gs-container">
          <nav className="public-breadcrumb" aria-label={t('website.breadcrumb')}>
            <Link to="/">{t('website.nav.home')}</Link>
            <span aria-hidden="true"> / </span>
            <span>{t('website.cropsMenu.parent')}</span>
            <span aria-hidden="true"> / </span>
            <span>{t(item.labelKey)}</span>
          </nav>
          <div className="gs-crops-placeholder">
            <div className="gs-cat-icon-wrap" style={{ ['--icon-bg' as string]: 'oklch(0.88 0.06 90)' }}>
              <span aria-hidden="true">{item.icon}</span>
            </div>
            <h1 id="crop-category-title">{t(item.labelKey)}</h1>
            <p>{t('website.cropsMenu.placeholderBody')}</p>
            <Link to="/" className="gs-btn gs-btn-primary">{t('website.nav.home')}</Link>
          </div>
        </div>
      </section>
    </PublicLayout>
  )
}
