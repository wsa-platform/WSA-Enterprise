import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { PageHeader } from '../../components/PageHeader'
import { usePermissions } from '../../context/PermissionContext'
import { internalPaths, publicPaths } from '../../navigation/paths'

export function AccountHomePage() {
  const { t } = useTranslation()
  const { can, loading } = usePermissions()
  const canViewProducts = can('market.view') || can('market.create') || can('market.manage_own')
  const canCreate = can('market.create')

  return (
    <>
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={t('nav.myAccount')}
        description={t('accountPage.description')}
      />

      <section className="panel account-nav-grid">
        <Link className="account-nav-card" to={internalPaths.profile}>
          <strong>{t('accountPage.profile')}</strong>
          <span>{t('accountPage.profileDescription')}</span>
        </Link>
        {loading ? (
          <p className="loading">{t('errors.checkingAccess')}</p>
        ) : (
          <>
            {canViewProducts && (
              <Link className="account-nav-card" to={internalPaths.products}>
                <strong>{t('accountPage.myProducts')}</strong>
                <span>{t('accountPage.myProductsDescription')}</span>
              </Link>
            )}
            {canCreate && (
              <Link className="account-nav-card" to={internalPaths.newProduct}>
                <strong>{t('accountPage.addProduct')}</strong>
                <span>{t('accountPage.addProductDescription')}</span>
              </Link>
            )}
          </>
        )}
        <Link className="account-nav-card" to={publicPaths.market}>
          <strong>{t('accountPage.productMarket')}</strong>
          <span>{t('accountPage.productMarketDescription')}</span>
        </Link>
      </section>
    </>
  )
}
