import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import '../jobs/jobSeekerProfile.css'
import './employerWorkspace.css'

export function EmployerWorkspacePage() {
  const { t } = useTranslation()
  const { user } = useAuth()

  return (
    <div className="job-seeker-profile employer-workspace">
      <header className="page-header">
        <div>
          <p className="eyebrow">{t('auth.employer.navLabel')}</p>
          <h1>{t('auth.employer.title')}</h1>
          <p className="page-description">{t('auth.employer.workspaceIntro')}</p>
        </div>
      </header>
      <section className="panel">
        <div className="js-field js-field-large">
          <span className="js-field-label">{t('auth.employer.account')}</span>
          <div className="js-field-value"><span className="js-field-text">{user?.name} — {user?.email}</span></div>
        </div>
        <div className="form-actions">
          <Link className="js-btn js-btn-primary" to="/employer/search">{t('auth.employer.openSearch')}</Link>
          <Link className="js-btn" to="/employer/notifications">{t('auth.employer.notifications')}</Link>
        </div>
      </section>
    </div>
  )
}
