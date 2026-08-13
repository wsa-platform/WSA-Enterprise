import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PublicLayout } from '../../public/PublicLayout'

type InfoPageKey = 'about' | 'privacy' | 'terms' | 'contact'

const PAGE_KEYS: Record<InfoPageKey, { title: string; paragraphs: string[] }> = {
  about: {
    title: 'website.info.about.title',
    paragraphs: ['website.info.about.p1', 'website.info.about.p2', 'website.info.about.p3'],
  },
  privacy: {
    title: 'website.info.privacy.title',
    paragraphs: ['website.info.privacy.p1', 'website.info.privacy.p2', 'website.info.privacy.p3'],
  },
  terms: {
    title: 'website.info.terms.title',
    paragraphs: ['website.info.terms.p1', 'website.info.terms.p2', 'website.info.terms.p3'],
  },
  contact: {
    title: 'website.info.contact.title',
    paragraphs: ['website.info.contact.p1', 'website.info.contact.p2', 'website.info.contact.p3'],
  },
}

export function InfoPage({ page }: { page: InfoPageKey }) {
  const { t } = useTranslation()
  const config = PAGE_KEYS[page]

  return (
    <PublicLayout>
      <article className="public-info-page">
        <Link to="/" className="public-auth-back">
          ← {t('website.nav.home')}
        </Link>
        <h1>{t(config.title)}</h1>
        {config.paragraphs.map((key) => (
          <p key={key}>{t(key)}</p>
        ))}
        <div className="public-cta-banner">
          <div>
            <h3>{t('website.cta.joinTitle')}</h3>
            <p>{t('website.cta.joinBody')}</p>
          </div>
          <Link to="/register" className="gs-btn gs-btn-primary">
            {t('website.nav.register')}
          </Link>
        </div>
      </article>
    </PublicLayout>
  )
}
