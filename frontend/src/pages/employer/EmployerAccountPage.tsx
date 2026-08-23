import { useTranslation } from 'react-i18next'
import { useAuth } from '../../context/AuthContext'
import { JobSeekerField } from '../jobs/JobSeekerField'
import '../jobs/jobSeekerProfile.css'
import './employerWorkspace.css'

export function EmployerAccountPage() {
  const { t } = useTranslation()
  const { user } = useAuth()

  return (
    <div className="job-seeker-profile employer-workspace">
      <header className="page-header">
        <h1>{t('auth.employer.account')}</h1>
      </header>
      <section className="panel">
        <JobSeekerField label={t('common.name')} value={user?.name ?? '—'} />
        <JobSeekerField label={t('common.email')} value={user?.email ?? '—'} />
      </section>
    </div>
  )
}
