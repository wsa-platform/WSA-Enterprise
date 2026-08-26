import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { internalPaths } from '../navigation/paths'
import { JOB_SEEKER_ENTER, sellerAddProductHref } from '../navigation/roleDestinations'

/** Homepage-only entry panels — links to existing platform routes only. */
export const HOME_FEATURE_PANEL_ROUTES = {
  producer: internalPaths.newProduct,
  smartFarmer: '/library',
  jobs: JOB_SEEKER_ENTER,
} as const

const PANEL_IMAGES = {
  producer: 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?auto=format&fit=crop&w=900&q=80',
  smart: 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?auto=format&fit=crop&w=900&q=80',
  jobs: 'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=900&q=80',
} as const

/** Promo sidebar / hero feature panels — three cards, Smart Farmer exactly once. */
export function HomeFeaturePanels({
  variant = 'promo',
}: {
  variant?: 'promo' | 'hero'
} = {}) {
  const { t } = useTranslation()
  const { token } = useAuth()
  const producerTo = sellerAddProductHref(Boolean(token))

  const panels = [
    {
      id: 'producer',
      to: producerTo,
      title: t('website.homePanels.producerTitle'),
      body: t('website.homePanels.producerBody'),
      cta: t('website.homePanels.producerCta'),
      tone: 'producer',
      image: PANEL_IMAGES.producer,
    },
    {
      id: 'smart-farmer',
      to: HOME_FEATURE_PANEL_ROUTES.smartFarmer,
      title: t('website.homePanels.smartFarmerTitle'),
      body: t('website.homePanels.smartFarmerBody'),
      cta: t('website.homePanels.smartFarmerCta'),
      tone: 'smart',
      image: PANEL_IMAGES.smart,
    },
    {
      id: 'jobs',
      to: HOME_FEATURE_PANEL_ROUTES.jobs,
      title: t('website.homePanels.jobsTitle'),
      body: t('website.homePanels.jobsBody'),
      cta: t('website.homePanels.jobsCta'),
      tone: 'jobs',
      image: PANEL_IMAGES.jobs,
    },
  ] as const

  return (
    <aside
      className={`hp-feature-panels hp-feature-panels--${variant}`}
      aria-label={t('website.homePanels.ariaLabel')}
    >
      <div className="hp-feature-panels-stack">
        {panels.map((panel) => (
          <article
            key={panel.id}
            className={`hp-promo-card hp-promo-card--${panel.tone}`}
            style={{ backgroundImage: `url(${panel.image})` }}
          >
            <div className="hp-promo-card-shade" aria-hidden="true" />
            <div className="hp-promo-card-copy">
              <h3>{panel.title}</h3>
              <p>{panel.body}</p>
              <Link to={panel.to} className="hp-promo-card-cta">
                {panel.cta}
                <span aria-hidden="true">←</span>
              </Link>
            </div>
          </article>
        ))}
      </div>
    </aside>
  )
}
