import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  createListing,
  fetchMyListing,
  fetchPublicCategories,
  submitListing,
  updateListing,
  type OwnerListing,
  type PublicMarketCategory,
} from '../../api/marketplace'
import { PageHeader } from '../../components/PageHeader'
import { EmptyState, ErrorBanner, StatusBadge } from '../../components/UiPrimitives'
import { useAuth } from '../../context/AuthContext'
import { usePermissions } from '../../context/PermissionContext'
import { useAsyncData } from '../../hooks/useAsyncData'
import { apiFieldErrorMessages, translateApiError } from '../../i18n/apiErrors'
import { internalPaths, publicPaths } from '../../navigation/paths'

export function ListingEditorPage() {
  const { t, i18n } = useTranslation()
  const { token, organizationId, user } = useAuth()
  const { can, loading: permissionsLoading } = usePermissions()
  const navigate = useNavigate()
  const { listingId } = useParams()
  const numericId = listingId ? Number(listingId) : NaN
  const isNew = !Number.isFinite(numericId)
  const canManage = can('market.create') || can('market.manage_own') || can('market.manage_all')

  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [sellerType, setSellerType] = useState<'local' | 'international'>('local')
  const [price, setPrice] = useState('')
  const [currency, setCurrency] = useState('SAR')
  const [country, setCountry] = useState('')
  const [city, setCity] = useState('')
  const [sellerEmail, setSellerEmail] = useState(user?.email ?? '')
  const [sellerPhone, setSellerPhone] = useState('')
  const [categoryId, setCategoryId] = useState<number | null>(null)
  const [notice, setNotice] = useState('')
  const [fieldErrors, setFieldErrors] = useState<string[]>([])
  const [submitting, setSubmitting] = useState(false)
  const [listing, setListing] = useState<OwnerListing | null>(null)
  const [categories, setCategories] = useState<PublicMarketCategory[]>([])

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
    setSellerType(listing.seller_type === 'international' ? 'international' : 'local')
    setPrice(listing.price != null ? String(listing.price) : '')
    setCurrency(listing.currency ?? 'SAR')
    setCountry(listing.country ?? '')
    setCity(listing.city ?? '')
    setSellerEmail(listing.seller_email ?? '')
    setSellerPhone(listing.seller_phone ?? '')
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
    seller_type: sellerType,
    seller_display_name: user?.name,
    price: price ? Number(price) : null,
    currency: currency || undefined,
    country: country || undefined,
    city: city || undefined,
    seller_email: sellerEmail || undefined,
    seller_phone: sellerPhone || undefined,
    category_id: categoryId,
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
        setNotice(t('market.created'))
      } else if (numericId) {
        saved = await updateListing(token, numericId, payload(), organizationId ?? undefined)
        setNotice(t('market.updated'))
      } else {
        return
      }
      if (alsoSubmit) {
        saved = await submitListing(token, saved.id, organizationId ?? undefined)
        setNotice(t('market.submitted'))
      }
      navigate(alsoSubmit ? internalPaths.products : internalPaths.editProduct(saved.id), { replace: true })
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

  return <>
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
            <input value={title} onChange={(event) => setTitle(event.target.value)} disabled={!canManage || !editable} dir="auto" required />
          </label>
          <label>
            {t('common.description')}
            <textarea value={description} onChange={(event) => setDescription(event.target.value)} rows={4} disabled={!canManage || !editable} dir="auto" />
          </label>
          <label>
            {t('market.category')}
            <select
              value={categoryId ?? ''}
              onChange={(event) => setCategoryId(event.target.value ? Number(event.target.value) : null)}
              disabled={!canManage || !editable}
            >
              <option value="">{t('market.noneCategory')}</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>{categoryLabel(category)}</option>
              ))}
            </select>
          </label>
          <label>
            {t('market.sellerType')}
            <select value={sellerType} onChange={(event) => setSellerType(event.target.value as 'local' | 'international')} disabled={!canManage || !editable}>
              <option value="local">{t('market.sellerLocal')}</option>
              <option value="international">{t('market.sellerInternational')}</option>
            </select>
          </label>
          <label>
            {t('market.price')}
            <input value={price} onChange={(event) => setPrice(event.target.value)} type="number" min="0" step="0.01" disabled={!canManage || !editable} />
          </label>
          <label>
            {t('market.currency')}
            <input value={currency} onChange={(event) => setCurrency(event.target.value)} maxLength={3} disabled={!canManage || !editable} />
          </label>
          <label>
            {t('market.country')}
            <input value={country} onChange={(event) => setCountry(event.target.value)} disabled={!canManage || !editable} dir="auto" />
          </label>
          <label>
            {t('market.city')}
            <input value={city} onChange={(event) => setCity(event.target.value)} disabled={!canManage || !editable} dir="auto" />
          </label>
          <label>
            {t('market.sellerEmail')}
            <input value={sellerEmail} onChange={(event) => setSellerEmail(event.target.value)} type="email" disabled={!canManage || !editable} />
          </label>
          <label>
            {t('market.sellerPhone')}
            <input value={sellerPhone} onChange={(event) => setSellerPhone(event.target.value)} disabled={!canManage || !editable} />
          </label>
        </div>
        {canManage && editable && (
          <div className="form-actions">
            <button type="button" disabled={submitting || !title.trim()} onClick={() => void save(false)}>
              {submitting ? t('common.saving') : t('common.save')}
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
}
