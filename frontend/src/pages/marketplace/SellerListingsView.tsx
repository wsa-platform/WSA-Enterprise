import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import type { OwnerListing } from '../../api/marketplace'
import { PaginationBar } from '../../components/DataTable'
import { PageHeader } from '../../components/PageHeader'
import { ConfirmDialog } from '../../components/ConfirmDialog'
import { ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { countryDisplayName } from '../../marketplace/isoCountries'
import { availabilityI18nKey, formatQuantity, primaryListingImage } from '../../marketplace/productDisplay'
import { publicPaths } from '../../navigation/paths'
import {
  canPublishListing,
  isEditorOpen,
  type SellerEditorState,
} from './sellerListingsActions'

export function AddProductButton({
  onClick,
  className = 'gs-btn gs-btn-primary',
}: {
  onClick: () => void
  className?: string
}) {
  const { t } = useTranslation()
  return (
    <button type="button" className={className} onClick={onClick}>
      {t('nav.addProduct')}
    </button>
  )
}

export function SellerListingsView({
  listings,
  loading,
  error,
  onRetry,
  page,
  lastPage,
  total,
  onPageChange,
  editor,
  form,
  pendingDelete,
  notice,
  noticeIsError,
  language,
  onAddProduct,
  onEditProduct,
  onRequestDelete,
  onCancelDelete,
  onConfirmDelete,
  onHide,
  onPublish,
  busy = false,
}: {
  listings: OwnerListing[]
  loading: boolean
  error?: string
  onRetry: () => void
  page: number
  lastPage: number
  total: number
  onPageChange: (page: number) => void
  editor: SellerEditorState
  form: ReactNode
  pendingDelete: OwnerListing | null
  notice: string
  noticeIsError: boolean
  language: string
  onAddProduct: () => void
  onEditProduct: (listing: OwnerListing) => void
  onRequestDelete: (listing: OwnerListing) => void
  onCancelDelete: () => void
  onConfirmDelete: () => void
  onHide: (listing: OwnerListing) => void
  onPublish: (listing: OwnerListing) => void
  busy?: boolean
}) {
  const { t } = useTranslation()
  const editorOpen = isEditorOpen(editor)

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
    <div className="seller-listings" dir={language.startsWith('ar') ? 'rtl' : undefined}>
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={t('accountPage.myProducts')}
        description={t('accountPage.myProductsDescription')}
        actions={(
          <>
            <Link className="gs-btn gs-btn-ghost" to={publicPaths.market}>{t('nav.productMarket')}</Link>
            <AddProductButton onClick={onAddProduct} />
          </>
        )}
      />

      {error && <ErrorBanner message={error} onRetry={onRetry} />}
      {notice && (
        <p className={`notice ${noticeIsError ? '' : 'success'}`.trim()} role="alert">
          {notice}
        </p>
      )}

      {editorOpen ? (
        <div className="listing-editor seller-inline-editor">
          {form}
        </div>
      ) : (
        <section className="panel">
          {loading ? (
            <p className="loading">{t('market.loadingListings')}</p>
          ) : listings.length === 0 ? (
            <div className="empty-state seller-empty-products">
              <strong>{t('market.noListings')}</strong>
              <AddProductButton onClick={onAddProduct} />
              <p>{t('market.noListingsDescription')}</p>
            </div>
          ) : (
            <>
              <div className="seller-product-list">
                {listings.map((row) => {
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
                      <span className="seller-product-actions">
                        {canPublishListing(row.status) && (
                          <button type="button" className="gs-btn gs-btn-ghost" disabled={busy} onClick={() => onPublish(row)}>
                            {t('market.publish')}
                          </button>
                        )}
                        {row.status === 'published' && (
                          <>
                            <Link className="gs-btn gs-btn-ghost" to={publicPaths.listing(row.id)}>{t('market.viewPublicListing')}</Link>
                            <button type="button" className="gs-btn gs-btn-ghost" disabled={busy} onClick={() => onHide(row)}>
                              {t('market.hideProduct')}
                            </button>
                          </>
                        )}
                        <button type="button" className="gs-btn gs-btn-primary" disabled={busy} onClick={() => onEditProduct(row)}>
                          {t('common.edit')}
                        </button>
                        <button type="button" className="gs-btn gs-btn-danger" disabled={busy} onClick={() => onRequestDelete(row)}>
                          {t('common.delete')}
                        </button>
                      </span>
                    </article>
                  )
                })}
              </div>
              <PaginationBar page={page} lastPage={lastPage} total={total} onPageChange={onPageChange} />
            </>
          )}
        </section>
      )}

      <ConfirmDialog
        open={pendingDelete != null}
        title={t('market.deleteProduct')}
        message={t('market.confirmDelete')}
        confirmLabel={t('market.deleteProduct')}
        onConfirm={onConfirmDelete}
        onCancel={onCancelDelete}
      />
    </div>
  )
}
