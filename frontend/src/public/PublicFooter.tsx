import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import { publicLoginHref, publicRegisterHref, readStoredAudience } from '../navigation/roleDestinations'
import { publicPaths } from '../navigation/paths'
import { PUBLIC_SECTIONS } from './sections'

/** Footer — modern agricultural platform with existing real destinations only. */
export function PublicFooter() {
  const { t } = useTranslation()
  const { pathname } = useLocation()
  const storedAudience = readStoredAudience()
  const loginTo = publicLoginHref(storedAudience, pathname)
  const registerTo = publicRegisterHref(storedAudience, pathname)
  const serviceSections = PUBLIC_SECTIONS.filter((section) => section.id !== 'store' && section.id !== 'jobs')
  const resourceSections = PUBLIC_SECTIONS.filter((section) => (
    section.id === 'training' || section.id === 'small-projects' || section.id === 'store'
  ))

  return (
    <footer className="gs-footer hp-footer">
      <div className="gs-container gs-footer-main">
        <div className="gs-footer-grid hp-footer-grid">
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

          <nav aria-label={t('website.footer.groups.platform')}>
            <h4>{t('website.footer.groups.platform')}</h4>
            <ul>
              <li><Link to="/">{t('website.nav.home')}</Link></li>
              <li><Link to="/about">{t('website.nav.aboutPlatform')}</Link></li>
              <li><Link to="/contact">{t('website.footer.contact')}</Link></li>
            </ul>
          </nav>

          <nav aria-label={t('website.footer.groups.market')}>
            <h4>{t('website.footer.groups.market')}</h4>
            <ul>
              <li><Link to={publicPaths.market}>{t('website.nav.productMarket')}</Link></li>
              <li><Link to="/sections/store">{t('website.sections.store.title')}</Link></li>
            </ul>
          </nav>

          <nav aria-label={t('website.footer.groups.services')}>
            <h4>{t('website.footer.groups.services')}</h4>
            <ul>
              {serviceSections.slice(0, 6).map((section) => (
                <li key={section.id}>
                  <Link to={`/sections/${section.id}`}>{t(section.titleKey)}</Link>
                </li>
              ))}
            </ul>
          </nav>

          <nav aria-label={t('website.footer.groups.resources')}>
            <h4>{t('website.footer.groups.resources')}</h4>
            <ul>
              {resourceSections.map((section) => (
                <li key={section.id}>
                  <Link to={`/sections/${section.id}`}>{t(section.titleKey)}</Link>
                </li>
              ))}
              <li><Link to="/library">{t('website.services.libraryGuides')}</Link></li>
            </ul>
          </nav>

          <nav aria-label={t('website.footer.groups.support')}>
            <h4>{t('website.footer.groups.support')}</h4>
            <ul>
              <li><Link to="/privacy">{t('website.footer.privacy')}</Link></li>
              <li><Link to="/terms">{t('website.footer.terms')}</Link></li>
              <li><Link to={loginTo}>{t('website.nav.login')}</Link></li>
              <li><Link to={registerTo}>{t('website.nav.register')}</Link></li>
            </ul>
          </nav>

          <nav aria-label={t('website.footer.groups.contact')}>
            <h4>{t('website.footer.groups.contact')}</h4>
            <ul>
              <li><Link to="/contact">{t('website.footer.contact')}</Link></li>
              <li><Link to="/jobs/enter">{t('website.homePanels.jobsTitle')}</Link></li>
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
