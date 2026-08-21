import { useTranslation } from 'react-i18next'
import { PageHeader } from '../../components/PageHeader'

export function EmployerEntryPage() {
  const { t } = useTranslation()

  return (
    <>
      <PageHeader
        eyebrow={t('auth.employer.home')}
        title={t('auth.employer.title')}
        description={t('auth.employer.placeholder')}
      />
      <section className="panel">
        <p className="muted">{t('auth.employer.placeholderBody')}</p>
      </section>
    </>
  )
}
