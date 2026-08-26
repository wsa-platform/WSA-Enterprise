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
      icon: '🌱',
      tone: 'producer',
    },
    {
      id: 'smart-farmer',
      to: HOME_FEATURE_PANEL_ROUTES.smartFarmer,
      title: t('website.homePanels.smartFarmerTitle'),
      body: t('website.homePanels.smartFarmerBody'),
      icon: '🧠',
      tone: 'smart',
    },
    {
      id: 'jobs',
      to: HOME_FEATURE_PANEL_ROUTES.jobs,
      title: t('website.homePanels.jobsTitle'),
      body: t('website.homePanels.jobsBody'),
      icon: '💼',
      tone: 'jobs',
    },
  ] as const

  return (
    <aside className="hp-feature-panels" aria-label={t('website.homePanels.ariaLabel')}>
      <div className="hp-feature-panels-tray">
        {panels.map((panel) => (
          <Link
            key={panel.id}
            to={panel.to}
            className={`hp-feature-panel hp-feature-panel--${panel.tone}`}
          >
            <span className="hp-feature-panel-icon" aria-hidden="true">{panel.icon}</span>
            <span className="hp-feature-panel-copy">
              <strong>{panel.title}</strong>
              <span>{panel.body}</span>
            </span>
          </Link>
        ))}
      </div>
    </aside>
  )
}
