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

/** Reference hero card imagery — vegetable / plant / laptop. */
const PANEL_IMAGES = {
  producer: 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?auto=format&fit=crop&w=640&q=80',
  smart: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=640&q=80',
  jobs: 'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=640&q=80',
} as const

export function HomeFeaturePanels() {
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
    <aside className="hp-feature-panels" aria-label={t('website.homePanels.ariaLabel')}>
      <div className="hp-feature-panels-tray">
        {panels.map((panel) => (
          <article key={panel.id} className={`hp-feature-panel hp-feature-panel--${panel.tone}`}>
            <div
              className="hp-feature-panel-visual"
              style={{ backgroundImage: `url(${panel.image})` }}
              aria-hidden="true"
            />
            <div className="hp-feature-panel-copy">
              <h3>{panel.title}</h3>
              <p>{panel.body}</p>
              <Link to={panel.to} className="hp-feature-panel-cta">
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
