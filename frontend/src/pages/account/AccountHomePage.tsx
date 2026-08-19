import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PageHeader } from '../../components/PageHeader'
import { usePermissions } from '../../context/PermissionContext'

export function AccountHomePage() {
  const { t } = useTranslation()
  const { can } = usePermissions()
  const canViewProducts = can('market.view') || can('market.create') || can('market.manage_own')

  return (
    <>
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={t('nav.myAccount')}
        description={t('accountPage.description')}
      />

      <section className="panel">
        <Link className="record-card" to="/account/profile">
          <strong>{t('accountPage.profile')}</strong>
          <span>{t('accountPage.profileDescription')}</span>
        </Link>
        {canViewProducts && (
          <Link className="record-card" to="/account/products">
            <strong>{t('accountPage.myProducts')}</strong>
            <span>{t('accountPage.myProductsDescription')}</span>
          </Link>
        )}
      </section>
    </>
  )
}
