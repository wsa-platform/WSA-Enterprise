import { useTranslation } from 'react-i18next'
import { Link, Navigate, useParams } from 'react-router-dom'
import { FieldCropSelector } from '../../public/FieldCropSelector'
import { PublicLayout } from '../../public/PublicLayout'
import {
  isPlantProductionCategoryId,
  plantProductionCategoryItem,
} from '../../public/plantProductionMenu'

/** Dedicated public page for a plant production category. */
export function PlantProductionPage() {
  const { t } = useTranslation()
  const { categoryId } = useParams<{ categoryId: string }>()

  if (!isPlantProductionCategoryId(categoryId)) {
    return <Navigate to="/" replace />
  }

  const item = plantProductionCategoryItem(categoryId)

  return (
    <PublicLayout>
      <section className="gs-section gs-section-cream" aria-labelledby="plant-production-title">
        <div className="gs-container">
          <nav className="public-breadcrumb" aria-label={t('website.breadcrumb')}>
            <Link to="/">{t('website.nav.home')}</Link>
            <span aria-hidden="true"> / </span>
            <span>{t('website.nav.plantProduction')}</span>
            <span aria-hidden="true"> / </span>
            <span>{t(item.labelKey)}</span>
          </nav>
          <div
            className={
              categoryId === 'field-crops'
                ? 'gs-crops-placeholder gs-crops-placeholder--with-selector'
                : 'gs-crops-placeholder'
            }
          >
            <div className="gs-cat-icon-wrap" style={{ ['--icon-bg' as string]: 'oklch(0.88 0.06 145)' }}>
              <span aria-hidden="true">{item.icon}</span>
            </div>
            <h1 id="plant-production-title">{t(item.labelKey)}</h1>
            <p>{t('website.plantProduction.placeholderBody')}</p>
            {categoryId === 'field-crops' ? <FieldCropSelector /> : null}
          </div>
        </div>
      </section>
    </PublicLayout>
  )
}
