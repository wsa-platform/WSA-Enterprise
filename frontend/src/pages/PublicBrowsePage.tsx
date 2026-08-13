import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

type ServiceModule = { key: string; label: string; requires_auth: boolean }

type PublicCatalog = {
  platform: string
  description: string
  service_modules: ServiceModule[]
  public_capabilities: string[]
  protected_capabilities: string[]
}

export function PublicBrowsePage() {
  const { t } = useTranslation()
  const [catalog, setCatalog] = useState<PublicCatalog | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    fetch('/api/v1/public/services')
      .then(async (response) => {
        if (!response.ok) throw new Error('catalog')
        return response.json() as Promise<PublicCatalog>
      })
      .then(setCatalog)
      .catch(() => setError(t('public.catalogError')))
  }, [t])

  return (
    <main className="login-shell">
      <section className="login-card">
        <p className="eyebrow">{t('auth.brand')}</p>
        <h1>{t('public.browseTitle')}</h1>
        <p className="muted">{catalog?.description ?? t('public.browseSubtitle')}</p>
        {error && <p className="error">{error}</p>}
        {catalog && (
          <>
            <ul>
              {catalog.service_modules.map((module) => (
                <li key={module.key}>
                  {module.label} {module.requires_auth ? t('public.requiresSignIn') : t('public.openAccess')}
                </li>
              ))}
            </ul>
            <p className="hint">{t('public.protectedHint')}</p>
          </>
        )}
        <p className="hint">
          <Link to="/register">{t('auth.createAccount')}</Link> · <Link to="/login">{t('common.signIn')}</Link>
        </p>
      </section>
    </main>
  )
}
