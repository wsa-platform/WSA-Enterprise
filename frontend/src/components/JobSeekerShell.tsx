import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, Outlet, useNavigate } from 'react-router-dom'
import { logout } from '../api'
import { useAuth } from '../context/AuthContext'
import { usePermissions } from '../context/PermissionContext'
import { JOB_SEEKER_HOME } from '../navigation/roleDestinations'
import { PublicLanguageMenu } from '../public/PublicLanguageMenu'
import '../public/publicSite.css'

export function JobSeekerShell() {
  const { t } = useTranslation()
  const { user, token, clearSession } = useAuth()
  const { can } = usePermissions()
  const navigate = useNavigate()
  const [signingOut, setSigningOut] = useState(false)
  const showTalent = can('jobs.talent.register') || can('jobs.talent.manage')

  const handleLogout = async () => {
    setSigningOut(true)
    const currentToken = token
    navigate('/jobs/enter', { replace: true })
    if (currentToken) await logout(currentToken).catch(() => undefined)
    clearSession()
  }

  return (
    <div className="job-seeker-shell">
      <header className="job-seeker-shell-header">
        <Link to={JOB_SEEKER_HOME} className="public-brand">
          <span className="public-brand-mark" aria-hidden="true">W</span>
          <span>{t('website.brand')}</span>
        </Link>
        <nav className="job-seeker-shell-nav" aria-label={t('auth.jobSeeker.navLabel')}>
          <Link to={JOB_SEEKER_HOME}>{t('jobs.myApplicationTitle')}</Link>
          {showTalent ? <Link to="/jobs/talent">{t('nav.talentProfile')}</Link> : null}
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
