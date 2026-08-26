import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom'
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
import { PUBLIC_TOP_NAV_ITEMS, internalPaths, publicPaths } from '../navigation/paths'
import { PublicLanguageMenu } from './PublicLanguageMenu'

/** Public site header — matches approved agricultural homepage chrome. */
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
  const navigate = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')
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

  const onSearch = (event: FormEvent) => {
    event.preventDefault()
    setMenuOpen(false)
    navigate(publicPaths.market)
    setSearchQuery('')
  }

  return (
    <header className="gs-header hp-header hp-header--design">
      <div className="gs-container gs-header-inner">
        <Link to="/" className="gs-brand" onClick={() => setMenuOpen(false)}>
          <span className="gs-brand-mark" aria-hidden="true">🌿</span>
          <span className="gs-brand-text">
            <strong>{t('website.brand')}</strong>
            <small>{t('website.brandTagline')}</small>
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
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              onClick={() => setMenuOpen(false)}
            >
              {t(item.labelKey)}
            </NavLink>
          ))}
        </nav>

        <form className="hp-header-search" role="search" onSubmit={onSearch}>
          <label className="visually-hidden" htmlFor="public-header-search">
            {t('website.nav.search')}
          </label>
          <input
            id="public-header-search"
            type="search"
            value={searchQuery}
            onChange={(event) => setSearchQuery(event.target.value)}
            placeholder={t('website.nav.searchPlaceholder')}
          />
          <button type="submit" aria-label={t('website.nav.search')}>
            🔍
          </button>
        </form>

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
