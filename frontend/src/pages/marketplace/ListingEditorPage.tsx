import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  PRODUCT_AVAILABILITIES,
  createListing,
  fetchMyListing,
  fetchPublicCategories,
  submitListing,
  updateListing,
  type OwnerListing,
  type ProductAvailability,
  type ProductSellerType,
  type PublicMarketCategory,
} from '../../api/marketplace'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { apiFieldErrorMessages, translateApiError } from '../../i18n/apiErrors'
import { countryDisplayName, MARKETPLACE_COUNTRY_CODES } from '../../marketplace/isoCountries'
import { listingImageUrl, listingImages, parseSpecificationLines, specificationLines } from '../../marketplace/productDisplay'
import { internalPaths, publicPaths } from '../../navigation/paths'

function numberOrNull(value: string): number | null {
  const trimmed = value.trim()
  if (!trimmed) return null
  const parsed = Number(trimmed)
  return Number.isFinite(parsed) ? parsed : null
}

export function ListingEditorPage() {
  const { t, i18n } = useTranslation()
  const { token, organizationId, user } = useAuth()
  const { can, loading: permissionsLoading } = usePermissions()
  const navigate = useNavigate()
  const { listingId } = useParams()
  const numericId = listingId ? Number(listingId) : NaN
  const isNew = !Number.isFinite(numericId)
  const canManage = can('market.create') || can('market.manage_own') || can('market.manage_all')
  const language = i18n.language ?? 'ar'

  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [productType, setProductType] = useState('')
  const [brand, setBrand] = useState('')
  const [sellerType, setSellerType] = useState<ProductSellerType>('local')
  const [availability, setAvailability] = useState<ProductAvailability | ''>('')
  const [price, setPrice] = useState('')
  const [currency, setCurrency] = useState('SAR')
  const [country, setCountry] = useState('SA')
  const [originCountry, setOriginCountry] = useState('')
  const [city, setCity] = useState('')
  const [sellerRegion, setSellerRegion] = useState('')
  const [minOrderQuantity, setMinOrderQuantity] = useState('')
  const [availableQuantity, setAvailableQuantity] = useState('')
  const [productionCapacity, setProductionCapacity] = useState('')
  const [wholesale, setWholesale] = useState(false)
  const [retail, setRetail] = useState(false)
  const [exportReady, setExportReady] = useState(false)
  const [packaging, setPackaging] = useState('')
  const [shippingTerms, setShippingTerms] = useState('')
  const [leadTimeDays, setLeadTimeDays] = useState('')
  const [specificationsText, setSpecificationsText] = useState('')
  const [videoUrl, setVideoUrl] = useState('')
  const [categoryId, setCategoryId] = useState<number | null>(null)
  const [notice, setNotice] = useState('')
  const [fieldErrors, setFieldErrors] = useState<string[]>([])
  const [submitting, setSubmitting] = useState(false)
  const [listing, setListing] = useState<OwnerListing | null>(null)
  const [categories, setCategories] = useState<PublicMarketCategory[]>([])

  const countryOptions = useMemo(
    () => MARKETPLACE_COUNTRY_CODES.map((code) => ({ code, label: countryDisplayName(code, language) })),
    [language],
  )

  const { loading, error, reload } = useAsyncData(async () => {
    const [categoryRes, match] = await Promise.all([
      fetchPublicCategories().catch(() => ({ data: [] as PublicMarketCategory[] })),
      !token || isNew || !numericId ? Promise.resolve(null) : fetchMyListing(token, numericId, organizationId ?? undefined),
    ])
    setCategories(Array.isArray(categoryRes) ? categoryRes : categoryRes.data ?? [])
    if (match) setListing(match)
    return match
  }, [token, organizationId, numericId, isNew])

  useEffect(() => {
    if (!listing) return
    setTitle(listing.title)
    setDescription(listing.description ?? '')
    setProductType(listing.product_type ?? '')
    setBrand(listing.brand ?? '')
    setSellerType(listing.seller_type === 'international' ? 'international' : 'local')
    setAvailability(PRODUCT_AVAILABILITIES.includes(listing.availability as ProductAvailability) ? listing.availability as ProductAvailability : '')
    setPrice(listing.price != null ? String(listing.price) : '')
    setCurrency(listing.currency ?? 'SAR')
    setCountry(listing.country ?? listing.seller_country ?? 'SA')
    setOriginCountry(listing.origin_country ?? '')
    setCity(listing.city ?? '')
    setSellerRegion(listing.seller_region ?? '')
    setMinOrderQuantity(listing.min_order_quantity != null ? String(listing.min_order_quantity) : '')
    setAvailableQuantity(listing.available_quantity != null ? String(listing.available_quantity) : '')
    setProductionCapacity(listing.production_capacity != null ? String(listing.production_capacity) : '')
    setWholesale(Boolean(listing.wholesale))
    setRetail(Boolean(listing.retail))
    setExportReady(Boolean(listing.export_ready))
    setPackaging(listing.packaging ?? '')
    setShippingTerms(listing.shipping_terms ?? '')
    setLeadTimeDays(listing.lead_time_days != null ? String(listing.lead_time_days) : '')
    setSpecificationsText(specificationLines(listing.specifications ?? null))
    setVideoUrl(listing.video_url ?? '')
    setCategoryId(listing.category?.id ?? null)
  }, [listing])

  if (permissionsLoading) {
    return <p className="loading">{t('errors.checkingAccess')}</p>
  }

  if (!can('market.view')) {
    return <ErrorBanner message={t('market.noPermissionView')} />
  }

  if (isNew && !can('market.create')) {
    return <ErrorBanner message={t('market.noPermissionCreate')} />
  }

  const payload = () => ({
    title: title.trim(),
    description: description || undefined,
    product_type: productType.trim() || null,
    brand: brand.trim() || null,
    seller_type: sellerType,
    availability: availability || null,
    seller_display_name: user?.name,
    price: numberOrNull(price),
    currency: currency || undefined,
    country: country || undefined,
    origin_country: originCountry || null,
    city: city || undefined,
    seller_region: sellerRegion.trim() || null,
    category_id: categoryId,
    export_ready: exportReady,
    min_order_quantity: numberOrNull(minOrderQuantity),
    available_quantity: numberOrNull(availableQuantity),
    production_capacity: numberOrNull(productionCapacity),
    wholesale,
    retail,
    packaging: packaging.trim() || null,
    shipping_terms: shippingTerms.trim() || null,
    lead_time_days: numberOrNull(leadTimeDays),
    specifications: parseSpecificationLines(specificationsText),
    video_url: videoUrl.trim() || null,
  })

  const save = async (alsoSubmit = false) => {
    if (!token || !canManage || !title.trim()) return
    setSubmitting(true)
    setNotice('')
    setFieldErrors([])
    try {
      let saved: OwnerListing
      if (isNew) {
        saved = await createListing(token, payload(), organizationId ?? undefined)
      } else if (numericId) {
        saved = await updateListing(token, numericId, payload(), organizationId ?? undefined)
      } else {
        return
      }
      if (alsoSubmit) {
        saved = await submitListing(token, saved.id, organizationId ?? undefined)
      }
      navigate(internalPaths.products, { replace: true, state: { productNotice: isNew ? 'created' : 'updated' } })
    } catch (requestError) {
      setFieldErrors(apiFieldErrorMessages(requestError))
      setNotice(translateApiError(requestError) || t('market.saveFailed'))
    } finally {
      setSubmitting(false)
    }
  }

  const editable = isNew || listing?.status === 'draft' || listing?.status === 'rejected' || listing?.status === 'unpublished'
  const categoryLabel = (category: PublicMarketCategory) => (
    i18n.language.startsWith('ar') && category.name_ar ? category.name_ar : category.name ?? category.slug ?? String(category.id)
  )
  const disabled = !canManage || !editable

  if (!isNew && !loading && !listing) {
    return (
      <>
        <PageHeader
          eyebrow={t('nav.myAccount')}
          title={t('market.editProduct')}
          actions={<Link className="link-button" to={internalPaths.products}>{t('market.backToListings')}</Link>}
        />
        {error ? <ErrorBanner message={error} onRetry={reload} /> : <EmptyState title={t('errors.notFound')} description={t('market.loadingListing')} />}
      </>
    )
  }

  return (
    <>
      <PageHeader
        eyebrow={t('nav.myAccount')}
        title={isNew ? t('market.addProduct') : t('market.editProduct')}
        description={t('market.editorDescription')}
        actions={(
          <span className="header-actions">
            <Link className="link-button" to={internalPaths.products}>{t('market.backToListings')}</Link>
            {listing?.status === 'published' && (
              <Link className="link-button" to={publicPaths.listing(listing.id)}>{t('market.viewPublicListing')}</Link>
            )}
          </span>
        )}
      />

      {error && <ErrorBanner message={error} onRetry={reload} />}
      {fieldErrors.length > 0 && (
        <ul className="field-errors">
          {fieldErrors.map((message) => <li key={message}>{message}</li>)}
        </ul>
      )}
      {notice && <p className={`notice ${notice === t('market.saveFailed') ? '' : 'success'}`.trim()}>{notice}</p>}

      {loading && !isNew ? (
        <p className="loading">{t('market.loadingListing')}</p>
      ) : (
        <section className="panel">
          <div className="panel-heading">
            <div><p className="eyebrow">{t('market.details')}</p><h2>{t('market.listingForm')}</h2></div>
            {!isNew && listing?.status && <StatusBadge status={listing.status} />}
          </div>
          <div className="record-form">
            <label>
              {t('common.title')}
              <input value={title} onChange={(event) => setTitle(event.target.value)} disabled={disabled} dir="auto" required />
            </label>
            <label>
              {t('common.description')}
              <textarea value={description} onChange={(event) => setDescription(event.target.value)} rows={4} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.category')}
              <select value={categoryId ?? ''} onChange={(event) => setCategoryId(event.target.value ? Number(event.target.value) : null)} disabled={disabled}>
                <option value="">{t('market.noneCategory')}</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>{categoryLabel(category)}</option>
                ))}
              </select>
            </label>
            <label>
              {t('market.productType')}
              <input value={productType} onChange={(event) => setProductType(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.brand')}
              <input value={brand} onChange={(event) => setBrand(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.sellerType')}
              <select value={sellerType} onChange={(event) => setSellerType(event.target.value as ProductSellerType)} disabled={disabled}>
                <option value="local">{t('market.sellerLocal')}</option>
                <option value="international">{t('market.sellerInternational')}</option>
              </select>
            </label>
            <label>
              {t('market.availabilityLabel')}
              <select value={availability} onChange={(event) => setAvailability(event.target.value as ProductAvailability | '')} disabled={disabled}>
                <option value="">{t('market.noneCategory')}</option>
                {PRODUCT_AVAILABILITIES.map((value) => (
                  <option key={value} value={value}>{t(`market.availability.${value}`)}</option>
                ))}
              </select>
            </label>
            {listing?.unit && (
              <p className="muted">
                {t('market.unit')}: {language.startsWith('ar') && listing.unit.name_ar ? listing.unit.name_ar : listing.unit.name ?? listing.unit.slug}
              </p>
            )}
            <label>
              {t('market.price')}
              <input value={price} onChange={(event) => setPrice(event.target.value)} type="number" min="0" step="0.01" disabled={disabled} />
            </label>
            <label>
              {t('market.currency')}
              <input value={currency} onChange={(event) => setCurrency(event.target.value)} maxLength={3} disabled={disabled} />
            </label>
            <label>
              {t('market.sellerCountry')}
              <select value={country} onChange={(event) => setCountry(event.target.value)} disabled={disabled} required>
                {countryOptions.map((option) => (
                  <option key={option.code} value={option.code}>{option.label}</option>
                ))}
              </select>
            </label>
            <label>
              {t('market.originCountry')}
              <select value={originCountry} onChange={(event) => setOriginCountry(event.target.value)} disabled={disabled}>
                <option value="">{t('market.noneCategory')}</option>
                {countryOptions.map((option) => (
                  <option key={option.code} value={option.code}>{option.label}</option>
                ))}
              </select>
            </label>
            <label>
              {t('market.city')}
              <input value={city} onChange={(event) => setCity(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.sellerRegion')}
              <input value={sellerRegion} onChange={(event) => setSellerRegion(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.minOrderQuantity')}
              <input value={minOrderQuantity} onChange={(event) => setMinOrderQuantity(event.target.value)} type="number" min="0" disabled={disabled} />
            </label>
            <label>
              {t('market.availableQuantity')}
              <input value={availableQuantity} onChange={(event) => setAvailableQuantity(event.target.value)} type="number" min="0" disabled={disabled} />
            </label>
            <label>
              {t('market.productionCapacity')}
              <input value={productionCapacity} onChange={(event) => setProductionCapacity(event.target.value)} type="number" min="0" disabled={disabled} />
            </label>
            <label className="checkbox-field">
              <input type="checkbox" checked={wholesale} onChange={(event) => setWholesale(event.target.checked)} disabled={disabled} />
              {t('market.wholesale')}
            </label>
            <label className="checkbox-field">
              <input type="checkbox" checked={retail} onChange={(event) => setRetail(event.target.checked)} disabled={disabled} />
              {t('market.retail')}
            </label>
            <label className="checkbox-field">
              <input type="checkbox" checked={exportReady} onChange={(event) => setExportReady(event.target.checked)} disabled={disabled} />
              {t('market.exportReady')}
            </label>
            <label>
              {t('market.packaging')}
              <input value={packaging} onChange={(event) => setPackaging(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.shippingTerms')}
              <textarea value={shippingTerms} onChange={(event) => setShippingTerms(event.target.value)} rows={2} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.leadTimeDays')}
              <input value={leadTimeDays} onChange={(event) => setLeadTimeDays(event.target.value)} type="number" min="0" disabled={disabled} />
            </label>
            <label>
              {t('market.video')}
              <input value={videoUrl} onChange={(event) => setVideoUrl(event.target.value)} type="url" disabled={disabled} dir="ltr" />
            </label>
            <label>
              {t('market.specifications')}
              <textarea
                value={specificationsText}
                onChange={(event) => setSpecificationsText(event.target.value)}
                rows={4}
                disabled={disabled}
                dir="auto"
                placeholder={t('market.specificationsHint')}
              />
            </label>
          </div>
          {!isNew && listing && listingImages(listing).length > 0 && (
            <div className="listing-image-preview">
              {listingImages(listing).map((image) => {
                const src = listingImageUrl(image.path)
                return src ? (
                  <img key={image.id ?? image.path} src={src} alt={image.alt_text || listing.title} />
                ) : null
              })}
            </div>
          )}
          {canManage && editable && (
            <div className="form-actions">
              <button type="button" disabled={submitting || !title.trim()} onClick={() => void save(false)}>
                {submitting ? t('common.saving') : t('market.saveProduct')}
              </button>
              <button type="button" className="refresh" disabled={submitting || !title.trim()} onClick={() => void save(true)}>
                {t('market.publishProduct')}
              </button>
            </div>
          )}
          {canManage && !editable && listing && (
            <p className="muted">{t('market.publishedReadOnly')}</p>
          )}
        </section>
      )}
    </>
  )
}
