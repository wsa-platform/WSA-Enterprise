import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PublicLanguageMenu } from '../../public/PublicLanguageMenu'
import '../../public/publicSite.css'

export function EmployerBlockedPage({
  asJobSeeker = false,
  message,
}: {
  asJobSeeker?: boolean
  message?: string
}) {
  const { t } = useTranslation()
  const detail = message ?? (asJobSeeker ? t('auth.employer.blockedEmployer') : t('auth.employer.blockedJobSeeker'))
  const backTo = asJobSeeker ? '/' : '/employer/enter'
  const backLabel = asJobSeeker ? t('website.nav.home') : t('auth.employer.back')

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
        <section className="public-auth-card">
          <h1>{t('auth.employer.blockedTitle')}</h1>
          <p role="alert">{detail}</p>
          <Link to={backTo} className="gs-btn gs-btn-primary">{backLabel}</Link>
        </section>
      </main>
    </div>
  )
}
