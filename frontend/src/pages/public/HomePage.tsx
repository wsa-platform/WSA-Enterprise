import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { CropsCategoryMenu } from '../../public/CropsCategoryMenu'
import { HomeFeaturePanels } from '../../public/HomeFeaturePanels'
import { PublicFeatured } from '../../public/PublicFeatured'
import { PublicLayout } from '../../public/PublicLayout'
import { PublicSectionCard } from '../../public/PublicSectionCard'
import { HERO_IMAGE, HOME_MARKETPLACE_TILE, PUBLIC_SECTIONS } from '../../public/sections'

const PILLARS = [
  { key: 'knowledge', to: '/library', icon: '📚' },
  { key: 'market', to: '/market', icon: '🛒' },
  { key: 'services', to: '/#home-categories', icon: '🌱' },
  { key: 'projects', to: '/sections/small-projects', icon: '🏡' },
] as const

const HERO_PILLS = ['betterYield', 'lessResources', 'sustainableFuture'] as const

const HIGHLIGHTS = [
  { key: 'products', to: '/market', icon: '🛒' },
  { key: 'users', to: '/register', icon: '👥' },
  { key: 'services', to: '/#home-categories', icon: '🛠️' },
  { key: 'projects', to: '/sections/small-projects', icon: '🏗️' },
  { key: 'trust', to: '/about', icon: '✅' },
] as const

/** Visual strip categories from the approved design (real section routes). */
const STRIP_SECTION_IDS = [
  'beekeeping',
  'ornamental-plants',
  'medicinal-plants',
  'fruit-trees',
  'vegetables',
  'field-crops',
  'hydroponic-aquaculture',
] as const

export function HomePage() {
  const { t } = useTranslation()

  return (
    <PublicLayout>
      <section className="hp-hero hp-hero--design" aria-labelledby="hero-title">
        <div
          className="hp-hero-bg"
          style={{ backgroundImage: `url(${HERO_IMAGE})` }}
          role="img"
          aria-label={t('website.hero.imageAlt')}
        />
        <div className="hp-hero-overlay" aria-hidden="true" />
        <div className="gs-container hp-hero-grid">
          <div className="hp-hero-copy">
            <span className="hp-hero-kicker">— {t('website.brand')} —</span>
            <h1 id="hero-title">
              <span className="hp-hero-line hp-hero-line--primary">
                {t('website.hero.titleLine1Lead')}
                <em>{t('website.hero.titleLine1Accent')}</em>
              </span>
              <span className="hp-hero-line hp-hero-line--accent">
                {t('website.hero.titleLine2')}
              </span>
              <span className="hp-hero-line hp-hero-line--accent">
                {t('website.hero.titleLine3')}
              </span>
            </h1>
            <p className="hp-hero-support">{t('website.hero.supportLine')}</p>
            <ul className="hp-hero-pills">
              {HERO_PILLS.map((pill) => (
                <li key={pill}>{t(`website.hero.pills.${pill}`)}</li>
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
          <HomeFeaturePanels />
        </div>
      </section>

      <section className="hp-strip" aria-label={t('website.sections.title')}>
        <div className="gs-container hp-strip-row">
          {STRIP_SECTION_IDS.map((id) => {
            const section = PUBLIC_SECTIONS.find((item) => item.id === id)
            if (!section) return null
            const to = id === 'field-crops' ? '/crops/field' : `/sections/${id}`
            return (
              <Link key={id} to={to} className="hp-strip-card">
                <span className="hp-strip-media" style={{ backgroundImage: `url(${section.image})` }} />
                <span className="hp-strip-label">{t(section.titleKey)}</span>
              </Link>
            )
          })}
          <a href="#home-categories" className="hp-strip-card hp-strip-card--more">
            <span className="hp-strip-more-icon" aria-hidden="true">▦</span>
            <span className="hp-strip-label">{t('website.sections.exploreAll')}</span>
          </a>
        </div>
      </section>

      <section className="hp-highlights" aria-label={t('website.highlightsBar.ariaLabel')}>
        <div className="gs-container hp-highlights-row">
          {HIGHLIGHTS.map((item) => (
            <Link key={item.key} to={item.to} className="hp-highlight-item">
              <span aria-hidden="true">{item.icon}</span>
              <strong>{t(`website.highlightsBar.${item.key}`)}</strong>
            </Link>
          ))}
        </div>
      </section>

      <section className="hp-pillars" aria-labelledby="pillars-title">
        <div className="gs-container">
          <div className="gs-section-header hp-section-header">
            <div className="gs-section-eyebrow">
              <span className="gs-section-line" />
              <span>{t('website.brand')}</span>
            </div>
            <h2 id="pillars-title">{t('website.pillars.title')}</h2>
            <p className="gs-section-subtitle">{t('website.pillars.subtitle')}</p>
          </div>
          <div className="hp-pillars-grid">
            {PILLARS.map((pillar) => (
              <Link key={pillar.key} to={pillar.to} className="hp-pillar-card">
                <span className="hp-pillar-icon" aria-hidden="true">{pillar.icon}</span>
                <h3>{t(`website.pillars.${pillar.key}`)}</h3>
                <p>{t(`website.pillars.${pillar.key}Desc`)}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section
        id="home-categories"
        className="gs-section gs-section-cream hp-categories"
        aria-labelledby="sections-title"
      >
        <div className="gs-container">
          <div className="gs-section-header hp-section-header">
            <div className="gs-section-eyebrow">
              <span className="gs-section-line" />
              <span>{t('website.brand')}</span>
            </div>
            <h2 id="sections-title">{t('website.sections.title')}</h2>
            <p className="gs-section-subtitle">{t('website.sections.subtitle')}</p>
          </div>
          <div className="gs-category-grid hp-category-grid">
            {PUBLIC_SECTIONS.map((section) => (
              section.id === 'field-crops' ? (
                <CropsCategoryMenu key="crops-menu-grid" />
              ) : (
                <PublicSectionCard
                  key={section.id}
                  section={section}
                  to={`/sections/${section.id}`}
                  variant="media"
                />
              )
            ))}
            <PublicSectionCard
              key="product-market"
              section={HOME_MARKETPLACE_TILE}
              to={HOME_MARKETPLACE_TILE.to}
              variant="media"
              image={PUBLIC_SECTIONS.find((s) => s.id === 'store')?.image}
            />
          </div>
        </div>
      </section>

      <PublicFeatured />
    </PublicLayout>
  )
}
