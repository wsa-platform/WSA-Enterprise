import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { FEATURED_LINKS, PUBLIC_SECTIONS } from './sections'

/** Featured cards — garden-store product-card style */
export function PublicFeatured() {
  const { t } = useTranslation()

  return (
    <section className="gs-section gs-section-white" aria-labelledby="featured-title">
      <div className="gs-container">
        <div className="gs-section-header">
          <div className="gs-section-eyebrow">
            <span className="gs-section-line" />
            <span>{t('website.featured.title')}</span>
          </div>
          <h2 id="featured-title">{t('website.featured.subtitle')}</h2>
        </div>
        <div className="gs-product-grid">
          {FEATURED_LINKS.map(({ sectionId, highlightKey }) => {
            const section = PUBLIC_SECTIONS.find((item) => item.id === sectionId)
            if (!section) return null

            return (
              <Link key={sectionId} to={`/sections/${sectionId}`} className="gs-product-card">
                <div className="gs-product-card-media">
                  <img src={section.image} alt={t(section.imageAltKey)} loading="lazy" />
                </div>
                <div className="gs-product-card-body">
                  <span className="gs-product-card-icon" aria-hidden="true">
                    {section.icon}
                  </span>
                  <h3>{t(highlightKey)}</h3>
                  <p>{t(`${highlightKey}Desc`)}</p>
                </div>
              </Link>
            )
          })}
        </div>
      </div>
    </section>
  )
}
