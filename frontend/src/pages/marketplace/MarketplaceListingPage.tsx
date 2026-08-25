import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  fetchPublicListing,
  payContactAccess,
  requestContactAccess,
  type PublicListingContact,
} from '../../api/marketplace'
import { useAuth } from '../../context/AuthContext'
import { translateApiError } from '../../i18n/apiErrors'
import { countryDisplayName } from '../../marketplace/isoCountries'
import { sellerTypeLabelKey } from '../../marketplace/listingForm'
import { isProductCategorySlug, productCategoryLabel } from '../../marketplace/productCategories'
import { availabilityI18nKey, listingImageUrl, listingImages, specificationLines, toPublicProduct } from '../../marketplace/productDisplay'
import { productNameFromListing } from '../../marketplace/units'
import {
  completeContactPayment,
  listingHasVisibleContact,
  showContactClickAction,
} from '../../marketplace/contactUnlock'
import { publicPaths } from '../../navigation/paths'
import { PublicLayout } from '../../public/PublicLayout'
import { useAsyncData } from '../../hooks/useAsyncData'

export function ContactUnlockPanel({
  authenticated,
  loginHref,
  paying,
  paymentOpen,
  error,
  contact,
  price,
  currency,
  onShowContact,
  onConfirmPayment,
}: {
  authenticated: boolean
  loginHref: string
  paying: boolean
  paymentOpen: boolean
  error: string
  contact: PublicListingContact | null
  price?: string | number
  currency?: string
  onShowContact: () => void
  onConfirmPayment: () => void
}) {
  const { t } = useTranslation()

  if (contact && (contact.seller_email || contact.seller_phone)) {
    return (
      <section className="gs-market-contact" aria-live="polite">
        <h2>{t('market.contactDetails')}</h2>
        <dl className="gs-market-specs">
          {contact.seller_display_name && (
            <><dt>{t('market.seller')}</dt><dd>{contact.seller_display_name}</dd></>
          )}
          {contact.seller_email && (
            <><dt>{t('market.sellerEmail')}</dt><dd>{contact.seller_email}</dd></>
          )}
          {contact.seller_phone && (
            <><dt>{t('market.sellerPhone')}</dt><dd>{contact.seller_phone}</dd></>
          )}
        </dl>
      </section>
    )
  }

  return (
    <section className="gs-market-contact">
      {!paymentOpen && (
        authenticated ? (
          <button type="button" className="gs-btn gs-btn-primary" disabled={paying} onClick={onShowContact}>
            {t('market.showContact')}
          </button>
        ) : (
          <Link className="gs-btn gs-btn-primary" to={loginHref}>
            {t('market.showContact')}
          </Link>
        )
      )}
      {paymentOpen && (
        <div className="gs-market-payment">
          <p>{t('market.contactProtected', { price: price ?? '—', currency: currency ?? '' })}</p>
          <button type="button" className="gs-btn gs-btn-primary" disabled={paying} onClick={onConfirmPayment}>
            {paying ? t('market.paying') : t('market.requestContact')}
          </button>
        </div>
      )}
      {error && <p className="gs-market-status" role="alert">{error}</p>}
    </section>
  )
}

