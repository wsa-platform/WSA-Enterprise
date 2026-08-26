import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { CropsCategoryMenu } from '../../public/CropsCategoryMenu'
import { HomeFeaturePanels } from '../../public/HomeFeaturePanels'
import { PublicLayout } from '../../public/PublicLayout'
import { HERO_IMAGE, PUBLIC_SECTIONS } from '../../public/sections'

const HERO_PILLS = [
  { key: 'sustainableFuture', icon: '🌍' },
  { key: 'lessResources', icon: '💧' },
  { key: 'betterYield', icon: '🌱' },
] as const

const STATS = [
  { key: 'products', value: '12,540', icon: '📦', to: '/market' },
  { key: 'users', value: '3,285', icon: '👥', to: '/register' },
  { key: 'services', value: '1,250', icon: '📄', to: '/#home-categories' },
  { key: 'projects', value: '568', icon: '🏗️', to: '/sections/small-projects' },
  { key: 'trust', value: '98%', icon: '✅', to: '/about' },
] as const

/** Category strip keys mapped to real platform sections/routes. */
const CATEGORY_STRIP = [
  { id: 'beekeeping', to: '/sections/beekeeping' },
  { id: 'ornamental-plants', to: '/sections/ornamental-plants' },
  { id: 'medicinal-plants', to: '/sections/medicinal-plants' },
  { id: 'fruit-trees', to: '/sections/fruit-trees' },
  { id: 'vegetables', to: '/sections/vegetables' },
  { id: 'field-crops', to: '/crops/field' },
  { id: 'hydroponic-aquaculture', to: '/sections/hydroponic-aquaculture' },
] as const

/**
 * Public homepage — layout adapted from the approved WSA prototype
 * (hero + categories + promo sidebar + stats), wired to real routes.
 */
export function HomePage() {
  const { t } = useTranslation()

  return (
    <PublicLayout>
      <div className="hp-shell">
        <section className="hp-hero hp-hero--prototype" aria-labelledby="hero-title">
          <div
            className="hp-hero-bg"
            style={{ backgroundImage: `url(${HERO_IMAGE})` }}
            role="img"
            aria-label={t('website.hero.imageAlt')}
          />
          <div className="hp-hero-copy">
            <span className="hp-hero-kicker">{t('website.hero.eyebrow')}</span>
            <h1 id="hero-title">
              <span className="hp-hero-line">{t('website.hero.titleLine1')}</span>
              <span className="hp-hero-line hp-hero-line--accent">{t('website.hero.titleLine2Full')}</span>
            </h1>
            <p className="hp-hero-support">{t('website.hero.supportLine')}</p>
            <ul className="hp-hero-pills">
              {HERO_PILLS.map((pill) => (
                <li key={pill.key}>
                  <span className="hp-hero-pill-icon" aria-hidden="true">{pill.icon}</span>
                  {t(`website.hero.pills.${pill.key}`)}
                </li>
              ))}
            </ul>
            <div className="hp-hero-actions">
              <a href="#home-categories" className="gs-btn gs-btn-hero-primary">
                {t('website.hero.explore')}
              </a>
              <Link to="/register" className="gs-btn gs-btn-hero-outline">
                {t('website.hero.getStarted')}
              </Link>
            </div>
          </div>
        </section>

        <div className="hp-main-grid">
          <div className="hp-main-primary">
            <section
              id="home-categories"
              className="hp-category-section"
              aria-labelledby="sections-title"
            >
              <div className="hp-category-heading">
                <h2 id="sections-title">{t('website.categoriesStrip.title')}</h2>
                <span className="hp-category-rule" aria-hidden="true" />
              </div>

              <div className="hp-category-prototype-grid">
                {CATEGORY_STRIP.map((item) => {
                  if (item.id === 'field-crops') {
                    return <CropsCategoryMenu key="crops-menu-grid" />
                  }
                  const section = PUBLIC_SECTIONS.find((entry) => entry.id === item.id)
                  if (!section) return null
                  return (
                    <Link key={item.id} to={item.to} className="hp-cat-card">
                      <span
                        className="hp-cat-card-media"
                        style={{ backgroundImage: `url(${section.image})` }}
                      />
                      <span className="hp-cat-card-title">{t(section.titleKey)}</span>
                    </Link>
                  )
                })}
                <a href="#home-categories" className="hp-cat-card hp-cat-card--more">
                  <span className="hp-cat-card-more-icon" aria-hidden="true">＋</span>
                  <span className="hp-cat-card-title">{t('website.sections.exploreAll')}</span>
                </a>
              </div>
            </section>

            <section className="hp-stats" aria-label={t('website.highlightsBar.ariaLabel')}>
              <div className="hp-stats-row">
                {STATS.map((item) => (
                  <Link key={item.key} to={item.to} className="hp-stats-item">
                    <span className="hp-stats-icon" aria-hidden="true">{item.icon}</span>
                    <strong>{item.value}</strong>
                    <span>{t(`website.highlightsBar.${item.key}`)}</span>
                  </Link>
                ))}
              </div>
            </section>
          </div>

          <HomeFeaturePanels variant="promo" />
        </div>
      </div>
    </PublicLayout>
  )
}
