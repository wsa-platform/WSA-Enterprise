import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, Outlet, useNavigate } from 'react-router-dom'
import { logout } from '../api'
import { useAuth } from '../context/AuthContext'
import { EMPLOYER_HOME, employerLogoutPath } from '../navigation/roleDestinations'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function EmployerShell() {
  const { t } = useTranslation()
  const { user, token, clearSession } = useAuth()
  const navigate = useNavigate()
  const [signingOut, setSigningOut] = useState(false)

  const handleLogout = async () => {
    setSigningOut(true)
    const currentToken = token
    navigate(employerLogoutPath(), { replace: true })
    if (currentToken) await logout(currentToken).catch(() => undefined)
    clearSession()
  }

  return (
    <div className="employer-shell">
      <header className="job-seeker-shell-header">
        <Link to={EMPLOYER_HOME} className="public-brand">
          <span className="public-brand-mark" aria-hidden="true">W</span>
          <span>{t('website.brand')}</span>
        </Link>
        <nav className="job-seeker-shell-nav" aria-label={t('auth.employer.navLabel')}>
          <Link to={EMPLOYER_HOME}>{t('auth.employer.home')}</Link>
          <Link to="/employer/search">{t('auth.employer.search')}</Link>
          <Link to="/employer/notifications">{t('auth.employer.notifications')}</Link>
          <Link to="/employer/account">{t('auth.employer.account')}</Link>
        </nav>
        <div className="job-seeker-shell-actions">
          <PublicLanguageMenu />
          <span className="job-seeker-shell-user">{user?.name}</span>
          <button type="button" className="gs-btn gs-btn-ghost" disabled={signingOut} onClick={() => void handleLogout()}>
            {t('common.signOut')}
          </button>
        </div>
      </header>
      <main className="job-seeker-shell-main">
        <Outlet />
      </main>
    </div>
  )
}
