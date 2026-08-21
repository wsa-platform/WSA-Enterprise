import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PublicLanguageMenu } from '../../public/PublicLanguageMenu'
import { authQuery } from '../../navigation/routeGuards'
import { EMPLOYER_HOME, JOB_SEEKER_HOME } from '../../navigation/roleDestinations'
import '../../public/publicSite.css'

export function JobsEnterPage() {
  const { t } = useTranslation()
  const jobSeekerAuth = `/login${authQuery({ audience: 'job_seeker', next: JOB_SEEKER_HOME })}`
  const employerAuth = `/login${authQuery({ audience: 'employer', next: EMPLOYER_HOME })}`

  return (
    <div className="public-site">
      <header className="public-header">
        <div className="public-header-inner">
          <Link to="/" className="public-brand">
            <span className="public-brand-mark" aria-hidden="true">W</span>
            <span>{t('website.brand')}</span>
          </Link>
          <div className="public-header-actions">
            <PublicLanguageMenu />
          </div>
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card entry-choice-card">
          <Link to="/" className="public-auth-back">← {t('website.nav.home')}</Link>
          <p className="eyebrow">{t('auth.brand')}</p>
          <h1>{t('auth.entry.title')}</h1>
          <p className="muted">{t('auth.entry.subtitle')}</p>
          <div className="entry-choice-grid">
            <Link className="entry-choice" to={jobSeekerAuth}>
              <strong>{t('auth.entry.jobSeeker')}</strong>
              <span>{t('auth.entry.jobSeekerHint')}</span>
            </Link>
            <Link className="entry-choice" to={employerAuth}>
              <strong>{t('auth.entry.employer')}</strong>
              <span>{t('auth.entry.employerHint')}</span>
            </Link>
          </div>
        </section>
      </main>
    </div>
  )
}

export function JobEntryChoices() {
  const { t } = useTranslation()
  const jobSeekerAuth = `/login${authQuery({ audience: 'job_seeker', next: JOB_SEEKER_HOME })}`
  const employerAuth = `/login${authQuery({ audience: 'employer', next: EMPLOYER_HOME })}`

  return (
    <div className="entry-choice-banner">
      <div>
        <h3>{t('auth.entry.title')}</h3>
        <p>{t('auth.entry.subtitle')}</p>
      </div>
      <div className="entry-choice-grid">
        <Link className="entry-choice" to={jobSeekerAuth}>
          <strong>{t('auth.entry.jobSeeker')}</strong>
          <span>{t('auth.entry.jobSeekerHint')}</span>
        </Link>
        <Link className="entry-choice" to={employerAuth}>
          <strong>{t('auth.entry.employer')}</strong>
          <span>{t('auth.entry.employerHint')}</span>
        </Link>
      </div>
    </div>
  )
}
