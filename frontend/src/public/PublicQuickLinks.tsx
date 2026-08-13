import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PUBLIC_SECTIONS } from './sections'

/** Quick links band — garden-store Footer quick-links visual language */
export function PublicQuickLinks() {
  const { t } = useTranslation()

  return (
    <section className="gs-quick-links" aria-labelledby="quick-links-title">
      <div className="gs-container">
        <h2 id="quick-links-title">{t('website.quickLinks.title')}</h2>
        <div className="gs-quick-links-grid">
          <nav aria-label={t('website.quickLinks.sections')}>
            <h3>{t('website.quickLinks.sections')}</h3>
            <ul>
              {PUBLIC_SECTIONS.map((section) => (
                <li key={section.id}>
                  <Link to={`/sections/${section.id}`}>{t(section.titleKey)}</Link>
                </li>
              ))}
            </ul>
          </nav>
          <nav aria-label={t('website.quickLinks.info')}>
            <h3>{t('website.quickLinks.info')}</h3>
            <ul>
              <li><Link to="/about">{t('website.footer.about')}</Link></li>
              <li><Link to="/privacy">{t('website.footer.privacy')}</Link></li>
              <li><Link to="/terms">{t('website.footer.terms')}</Link></li>
              <li><Link to="/contact">{t('website.footer.contact')}</Link></li>
              <li><Link to="/login">{t('website.nav.login')}</Link></li>
              <li><Link to="/register">{t('website.nav.register')}</Link></li>
            </ul>
          </nav>
        </div>
      </div>
    </section>
  )
}
