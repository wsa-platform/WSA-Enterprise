import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import type { PlantInspectionContext } from './sections'

type AiPlantInspectionCardProps = {
  context: PlantInspectionContext
  authenticated: boolean
}

export function AiPlantInspectionCard({ context, authenticated }: AiPlantInspectionCardProps) {
  const { t } = useTranslation()
  const examples = t(`website.aiPlantInspection.${context}.examples`, {
    returnObjects: true,
  }) as string[]

  return (
    <article className="public-ai-card" aria-labelledby={`ai-inspection-${context}`}>
      <div className="public-ai-card-header">
        <span className="public-service-badge paid">{t('website.access.paid')}</span>
        <h3 id={`ai-inspection-${context}`}>{t('website.aiPlantInspection.title')}</h3>
      </div>
      <p className="public-ai-card-description">{t('website.aiPlantInspection.description')}</p>
      <div className="public-ai-card-examples">
        <h4>{t('website.aiPlantInspection.examplesLabel')}</h4>
        <ul>
          {Array.isArray(examples) &&
            examples.map((example) => (
              <li key={example}>{example}</li>
            ))}
        </ul>
      </div>
      <p className="public-ai-card-note">{t('website.aiPlantInspection.sampleNote')}</p>
      <div className="public-ai-card-actions">
        {authenticated ? (
          <Link to="/diagnosis" className="gs-btn gs-btn-primary">
            {t('website.aiPlantInspection.openService')}
          </Link>
        ) : (
          <>
            <Link to="/login" className="gs-btn gs-btn-primary">
              {t('website.nav.login')}
            </Link>
            <Link to="/register" className="gs-btn gs-btn-ghost">
              {t('website.nav.register')}
            </Link>
          </>
        )}
      </div>
    </article>
  )
}
