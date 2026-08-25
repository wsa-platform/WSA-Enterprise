import { useEffect, useMemo, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import {
  PRODUCT_AVAILABILITIES,
  createListing,
  fetchPublicCategories,
  fetchPublicUnits,
  updateListing,
  type OwnerListing,
  type ProductAvailability,
  type PublicMarketCategory,
  type PublicMarketUnit,
} from '../../api/marketplace'
import { ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAsyncData } from '../../hooks/useAsyncData'
import { apiFieldErrorMessages, translateApiError } from '../../i18n/apiErrors'
import { CURRENCY_COUNTRIES, formatCurrencyCountryOption } from '../../marketplace/currencyCountries'
import { countryDisplayName, MARKETPLACE_COUNTRY_CODES } from '../../marketplace/isoCountries'
import {
  buildListingWritePayload,
  hydrateListingEditor,
  validateListingEditor,
} from '../../marketplace/listingForm'
import { CALLING_CODES, digitsOnly, formatCallingCodeOption } from '../../marketplace/phone'
import { PRODUCT_CATEGORIES, isProductCategorySlug, productCategoryLabel } from '../../marketplace/productCategories'
import { listingImageUrl, listingImages, parseSpecificationLines, specificationLines } from '../../marketplace/productDisplay'
import { mergeMarketUnits, unitOptionLabel } from '../../marketplace/units'
import { saveSellerListing } from './sellerListingsActions'

type SellerProductFormProps = {
  listing?: OwnerListing | null
  token: string
  organizationId?: number | null
  sellerDisplayName?: string
  onCancel: () => void
  onSaved: (listing: OwnerListing, kind: 'created' | 'updated') => void
  saveLabel: string
  cancelLabel: string
  readOnly?: boolean
}

export function SellerProductForm({
  listing,
  token,
  organizationId,
  sellerDisplayName,
  onCancel,
  onSaved,
  saveLabel,
  cancelLabel,
  readOnly = false,
}: SellerProductFormProps) {
  const { t, i18n } = useTranslation()
  const isNew = !listing
  const language = i18n.language ?? 'ar'
  const busyRef = useRef(false)

  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [categorySlug, setCategorySlug] = useState('')
  const [legacyCategorySlug, setLegacyCategorySlug] = useState('')
  const [productType, setProductType] = useState('')
  const [brand, setBrand] = useState('')
  const [sellerLocal, setSellerLocal] = useState(false)
  const [sellerInternational, setSellerInternational] = useState(false)
  const [availability, setAvailability] = useState<ProductAvailability | ''>('')
  const [price, setPrice] = useState('')
  const [currency, setCurrency] = useState('SAR')
  const [originCountry, setOriginCountry] = useState('')
  const [city, setCity] = useState('')
  const [sellerRegion, setSellerRegion] = useState('')
  const [unitId, setUnitId] = useState('')
  const [minOrderQuantity, setMinOrderQuantity] = useState('')
  const [availableQuantity, setAvailableQuantity] = useState('')
  const [productionCapacity, setProductionCapacity] = useState('')
  const [wholesale, setWholesale] = useState(false)
  const [retail, setRetail] = useState(false)
  const [exportReady, setExportReady] = useState(false)
  const [packaging, setPackaging] = useState('')
  const [specificationsText, setSpecificationsText] = useState('')
  const [callingCode, setCallingCode] = useState('966')
  const [nationalPhone, setNationalPhone] = useState('')
  const [sellerEmail, setSellerEmail] = useState('')
  const [notice, setNotice] = useState('')
  const [fieldErrors, setFieldErrors] = useState<string[]>([])
  const [submitting, setSubmitting] = useState(false)
  const [categories, setCategories] = useState<PublicMarketCategory[]>([])
  const [units, setUnits] = useState<PublicMarketUnit[]>([])

  const originOptions = useMemo(
    () => MARKETPLACE_COUNTRY_CODES.map((code) => ({ code, label: countryDisplayName(code, language) })),
    [language],
  )
  const currencyOptions = useMemo(
    () => CURRENCY_COUNTRIES.map((entry) => ({
      value: entry.currency,
      label: formatCurrencyCountryOption(entry, language),
    })),
    [language],
  )
  const categoryOptions = useMemo(() => {
    const fromApi = categories
      .filter((category) => Boolean(category.slug))
      .map((category) => ({
        slug: category.slug as string,
        label: (language.startsWith('ar') && category.name_ar ? category.name_ar : category.name)
          || productCategoryLabel(category.slug as string, language),
      }))
    if (fromApi.length > 0) return fromApi
    return PRODUCT_CATEGORIES.map((category) => ({
      slug: category.slug,
      label: productCategoryLabel(category.slug, language),
    }))
  }, [categories, language])

  const { loading, error, reload } = useAsyncData(async () => {
    const [categoryRes, unitRes] = await Promise.all([
      fetchPublicCategories().catch(() => ({ data: [] as PublicMarketCategory[] })),
      fetchPublicUnits().catch(() => ({ data: [] as PublicMarketUnit[] })),
    ])
    setCategories(Array.isArray(categoryRes) ? categoryRes : categoryRes.data ?? [])
    setUnits(Array.isArray(unitRes) ? unitRes : unitRes.data ?? [])
    return true
  }, [])

  useEffect(() => {
    if (!listing) return
    const hydrated = hydrateListingEditor(listing)
    setTitle(listing.title)
    setDescription(listing.description ?? '')
    setCategorySlug(isProductCategorySlug(hydrated.categorySlug) ? hydrated.categorySlug : '')
    setLegacyCategorySlug(isProductCategorySlug(hydrated.categorySlug) ? '' : hydrated.categorySlug)
    setProductType(isProductCategorySlug(listing.product_type) ? '' : (listing.product_type ?? ''))
    setBrand(listing.brand ?? '')
    setSellerLocal(hydrated.sellerLocal)
    setSellerInternational(hydrated.sellerInternational)
    setAvailability(hydrated.availability)
    setPrice(listing.price != null ? String(listing.price) : '')
    setCurrency(hydrated.matchedCurrency ? hydrated.currency : (hydrated.currency || 'SAR'))
    setOriginCountry(listing.origin_country ?? '')
    setCity(listing.city ?? '')
    setSellerRegion(listing.seller_region ?? '')
    setUnitId(hydrated.unitId)
    setMinOrderQuantity(listing.min_order_quantity != null ? String(listing.min_order_quantity) : '')
    setAvailableQuantity(listing.available_quantity != null ? String(listing.available_quantity) : '')
    setProductionCapacity(listing.production_capacity != null ? String(listing.production_capacity) : '')
    setWholesale(Boolean(listing.wholesale))
    setRetail(Boolean(listing.retail))
    setExportReady(Boolean(listing.export_ready))
    setPackaging(listing.packaging ?? '')
    setSpecificationsText(specificationLines(listing.specifications ?? null))
    setCallingCode(hydrated.callingCode || '966')
    setNationalPhone(hydrated.nationalPhone)
    setSellerEmail(hydrated.sellerEmail)
  }, [listing])

  const editorValues = () => ({
    title,
    description,
    categorySlug: categorySlug || legacyCategorySlug,
    productType,
    brand,
    sellerLocal,
    sellerInternational,
    availability,
    price,
    currency,
    originCountry,
    city,
    sellerRegion,
    unitId,
    minOrderQuantity,
    availableQuantity,
    productionCapacity,
    wholesale,
    retail,
    exportReady,
    packaging,
    specificationsText,
    callingCode,
    nationalPhone,
    sellerEmail,
  })

  const save = async () => {
    if (busyRef.current || readOnly) return
    const { payload, errors } = buildListingWritePayload(editorValues(), {
      categories,
      sellerDisplayName,
      specifications: parseSpecificationLines(specificationsText),
    })
    if (errors.length > 0) {
      setFieldErrors(errors.map((key) => t(key)))
      setNotice(t('market.saveFailed'))
      return
    }

    busyRef.current = true
    setSubmitting(true)
    setNotice('')
    setFieldErrors([])
    try {
      const result = await saveSellerListing({
        busy: false,
        token,
        organizationId: organizationId ?? undefined,
        mode: isNew ? 'create' : 'edit',
        listingId: listing?.id,
        payload,
        createListing,
        updateListing,
      })
      if (!result.ok) {
        if (result.reason === 'busy') return
        setFieldErrors(apiFieldErrorMessages(result.error))
        setNotice(translateApiError(result.error) || t('market.saveFailed'))
        return
      }
      onSaved(result.listing, result.kind)
    } catch (requestError) {
      setFieldErrors(apiFieldErrorMessages(requestError))
      setNotice(translateApiError(requestError) || t('market.saveFailed'))
    } finally {
      busyRef.current = false
      setSubmitting(false)
    }
  }

  const disabled = readOnly || submitting
  const knownCategorySlugs = categories.map((category) => category.slug).filter((slug): slug is string => Boolean(slug))
  const validation = validateListingEditor(editorValues(), knownCategorySlugs)
  const categoryInvalid = validation.includes('market.categoryRequired')
  const sellerInvalid = validation.includes('market.sellerTypeRequired')
  const currencyInvalid = validation.includes('market.currencyRequired')
  const phoneInvalid = validation.includes('market.sellerPhoneRequired') || validation.includes('market.sellerPhoneInvalid')
  const emailInvalid = validation.includes('market.sellerEmailRequired') || validation.includes('market.sellerEmailInvalid')

  return (
    <section className="panel seller-product-form-panel">
      <div className="panel-heading">
        <div>
          <p className="eyebrow">{t('market.details')}</p>
          <h2>{isNew ? t('market.addProduct') : t('market.editProduct')}</h2>
        </div>
        {!isNew && listing?.status && <StatusBadge status={listing.status} />}
      </div>

      {error && <ErrorBanner message={error} onRetry={reload} />}
      {fieldErrors.length > 0 && (
        <ul className="field-errors">
          {fieldErrors.map((message) => <li key={message}>{message}</li>)}
        </ul>
      )}
      {notice && (
        <p className={`notice ${notice === t('market.saveFailed') ? '' : 'success'}`.trim()} role="alert">
          {notice}
        </p>
      )}
      {readOnly && listing && <p className="muted">{t('market.publishedReadOnly')}</p>}
      {loading ? (
        <p className="loading">{t('market.loadingListing')}</p>
      ) : (
        <form
          className="listing-form"
          aria-busy={submitting}
          onSubmit={(event) => {
            event.preventDefault()
            void save()
          }}
        >
          <div className="listing-form-grid">
            <p className="listing-section-title span-2">{t('market.productInformation')}</p>
            <label className="span-2">
              {t('market.productName')}
              <input value={title} onChange={(event) => setTitle(event.target.value)} disabled={disabled} dir="auto" required />
            </label>
            <label>
              {t('market.category')}
              <select
                value={categorySlug}
                onChange={(event) => {
                  setCategorySlug(event.target.value)
                  if (event.target.value) setLegacyCategorySlug('')
                }}
                disabled={disabled}
                required
                aria-invalid={categoryInvalid}
                className={categoryInvalid ? 'is-invalid' : undefined}
              >
                <option value="">{t('market.selectCategory')}</option>
                {categoryOptions.map((category) => (
                  <option key={category.slug} value={category.slug}>{category.label}</option>
                ))}
                {legacyCategorySlug ? (
                  <option value={legacyCategorySlug}>{productCategoryLabel(legacyCategorySlug, language) || legacyCategorySlug}</option>
                ) : null}
              </select>
              {categoryInvalid && <p className="field-error">{t('market.categoryRequired')}</p>}
            </label>
            <label>
              {t('market.productType')}
              <input value={productType} onChange={(event) => setProductType(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label className="span-2">
              {t('common.description')}
              <textarea value={description} onChange={(event) => setDescription(event.target.value)} rows={4} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.brand')}
              <input value={brand} onChange={(event) => setBrand(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.originCountry')}
              <select value={originCountry} onChange={(event) => setOriginCountry(event.target.value)} disabled={disabled}>
                <option value="">{t('market.noneCategory')}</option>
                {originOptions.map((option) => (
                  <option key={option.code} value={option.code}>{option.label}</option>
                ))}
              </select>
            </label>

            <fieldset className="listing-fieldset span-2">
              <legend>{t('market.sellerType')}</legend>
              <div className="seller-type-options">
                <label className="checkbox-field">
                  <input
                    type="checkbox"
                    checked={sellerLocal}
                    onChange={(event) => setSellerLocal(event.target.checked)}
                    disabled={disabled}
                  />
                  {t('market.sellerLocal')}
                </label>
                <label className="checkbox-field">
                  <input
                    type="checkbox"
                    checked={sellerInternational}
                    onChange={(event) => setSellerInternational(event.target.checked)}
                    disabled={disabled}
                  />
                  {t('market.sellerInternational')}
                </label>
              </div>
              <p className="muted">{t('market.sellerTypeHint')}</p>
              {sellerInvalid && <p className="field-error">{t('market.sellerTypeRequired')}</p>}
            </fieldset>

            <p className="listing-section-title span-2">{t('market.price')}</p>
            <label>
              {t('market.price')}
              <input value={price} onChange={(event) => setPrice(event.target.value)} type="number" min="0" step="0.01" disabled={disabled} />
            </label>
            <label>
              {t('market.currencyCountry')}
              <select
                value={currency}
                onChange={(event) => setCurrency(event.target.value)}
                disabled={disabled}
                required
                aria-invalid={currencyInvalid}
                className={currencyInvalid ? 'is-invalid' : undefined}
              >
                {currencyOptions.map((option) => (
                  <option key={option.value} value={option.value}>{option.label}</option>
                ))}
              </select>
              {currencyInvalid && <p className="field-error">{t('market.currencyRequired')}</p>}
            </label>
            <label>
              {t('market.unit')}
              <select value={unitId} onChange={(event) => setUnitId(event.target.value)} disabled={disabled}>
                <option value="">{t('market.selectUnit')}</option>
                {mergeMarketUnits(units).map((unit) => (
                  <option key={unit.id} value={String(unit.id)}>
                    {unitOptionLabel(unit, language)}
                  </option>
                ))}
              </select>
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

            <p className="listing-section-title span-2">{t('market.salesSection')}</p>
            <label className="checkbox-field">
              <input type="checkbox" checked={wholesale} onChange={(event) => setWholesale(event.target.checked)} disabled={disabled} />
              {t('market.wholesale')}
            </label>
            <label className="checkbox-field">
              <input type="checkbox" checked={retail} onChange={(event) => setRetail(event.target.checked)} disabled={disabled} />
              {t('market.retail')}
            </label>
            <label>
              {t('market.exportAvailable')}
              <select
                value={exportReady ? 'yes' : 'no'}
                onChange={(event) => setExportReady(event.target.value === 'yes')}
                disabled={disabled}
              >
                <option value="yes">{t('common.yes')}</option>
                <option value="no">{t('common.no')}</option>
              </select>
            </label>

            <p className="listing-section-title span-2">{t('market.availabilityLabel')}</p>
            <label>
              {t('market.availabilityLabel')}
              <select value={availability} onChange={(event) => setAvailability(event.target.value as ProductAvailability | '')} disabled={disabled}>
                <option value="">{t('market.noneCategory')}</option>
                {PRODUCT_AVAILABILITIES.map((value) => (
                  <option key={value} value={value}>{t(`market.availability.${value}`)}</option>
                ))}
              </select>
            </label>

            <p className="listing-section-title span-2">{t('market.packaging')}</p>
            <label className="span-2">
              {t('market.packaging')}
              <input value={packaging} onChange={(event) => setPackaging(event.target.value)} disabled={disabled} dir="auto" />
            </label>

            <p className="listing-section-title span-2">{t('market.specifications')}</p>
            <label className="span-2">
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

            <p className="listing-section-title span-2">{t('market.sellerContact')}</p>
            <div className="phone-row span-2">
              <label>
                {t('market.callingCode')}
                <select
                  value={callingCode}
                  onChange={(event) => setCallingCode(event.target.value)}
                  disabled={disabled}
                  required
                  aria-invalid={phoneInvalid}
                  className={phoneInvalid ? 'is-invalid' : undefined}
                >
                  <option value="">{t('market.selectCallingCode')}</option>
                  {CALLING_CODES.map((entry) => (
                    <option key={`${entry.iso}-${entry.dial}`} value={entry.dial}>
                      {formatCallingCodeOption(entry, language)}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                {t('market.sellerPhone')}
                <input
                  value={nationalPhone}
                  onChange={(event) => setNationalPhone(digitsOnly(event.target.value))}
                  inputMode="numeric"
                  autoComplete="tel-national"
                  disabled={disabled}
                  required
                  aria-invalid={phoneInvalid}
                  className={phoneInvalid ? 'is-invalid' : undefined}
                />
              </label>
            </div>
            {phoneInvalid && (
              <p className="field-error span-2">
                {t(validation.includes('market.sellerPhoneRequired') ? 'market.sellerPhoneRequired' : 'market.sellerPhoneInvalid')}
              </p>
            )}
            <label className="span-2">
              {t('market.sellerEmail')}
              <input
                type="email"
                value={sellerEmail}
                onChange={(event) => setSellerEmail(event.target.value)}
                autoComplete="email"
                disabled={disabled}
                required
                dir="ltr"
                aria-invalid={emailInvalid}
                className={emailInvalid ? 'is-invalid' : undefined}
              />
              {emailInvalid && (
                <p className="field-error">
                  {t(validation.includes('market.sellerEmailRequired') ? 'market.sellerEmailRequired' : 'market.sellerEmailInvalid')}
                </p>
              )}
            </label>
            <label>
              {t('market.city')}
              <input value={city} onChange={(event) => setCity(event.target.value)} disabled={disabled} dir="auto" />
            </label>
            <label>
              {t('market.sellerRegion')}
              <input value={sellerRegion} onChange={(event) => setSellerRegion(event.target.value)} disabled={disabled} dir="auto" />
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
          <div className="listing-form-actions">
            {!readOnly && (
              <button type="submit" className="gs-btn gs-btn-primary" disabled={submitting}>
                {submitting ? t('common.saving') : saveLabel}
              </button>
            )}
            <button type="button" className="gs-btn gs-btn-ghost" disabled={submitting} onClick={onCancel}>
              {cancelLabel}
            </button>
          </div>
        </form>
      )}
    </section>
  )
}
