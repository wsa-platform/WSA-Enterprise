import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink } from 'react-router-dom'
import { PUBLIC_TOP_NAV_ITEMS } from '../navigation/paths'
import { PublicLanguageMenu } from './PublicLanguageMenu'

/** Header — adapted from garden-store/components/Header.tsx */
export function PublicHeader() {
  const { t } = useTranslation()
  const [menuOpen, setMenuOpen] = useState(false)

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
          <Link to="/login" className="gs-btn gs-btn-ghost">
            {t('website.nav.login')}
          </Link>
          <Link to="/register" className="gs-btn gs-btn-primary">
            {t('website.nav.register')}
          </Link>
        </div>
      </div>
    </header>
  )
}
