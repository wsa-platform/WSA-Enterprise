import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PublicLanguageMenu } from '../../public/PublicLanguageMenu'
import { loginHref, employerCreateAccountHref, EMPLOYER_HOME } from '../../navigation/roleDestinations'
import '../../public/publicSite.css'

export function EmployerEnterPage() {
  const { t } = useTranslation()
  const loginTo = loginHref('employer', EMPLOYER_HOME)
  const registerTo = employerCreateAccountHref()

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
          <h1>{t('auth.employer.enterTitle')}</h1>
          <p>{t('auth.employer.enterSubtitle')}</p>
          <div className="entry-choice-grid">
            <Link className="gs-btn gs-btn-primary" to={loginTo}>{t('common.signIn')}</Link>
            <Link className="gs-btn gs-btn-ghost" to={registerTo}>{t('auth.employer.registerTitle')}</Link>
          </div>
        </section>
      </main>
    </div>
  )
}
