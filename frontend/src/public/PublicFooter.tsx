import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import { publicLoginHref, publicRegisterHref, readStoredAudience } from '../navigation/roleDestinations'
import { PUBLIC_SECTIONS } from './sections'

/** Footer — adapted from garden-store/components/Footer.tsx */
export function PublicFooter() {
  const { t } = useTranslation()
  const { pathname } = useLocation()
  const storedAudience = readStoredAudience()
  const loginTo = publicLoginHref(storedAudience, pathname)
  const registerTo = publicRegisterHref(storedAudience, pathname)

  return (
    <footer className="gs-footer">
      <div className="gs-container gs-footer-main">
        <div className="gs-footer-grid">
          <div className="gs-footer-brand">
            <div className="gs-footer-logo">
              <span className="gs-brand-mark" aria-hidden="true">
                🌿
              </span>
              <div>
                <strong>{t('website.brand')}</strong>
                <small>{t('website.footer.tagline')}</small>
              </div>
            </div>
            <p>{t('website.footer.tagline')}</p>
          </div>

          <nav aria-label={t('website.quickLinks.title')}>
            <h4>{t('website.quickLinks.title')}</h4>
            <ul>
              <li><Link to="/">{t('website.nav.home')}</Link></li>
              <li><Link to="/about">{t('website.footer.about')}</Link></li>
              <li><Link to="/privacy">{t('website.footer.privacy')}</Link></li>
              <li><Link to="/terms">{t('website.footer.terms')}</Link></li>
              <li><Link to="/contact">{t('website.footer.contact')}</Link></li>
            </ul>
          </nav>

          <nav aria-label={t('website.footer.sections')}>
            <h4>{t('website.footer.sections')}</h4>
            <ul>
              {PUBLIC_SECTIONS.map((section) => (
                <li key={section.id}>
                  <Link to={`/sections/${section.id}`}>{t(section.titleKey)}</Link>
                </li>
              ))}
            </ul>
          </nav>

          <nav aria-label={t('website.footer.account')}>
            <h4>{t('website.footer.account')}</h4>
            <ul>
              <li><Link to={loginTo}>{t('website.nav.login')}</Link></li>
              <li><Link to={registerTo}>{t('website.nav.register')}</Link></li>
            </ul>
          </nav>
        </div>
      </div>

      <div className="gs-footer-bar">
        <div className="gs-container gs-footer-bar-inner">
          <p>{t('website.footer.rights')}</p>
          <div className="gs-footer-bar-links">
            <Link to="/privacy">{t('website.footer.privacy')}</Link>
            <Link to="/terms">{t('website.footer.terms')}</Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
