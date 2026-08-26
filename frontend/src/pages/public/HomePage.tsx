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

export function HomePage() {
  const { t } = useTranslation()

  return (
    <PublicLayout>
      <section className="hp-hero" aria-labelledby="hero-title">
        <div
          className="hp-hero-bg"
          style={{ backgroundImage: `url(${HERO_IMAGE})` }}
          role="img"
          aria-label={t('website.hero.imageAlt')}
        />
        <div className="hp-hero-overlay" aria-hidden="true" />
        <div className="gs-container hp-hero-grid">
          <div className="hp-hero-copy">
            <span className="hp-hero-badge">{t('website.brand')}</span>
            <h1 id="hero-title">
              <span className="hp-hero-line">{t('website.hero.titleLine1')}</span>
              <span className="hp-hero-line">{t('website.hero.titleLine2')}</span>
            </h1>
            <p className="hp-hero-support">{t('website.hero.supportLine')}</p>
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
        <div className="hp-hero-wave" aria-hidden="true">
          <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
            <path d="M0,48 C360,90 720,10 1440,48 L1440,80 L0,80 Z" fill="oklch(0.97 0.015 95)" />
          </svg>
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
                <CropsCategoryMenu key="crops-menu" />
              ) : (
                <PublicSectionCard
                  key={section.id}
                  section={section}
                  to={`/sections/${section.id}`}
                />
              )
            ))}
            <PublicSectionCard
              key="product-market"
              section={HOME_MARKETPLACE_TILE}
              to={HOME_MARKETPLACE_TILE.to}
            />
          </div>
        </div>
      </section>

      <PublicFeatured />
    </PublicLayout>
  )
}
