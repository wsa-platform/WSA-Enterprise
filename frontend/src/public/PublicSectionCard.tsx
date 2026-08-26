import type { CSSProperties } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import type { PublicSectionConfig } from './sections'

type PublicSectionCardModel = Pick<PublicSectionConfig, 'titleKey' | 'descriptionKey' | 'icon' | 'iconBg'> & {
  image?: string
}

/** Category card — icon or media variant for homepage grids. */
export function PublicSectionCard({
  section,
  to,
  variant = 'icon',
  image,
}: {
  section: PublicSectionCardModel
  to: string
  variant?: 'icon' | 'media'
  image?: string
}) {
  const { t } = useTranslation()
  const media = image ?? ('image' in section ? (section as PublicSectionConfig).image : undefined)

  if (variant === 'media' && media) {
    return (
      <Link to={to} className="hp-category-media-card">
        <span className="hp-category-media" style={{ backgroundImage: `url(${media})` }} />
        <span className="hp-category-media-body">
          <span className="hp-category-media-icon" aria-hidden="true">{section.icon}</span>
          <h3>{t(section.titleKey)}</h3>
          <p>{t(section.descriptionKey)}</p>
        </span>
      </Link>
    )
  }

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
