import { useTranslation } from 'react-i18next'
import { Link, Navigate, useNavigate, useParams } from 'react-router-dom'
import { fetchMyListing } from '../../api/marketplace'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { internalPaths, publicPaths } from '../../navigation/paths'
import { marketplaceLoginHref, sellerCreatePageGate } from '../../navigation/roleDestinations'
import { SellerProductForm } from './SellerProductForm'

export function ListingEditorPage() {
  const { t } = useTranslation()
  const { token, organizationId, user } = useAuth()
  const { can, loading: permissionsLoading } = usePermissions()
  const navigate = useNavigate()
  const { listingId } = useParams()
  const numericId = listingId ? Number(listingId) : NaN
  const isNew = !Number.isFinite(numericId)
  const canManage = can('market.create') || can('market.manage_own') || can('market.manage_all')

  const { data: listing, loading, error, reload } = useAsyncData(async () => {
    if (!token || isNew || !numericId) return null
    return fetchMyListing(token, numericId, organizationId ?? undefined)
  }, [token, organizationId, numericId, isNew])

  if (isNew) {
    const createGate = sellerCreatePageGate({
      authenticated: Boolean(token),
      permissionsLoading,
      canCreate: can('market.create'),
    })
    if (createGate === 'login') {
      return <Navigate to={marketplaceLoginHref(internalPaths.newProduct)} replace />
    }
    if (createGate === 'loading') {
      return <p className="loading">{t('errors.checkingAccess')}</p>
    }
  } else {
    if (!token) {
      return <Navigate to={marketplaceLoginHref(internalPaths.editProduct(numericId))} replace />
    }
    if (permissionsLoading) {
      return <p className="loading">{t('errors.checkingAccess')}</p>
    }
    if (!can('market.view')) {
      return <ErrorBanner message={t('market.noPermissionView')} />
    }
  }

  const editable = isNew || listing?.status === 'draft' || listing?.status === 'rejected' || listing?.status === 'unpublished'

  if (!isNew && !loading && !listing) {
    return (
      <div className="listing-editor">
        <PageHeader
          eyebrow={t('nav.myAccount')}
          title={t('market.editProduct')}
          actions={<Link className="gs-btn gs-btn-ghost" to={internalPaths.products}>{t('market.backToListings')}</Link>}
        />
        {error ? <ErrorBanner message={error} onRetry={reload} /> : <EmptyState title={t('errors.notFound')} description={t('market.loadingListing')} />}
      </div>
    )
  }

  return (
    <div className="listing-editor">
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={isNew ? t('market.addProduct') : t('market.editProduct')}
        description={t('market.editorDescription')}
        actions={(
          <span className="header-actions">
            <Link className="gs-btn gs-btn-ghost" to={internalPaths.products}>{t('market.backToListings')}</Link>
            {listing?.status === 'published' && (
              <Link className="gs-btn gs-btn-ghost" to={publicPaths.listing(listing.id)}>{t('market.viewPublicListing')}</Link>
            )}
          </span>
        )}
      />

      {error && <ErrorBanner message={error} onRetry={reload} />}
      {loading && !isNew ? (
        <p className="loading">{t('market.loadingListing')}</p>
      ) : (
        <SellerProductForm
          listing={isNew ? null : listing}
          token={token}
          organizationId={organizationId}
          sellerDisplayName={user?.name}
          saveLabel={t('common.save')}
          cancelLabel={t('common.cancel')}
          showSubmitForReview={canManage && editable}
          readOnly={!canManage || !editable}
          onCancel={() => navigate(internalPaths.products)}
          onSaved={(_saved, kind) => {
            navigate(internalPaths.products, { replace: true, state: { productNotice: kind === 'created' ? 'created' : 'updated' } })
          }}
        />
      )}
    </div>
  )
}
