import { useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import { PublicFeatured } from '../../public/PublicFeatured'
import { PublicLayout } from '../../public/PublicLayout'
import { PublicMarketSection } from '../../public/PublicMarketSection'
import { PublicSectionCard } from '../../public/PublicSectionCard'
import { WaveDivider } from '../../public/WaveDivider'
import { HERO_IMAGE, PUBLIC_SECTIONS } from '../../public/sections'

export function HomePage() {
  const { t } = useTranslation()
  const location = useLocation()

  useEffect(() => {
    if (location.hash !== '#market') return
    document.getElementById('market')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, [location.hash])

  return (
    <PublicLayout>
      {/* Hero — garden-store Home.tsx hero section */}
      <section className="gs-hero" aria-labelledby="hero-title">
        <div
          className="gs-hero-bg"
          style={{ backgroundImage: `url(${HERO_IMAGE})` }}
          role="img"
          aria-label={t('website.hero.imageAlt')}
        />
        <div className="gs-container gs-hero-content">
          <span className="gs-hero-badge">{t('website.brand')}</span>
          <h1 id="hero-title">{t('website.hero.title')}</h1>
          <p>{t('website.hero.subtitle')}</p>
          <div className="gs-hero-actions">
            <Link to="/sections/field-crops" className="gs-btn gs-btn-hero-primary">
              {t('website.hero.explore')}
            </Link>
            <Link to="/register" className="gs-btn gs-btn-hero-outline">
              {t('website.hero.getStarted')}
            </Link>
          </div>
        </div>
        <div className="gs-hero-wave" aria-hidden="true">
          <svg viewBox="0 0 1440 70" preserveAspectRatio="none">
            <path d="M0,40 C480,80 960,0 1440,40 L1440,70 L0,70 Z" fill="oklch(0.28 0.10 145)" />
          </svg>
        </div>
      </section>

      {/* Feature strip — garden-store feature-strip */}
      <section className="gs-feature-strip" aria-hidden="true">
        <div className="gs-container gs-feature-strip-inner">
          <span>🌱 {t('website.brand')}</span>
          <span>🌾 {t('website.sections.fieldCrops.title')}</span>
          <span>🌸 {t('website.sections.ornamentalPlants.title')}</span>
          <span>📚 {t('website.sections.training.title')}</span>
        </div>
        <WaveDivider fill="oklch(0.965 0.018 90)" />
      </section>

      {/* 11 category sections — garden-store category grid */}
      <section className="gs-section gs-section-cream" aria-labelledby="sections-title">
        <div className="gs-container">
          <div className="gs-section-header">
            <div className="gs-section-eyebrow">
              <span className="gs-section-line" />
              <span>{t('website.brand')}</span>
            </div>
            <h2 id="sections-title">{t('website.sections.title')}</h2>
            <p className="gs-section-subtitle">{t('website.sections.subtitle')}</p>
          </div>
          <div className="gs-category-grid">
            {PUBLIC_SECTIONS.map((section) => (
              <PublicSectionCard key={section.id} section={section} />
            ))}
          </div>
        </div>
      </section>

      <WaveDivider fill="oklch(1 0 0)" flip />

      <PublicMarketSection />

      <WaveDivider fill="oklch(1 0 0)" />

      <PublicFeatured />
    </PublicLayout>
  )
}
