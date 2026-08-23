import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PublicLanguageMenu } from '../../public/PublicLanguageMenu'
import { JOB_SEEKER_HOME, loginHref, registerHref } from '../../navigation/roleDestinations'
import '../../public/publicSite.css'

export function JobSeekerEnterPage() {
  const { t } = useTranslation()
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
          <PublicLanguageMenu />
        </div>
      </header>
      <main className="public-auth-shell">
        <section className="public-auth-card entry-choice-card">
          <Link to="/" className="public-auth-back">← {t('website.nav.home')}</Link>
          <h1>{t('auth.entry.seekOpportunity')}</h1>
          <p>{t('auth.jobSeeker.loginSubtitle')}</p>
          <div className="entry-choice-grid">
            <Link className="gs-btn gs-btn-primary" to={loginTo}>{t('auth.jobSeeker.signIn')}</Link>
            <Link className="gs-btn gs-btn-ghost" to={registerTo}>{t('auth.jobSeeker.createAccount')}</Link>
          </div>
        </section>
      </main>
    </div>
  )
}
