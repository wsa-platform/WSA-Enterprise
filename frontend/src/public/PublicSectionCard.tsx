import type { CSSProperties } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import type { PublicSectionConfig } from './sections'

type PublicSectionCardModel = Pick<PublicSectionConfig, 'titleKey' | 'descriptionKey' | 'icon' | 'iconBg'>

/** Category card — garden-store Home.tsx category grid */
export function PublicSectionCard({
  section,
  to,
}: {
  section: PublicSectionCardModel
  to: string
}) {
  const { t } = useTranslation()

  return (
    <Link
      to={to}
      className="gs-category-card"
      style={{ '--icon-bg': section.iconBg } as CSSProperties}
    >
      <div className="gs-cat-icon-wrap">
        <span aria-hidden="true">{section.icon}</span>
      </div>
      <div className="gs-category-card-text">
        <h3>{t(section.titleKey)}</h3>
        <p>{t(section.descriptionKey)}</p>
      </div>
    </Link>
  )
}
