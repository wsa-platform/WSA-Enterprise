import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PublicLanguageMenu } from '../../public/PublicLanguageMenu'
import { useAuth } from '../../context/AuthContext'
import {
  JOB_SEEKER_HOME,
  employerStartPath,
  jobSeekerLandingPath,
  jobSeekerStartPath,
  loginHref,
  registerHref,
} from '../../navigation/roleDestinations'
import '../../public/publicSite.css'

export function JobsEnterPage() {
  const { t } = useTranslation()
  const { token } = useAuth()
  const authenticated = Boolean(token)
  const jobSeekerAuth = jobSeekerStartPath(authenticated)
  const employerAuth = employerStartPath(authenticated)
  const loginTo = loginHref('job_seeker', JOB_SEEKER_HOME)
  const registerTo = registerHref('job_seeker', JOB_SEEKER_HOME)

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
            <Link to={loginTo} className="gs-btn gs-btn-ghost">
              {t('website.nav.login')}
            </Link>
            <Link to={registerTo} className="gs-btn gs-btn-primary">
              {t('website.nav.register')}
            </Link>
          </div>
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card entry-choice-card">
          <Link to="/" className="public-auth-back">← {t('website.nav.home')}</Link>
          <JobEntryChoiceContent
            headingLevel="h1"
            jobSeekerTo={jobSeekerAuth}
            employerTo={employerAuth}
          />
        </section>
      </main>
    </div>
  )
}

export function JobEntryChoices() {
  const { token } = useAuth()
  const authenticated = Boolean(token)

  return (
    <div className="entry-choice-banner">
      <JobEntryChoiceContent
        jobSeekerTo={jobSeekerLandingPath(authenticated)}
        employerTo={employerStartPath(authenticated)}
      />
    </div>
  )
}

function JobEntryChoiceContent({
  jobSeekerTo,
  employerTo,
  headingLevel = 'h2',
}: {
  jobSeekerTo: string
  employerTo: string
  headingLevel?: 'h1' | 'h2'
}) {
  const { t } = useTranslation()
  const Heading = headingLevel

  return (
    <>
      <Heading className="entry-choice-headline">{t('auth.entry.headline')}</Heading>
      <div className="entry-choice-grid">
        <Link className="entry-choice entry-choice-seeker" to={jobSeekerTo}>
          <strong>{t('auth.entry.seekOpportunity')}</strong>
          <span>{t('auth.entry.seekOpportunityHint')}</span>
        </Link>
        <Link className="entry-choice entry-choice-employer" to={employerTo}>
          <strong>{t('auth.entry.findTalent')}</strong>
          <span>{t('auth.entry.findTalentHint')}</span>
        </Link>
      </div>
    </>
  )
}
