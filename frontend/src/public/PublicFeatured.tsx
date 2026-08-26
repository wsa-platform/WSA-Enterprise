import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { FEATURED_LINKS, PUBLIC_SECTIONS } from './sections'

/** Featured highlights — restyled to match the modern homepage design. */
export function PublicFeatured() {
  const { t } = useTranslation()

  return (
    <section className="hp-featured" aria-labelledby="featured-title">
      <div className="gs-container">
        <div className="gs-section-header hp-section-header">
          <div className="gs-section-eyebrow">
            <span className="gs-section-line" />
            <span>{t('website.featured.title')}</span>
          </div>
          <h2 id="featured-title">{t('website.featured.subtitle')}</h2>
        </div>
        <div className="hp-featured-grid">
          {FEATURED_LINKS.map(({ sectionId, highlightKey }) => {
            const section = PUBLIC_SECTIONS.find((item) => item.id === sectionId)
            if (!section) return null

            return (
              <Link key={sectionId} to={`/sections/${sectionId}`} className="hp-featured-card">
                <div className="hp-featured-media">
                  <img src={section.image} alt={t(section.imageAltKey)} loading="lazy" />
                </div>
                <div className="hp-featured-body">
                  <span className="hp-featured-icon" aria-hidden="true">
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