export function MarketplaceListingPage() {
  const { t, i18n } = useTranslation()
  const { id } = useParams()
  const navigate = useNavigate()
  const { token } = useAuth()
  const listingId = Number(id)
  const language = i18n.language ?? 'ar'
  const [paymentOpen, setPaymentOpen] = useState(false)
  const [paying, setPaying] = useState(false)
  const [payError, setPayError] = useState('')
  const [unlockedContact, setUnlockedContact] = useState<PublicListingContact | null>(null)

  const { data: listing, loading, error } = useAsyncData(async () => {
    if (!Number.isFinite(listingId)) return null
    return toPublicProduct(await fetchPublicListing(listingId))
  }, [listingId])

  const product = listing
  const images = product ? listingImages(product) : []
  const name = product ? productNameFromListing(product) : ''
  const category = product?.category
    ? (language.startsWith('ar') && product.category.name_ar ? product.category.name_ar : product.category.name)
      || productCategoryLabel(product.category.slug ?? product.product_type ?? '', language)
    : productCategoryLabel(product?.product_type ?? '', language) || null
  const unit = product?.unit
    ? (language.startsWith('ar') && product.unit.name_ar ? product.unit.name_ar : product.unit.name)
    : null
  const specText = specificationLines(product?.specifications ?? null)
  const availabilityKey = availabilityI18nKey(product?.availability)
  const showAction = Number.isFinite(listingId) ? showContactClickAction(Boolean(token), listingId) : { kind: 'login' as const, href: '/login' }
  const visibleContact = unlockedContact

  const openContactFlow = () => {
    if (showAction.kind === 'login') {
      navigate(showAction.href)
      return
    }
    setPayError('')
    setPaymentOpen(true)
  }

  const confirmPayment = async () => {
    if (!token || !Number.isFinite(listingId) || paying) return
    setPaying(true)
    setPayError('')
    const result = await completeContactPayment({
      listingId,
      token,
      requestKey: `contact-req-${listingId}-${Date.now()}`,
      payKey: `contact-pay-${listingId}-${Date.now()}`,
      requestContactAccess,
      payContactAccess,
      fetchEntitledListing: (id, authToken) => fetchPublicListing(id, authToken),
    })
    setPaying(false)
    if (!result.ok) {
      setPayError(result.reason === 'unpaid' ? t('market.paymentFailed') : (translateApiError(result.error) || t('market.paymentFailed')))
      return
    }
    setUnlockedContact(result.contact)
  }

  return (
    <PublicLayout>
      <section className="gs-section gs-market-detail">
        <div className="gs-container">
          <Link to={publicPaths.market} className="gs-market-back">{t('market.backToMarket')}</Link>
          {loading && <p className="gs-market-status">{t('market.loadingProducts')}</p>}
          {error && (
            <p className="gs-market-status" role="alert">
              {t('market.loadProductsFailed')}
            </p>
          )}
          {!loading && !error && !product && (
            <p className="gs-market-status">{t('market.noProducts')}</p>
          )}
          {product && (
            <article className="gs-card gs-market-detail-card">
              {images.length > 0 && (
                <div className="gs-market-gallery">
                  {images.map((image) => {
                    const src = listingImageUrl(image.path)
                    return src ? <img key={image.path} src={src} alt={image.alt_text || name} /> : null
                  })}
                </div>
              )}
              <h1 dir="auto">{name}</h1>
              {product.brand && <p className="gs-market-brand">{product.brand}</p>}
              {product.description && <p className="gs-market-description">{product.description}</p>}
              {product.video_url && (
                <p>
                  <a href={product.video_url} target="_blank" rel="noreferrer">{t('market.video')}</a>
                </p>
              )}
              <dl className="gs-market-specs">
                {category && <><dt>{t('market.category')}</dt><dd>{category}</dd></>}
                {product.product_type && !isProductCategorySlug(product.product_type) && (
                  <><dt>{t('market.productType')}</dt><dd>{product.product_type}</dd></>
                )}
                {product.origin_country && <><dt>{t('market.originCountry')}</dt><dd>{countryDisplayName(product.origin_country, language)}</dd></>}
                {(product.seller_type ?? product.seller_types ?? product.seller?.seller_type) && (
                  <><dt>{t('market.sellerType')}</dt><dd>{t(sellerTypeLabelKey(product.seller_types ?? product.seller_type ?? product.seller?.seller_type) ?? 'market.sellerLocal')}</dd></>
                )}
                {(product.seller_country || product.country || product.seller?.country) && (
                  <><dt>{t('market.sellerCountry')}</dt><dd>{countryDisplayName(product.seller_country ?? product.country ?? product.seller?.country, language)}</dd></>
                )}
                {(product.city || product.seller?.city) && <><dt>{t('market.city')}</dt><dd>{product.city ?? product.seller?.city}</dd></>}
                {(product.seller_region || product.seller?.region) && <><dt>{t('market.sellerRegion')}</dt><dd>{product.seller_region ?? product.seller?.region}</dd></>}
                {availabilityKey && <><dt>{t('market.availabilityLabel')}</dt><dd>{t(availabilityKey)}</dd></>}
                {unit && <><dt>{t('market.unit')}</dt><dd>{unit}</dd></>}
                {product.min_order_quantity != null && <><dt>{t('market.minOrderQuantity')}</dt><dd>{String(product.min_order_quantity)}</dd></>}
                {product.available_quantity != null && <><dt>{t('market.availableQuantity')}</dt><dd>{String(product.available_quantity)}</dd></>}
                {product.production_capacity != null && <><dt>{t('market.productionCapacity')}</dt><dd>{String(product.production_capacity)}</dd></>}
                <><dt>{t('market.wholesale')}</dt><dd>{product.wholesale ? t('common.yes') : t('common.no')}</dd></>
                <><dt>{t('market.retail')}</dt><dd>{product.retail ? t('common.yes') : t('common.no')}</dd></>
                <><dt>{t('market.exportAvailable')}</dt><dd>{product.export_ready ? t('common.yes') : t('common.no')}</dd></>
                {product.packaging && <><dt>{t('market.packaging')}</dt><dd>{product.packaging}</dd></>}
                {product.price != null && <><dt>{t('market.price')}</dt><dd>{String(product.price)} {product.currency ?? ''}</dd></>}
              </dl>
              {specText && (
                <section>
                  <h2>{t('market.specifications')}</h2>
                  <pre className="gs-market-spec-block">{specText}</pre>
                </section>
              )}
              {!listingHasVisibleContact(product) && (
                <ContactUnlockPanel
                  authenticated={Boolean(token)}
                  loginHref={showAction.kind === 'login' ? showAction.href : publicPaths.market}
                  paying={paying}
                  paymentOpen={paymentOpen}
                  error={payError}
                  contact={visibleContact}
                  price={product.contact_access_price}
                  currency={product.contact_access_currency}
                  onShowContact={openContactFlow}
                  onConfirmPayment={() => void confirmPayment()}
                />
              )}
            </article>
          )}
        </div>
      </section>
    </PublicLayout>
  )
}
