import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import { publicLoginHref, publicRegisterHref, readStoredAudience } from '../navigation/roleDestinations'
import { publicPaths } from '../navigation/paths'
import { PUBLIC_SECTIONS } from './sections'

/** Quick links band below newsletter — preserves existing destinations, modern layout. */
export function PublicQuickLinks() {
  const { t } = useTranslation()
  const { pathname } = useLocation()
  const storedAudience = readStoredAudience()
  const loginTo = publicLoginHref(storedAudience, pathname)
  const registerTo = publicRegisterHref(storedAudience, pathname)

  return (
    <section className="gs-quick-links hp-quick-links" aria-labelledby="quick-links-title">
      <div className="gs-container">
        <div className="hp-quick-links-intro">
          <h2 id="quick-links-title">{t('website.quickLinks.title')}</h2>
          <p>{t('website.quickLinks.subtitle')}</p>
        </div>
        <div className="gs-quick-links-grid hp-quick-links-grid">
          <nav aria-label={t('website.quickLinks.sections')}>
            <h3>{t('website.quickLinks.sections')}</h3>
            <ul>
              {PUBLIC_SECTIONS.map((section) => (
                <li key={section.id}>
                  <Link to={`/sections/${section.id}`}>{t(section.titleKey)}</Link>
                </li>
              ))}
              <li>
                <Link to={publicPaths.market}>{t('website.nav.productMarket')}</Link>
              </li>
            </ul>
          </nav>
          <nav aria-label={t('website.quickLinks.info')}>
            <h3>{t('website.quickLinks.info')}</h3>
            <ul>
              <li><Link to="/about">{t('website.footer.about')}</Link></li>
              <li><Link to="/privacy">{t('website.footer.privacy')}</Link></li>
              <li><Link to="/terms">{t('website.footer.terms')}</Link></li>
              <li><Link to="/contact">{t('website.footer.contact')}</Link></li>
              <li><Link to="/jobs/enter">{t('website.homePanels.jobsTitle')}</Link></li>
              <li><Link to={loginTo}>{t('website.nav.login')}</Link></li>
              <li><Link to={registerTo}>{t('website.nav.register')}</Link></li>
            </ul>
          </nav>
        </div>
      </div>
    </section>
  )
}
