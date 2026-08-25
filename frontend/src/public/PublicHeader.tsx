import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import {
  EMPLOYER_HOME,
  JOB_SEEKER_HOME,
  isMarketplacePath,
  publicHeaderAudience,
  publicLoginHref,
  publicRegisterHref,
  readStoredAudience,
} from '../navigation/roleDestinations'
import { PUBLIC_TOP_NAV_ITEMS, internalPaths } from '../navigation/paths'
import { PublicLanguageMenu } from './PublicLanguageMenu'

/** Header — adapted from garden-store/components/Header.tsx */
export function PublicHeader({
  loginTo: loginToOverride,
  registerTo: registerToOverride,
}: {
  loginTo?: string
  registerTo?: string
} = {}) {
  const { t } = useTranslation()
  const { token } = useAuth()
  const { pathname } = useLocation()
  const [menuOpen, setMenuOpen] = useState(false)
  const authenticated = Boolean(token)
  const storedAudience = readStoredAudience()
  const audience = publicHeaderAudience(storedAudience, pathname)
  const authenticatedHome = audience === 'employer'
    ? EMPLOYER_HOME
    : isMarketplacePath(pathname)
      ? internalPaths.products
      : JOB_SEEKER_HOME
  const loginTo = !authenticated && loginToOverride
    ? loginToOverride
    : authenticated
      ? authenticatedHome
      : publicLoginHref(storedAudience, pathname)
  const registerTo = !authenticated && registerToOverride
    ? registerToOverride
    : authenticated
      ? authenticatedHome
      : publicRegisterHref(storedAudience, pathname)
  const marketplaceAccount = authenticated && isMarketplacePath(pathname)

  return (
    <header className="gs-header">
      <div className="gs-container gs-header-inner">
        <Link to="/" className="gs-brand" onClick={() => setMenuOpen(false)}>
          <span className="gs-brand-mark" aria-hidden="true">
            🌿
          </span>
          <span className="gs-brand-text">
            <strong>{t('website.brand')}</strong>
            <small>{t('website.footer.tagline')}</small>
          </span>
        </Link>

        <button
          type="button"
          className="gs-mobile-toggle"
          aria-expanded={menuOpen}
          aria-controls="public-primary-nav"
          onClick={() => setMenuOpen((open) => !open)}
        >
          {menuOpen ? '✕' : '☰'}
        </button>

        <nav
          id="public-primary-nav"
          className={`gs-nav ${menuOpen ? 'open' : ''}`}
          aria-label={t('website.nav.primary')}
        >
          {PUBLIC_TOP_NAV_ITEMS.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.end} onClick={() => setMenuOpen(false)}>
              {t(item.labelKey)}
            </NavLink>
          ))}
        </nav>

        <div className="gs-header-actions">
          <PublicLanguageMenu />
          {marketplaceAccount ? (
            <Link to={internalPaths.products} className="gs-btn gs-btn-primary">
              {t('nav.myProducts')}
            </Link>
          ) : (
            <>
              <Link to={loginTo} className="gs-btn gs-btn-ghost">
                {t('website.nav.login')}
              </Link>
              <Link to={registerTo} className="gs-btn gs-btn-primary">
                {t('website.nav.register')}
              </Link>
            </>
          )}
        </div>
      </div>
    </header>
  )
}
