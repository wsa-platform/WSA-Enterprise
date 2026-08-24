import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useLocation } from 'react-router-dom'
import { deleteListing, fetchMyListings, submitListing, unpublishListing, type OwnerListing } from '../../api/marketplace'
import { PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { translateApiError } from '../../i18n/apiErrors'
import { countryDisplayName } from '../../marketplace/isoCountries'
import { availabilityI18nKey, formatQuantity, primaryListingImage } from '../../marketplace/productDisplay'
import { internalPaths, publicPaths } from '../../navigation/paths'

export function MyListingsPage() {
  const { t, i18n } = useTranslation()
  const location = useLocation()
  const { token, organizationId } = useAuth()
  const { can, loading: permissionsLoading } = usePermissions()
  const [page, setPage] = useState(1)
  const [notice, setNotice] = useState('')
  const canCreate = can('market.create')
  const canManage = can('market.manage_own') || can('market.manage_all') || canCreate
  const language = i18n.language ?? 'ar'

  const { data: payload, loading, error, reload } = useAsyncData(async () => {
    if (!token || !can('market.view')) return null
    return fetchMyListings(token, organizationId ?? undefined, page)
  }, [token, organizationId, page, can])

  useEffect(() => {
    const key = (location.state as { productNotice?: string } | null)?.productNotice
    if (key === 'created') setNotice(t('market.created'))
    if (key === 'updated') setNotice(t('market.updated'))
  }, [location.state, t])

  if (permissionsLoading) {
    return <p className="loading">{t('errors.checkingAccess')}</p>
  }

  if (!can('market.view')) {
    return <ErrorBanner message={t('market.noPermissionView')} />
  }

  const runAction = async (listing: OwnerListing, action: 'submit' | 'delete' | 'hide') => {
    if (!token) return
    setNotice('')
    try {
      if (action === 'delete') {
        if (!window.confirm(t('market.confirmDelete'))) return
        await deleteListing(token, listing.id, organizationId ?? undefined)
        setNotice(t('market.deleted'))
      } else if (action === 'hide') {
        if (!window.confirm(t('market.confirmHide'))) return
        await unpublishListing(token, listing.id, organizationId ?? undefined)
        setNotice(t('market.hidden'))
      } else {
        await submitListing(token, listing.id, organizationId ?? undefined)
        setNotice(t('market.submitted'))
      }
      await reload()
    } catch (requestError) {
      setNotice(translateApiError(requestError) || t('market.actionFailed'))
    }
  }

  const categoryLabel = (listing: OwnerListing) => {
    const category = listing.category
    if (!category) return null
    return language.startsWith('ar') && category.name_ar ? category.name_ar : category.name ?? null
  }

  const unitLabel = (listing: OwnerListing) => {
    const unit = listing.unit
    if (!unit) return null
    return language.startsWith('ar') && unit.name_ar ? unit.name_ar : unit.name ?? unit.slug ?? null
  }

  return (
    <>
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={t('accountPage.myProducts')}
        description={t('accountPage.myProductsDescription')}
        actions={(
          <span className="header-actions">
            <Link className="link-button" to={publicPaths.market}>{t('nav.productMarket')}</Link>
            {canCreate && (
              <Link className="refresh" to={internalPaths.newProduct}>{t('nav.addProduct')}</Link>
            )}
          </span>
        )}
      />

      {error && <ErrorBanner message={error} onRetry={reload} />}
      {notice && <p className={`notice ${notice === t('market.actionFailed') ? '' : 'success'}`.trim()}>{notice}</p>}

      <section className="panel">
        {loading ? (
          <p className="loading">{t('market.loadingListings')}</p>
        ) : (payload?.data.length ?? 0) === 0 ? (
          <EmptyState
            title={t('market.noListings')}
            description={t('market.noListingsDescription')}
            action={canCreate ? <Link className="refresh" to={internalPaths.newProduct}>{t('nav.addProduct')}</Link> : undefined}
          />
        ) : (
          <>
            <div className="seller-product-list">
              {(payload?.data ?? []).map((row) => {
                const image = primaryListingImage(row)
                const availabilityKey = availabilityI18nKey(row.availability)
                const quantity = formatQuantity(row.available_quantity)
                const origin = countryDisplayName(row.origin_country, language)
                const sellerCountry = countryDisplayName(row.country ?? row.seller_country, language)
                return (
                  <article key={row.id} className="seller-product-card">
                    {image
                      ? <img src={image} alt={row.title} />
                      : <div className="seller-product-photo-fallback" aria-hidden="true" />}
                    <div>
                      <h3 dir="auto">{row.title}</h3>
                      <StatusBadge status={row.status ?? 'draft'} />
                      <ul className="seller-product-meta">
                        {categoryLabel(row) && <li>{t('market.category')}: {categoryLabel(row)}</li>}
                        {sellerCountry && <li>{t('market.sellerCountry')}: {sellerCountry}</li>}
                        {row.city && <li>{t('market.city')}: {row.city}</li>}
                        {row.seller_region && <li>{t('market.sellerRegion')}: {row.seller_region}</li>}
                        {origin && <li>{t('market.originCountry')}: {origin}</li>}
                        {availabilityKey && <li>{t('market.availabilityLabel')}: {t(availabilityKey)}</li>}
                        {unitLabel(row) && <li>{t('market.unit')}: {unitLabel(row)}</li>}
                        {quantity && <li>{t('market.availableQuantity')}: {quantity}</li>}
                        {row.wholesale ? <li>{t('market.wholesale')}</li> : null}
                        {row.retail ? <li>{t('market.retail')}</li> : null}
                        {row.export_ready ? <li>{t('market.exportReady')}</li> : null}
                      </ul>
                    </div>
                    {canManage ? (
                      <span className="seller-product-actions">
                        {(row.status === 'draft' || row.status === 'rejected' || row.status === 'unpublished') && (
                          <button type="button" className="link-button" onClick={() => void runAction(row, 'submit')}>
                            {t('market.publishProduct')}
                          </button>
                        )}
                        {row.status === 'published' && (
                          <>
                            <Link className="link-button" to={publicPaths.listing(row.id)}>{t('market.viewPublicListing')}</Link>
                            <button type="button" className="link-button" onClick={() => void runAction(row, 'hide')}>
                              {t('market.hideProduct')}
                            </button>
                          </>
                        )}
                        <Link className="link-button" to={internalPaths.editProduct(row.id)}>
                          {t('common.edit')}
                        </Link>
                        <button type="button" className="link-button" onClick={() => void runAction(row, 'delete')}>
                          {t('common.delete')}
                        </button>
                      </span>
                    ) : null}
                  </article>
                )
              })}
            </div>
            {payload && (
              <PaginationBar page={payload.current_page} lastPage={payload.last_page} total={payload.total} onPageChange={setPage} />
            )}
          </>
        )}
      </section>
    </>
  )
}
