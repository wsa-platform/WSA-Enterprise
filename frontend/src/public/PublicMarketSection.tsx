import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import {
  fetchPublicCategories,
  fetchPublicListings,
  type PublicListing,
  type PublicMarketCategory,
} from '../api/marketplace'
import { useAuth } from '../context/AuthContext'
import { ProductCard } from '../marketplace/ProductCard'
import { countryDisplayName, MARKETPLACE_COUNTRY_CODES } from '../marketplace/isoCountries'
import { toPublicProduct } from '../marketplace/productDisplay'
import { internalPaths } from '../navigation/paths'
import { marketplaceLoginHref, sellerAddProductHref } from '../navigation/roleDestinations'

const PAGE_SIZE = 12

export function PublicMarketSection() {
  const { t, i18n } = useTranslation()
  const { token } = useAuth()
  const [listings, setListings] = useState<PublicListing[]>([])
  const [categories, setCategories] = useState<PublicMarketCategory[]>([])
  const [categoryId, setCategoryId] = useState<number | null>(null)
  const [sellerType, setSellerType] = useState<'' | 'local' | 'international'>('')
  const [country, setCountry] = useState('')
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [total, setTotal] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [reloadToken, setReloadToken] = useState(0)

  const language = i18n.language ?? 'ar'
  const filtersActive = Boolean(search || categoryId || sellerType || country)

  const countryOptions = useMemo(
    () => MARKETPLACE_COUNTRY_CODES.map((code) => ({ code, label: countryDisplayName(code, language) })),
    [language],
  )

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    Promise.all([
      fetchPublicListings({
        page,
        per_page: PAGE_SIZE,
        search: search || undefined,
        category_id: categoryId ?? undefined,
        seller_type: sellerType || undefined,
        country: country || undefined,
      }),
      fetchPublicCategories().catch(() => ({ data: [] as PublicMarketCategory[] })),
    ])
      .then(([listingRes, categoryRes]) => {
        if (cancelled) return
        setListings((listingRes.data ?? []).map(toPublicProduct))
        setLastPage(listingRes.last_page ?? 1)
        setTotal(listingRes.total ?? listingRes.data?.length ?? 0)
        const rows = Array.isArray(categoryRes) ? categoryRes : categoryRes.data ?? []
        setCategories(rows)
        setError(null)
      })
      .catch((err) => {
        if (cancelled) return
        setError(err instanceof Error ? err.message : t('market.loadProductsFailed'))
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [categoryId, country, page, reloadToken, search, sellerType, t])

  const applySearch = (event: React.FormEvent) => {
    event.preventDefault()
    setPage(1)
    setSearch(searchInput.trim())
  }

  const categoryLabel = (category: PublicMarketCategory) => (
    language.startsWith('ar') && category.name_ar ? category.name_ar : category.name ?? category.slug ?? String(category.id)
  )

  return (
    <section id="market" className="gs-section gs-section-white gs-market-section" aria-labelledby="market-title">
      <div className="gs-container">
        <div className="gs-section-header gs-market-hero">
          <div className="gs-section-eyebrow">
            <span className="gs-section-line" />
            <span>{t('website.nav.productMarket')}</span>
          </div>
          <h1 id="market-title">{t('market.catalogTitle')}</h1>
          <p className="gs-section-subtitle">{t('market.catalogSubtitle')}</p>
          <div className="gs-market-seller-bar">
            {token ? (
              <>
                <Link className="gs-btn gs-btn-ghost" to={internalPaths.products}>{t('nav.myProducts')}</Link>
                <Link className="gs-btn gs-btn-primary" to={sellerAddProductHref(true)}>{t('nav.addProduct')}</Link>
              </>
            ) : (
              <>
                <Link className="gs-btn gs-btn-ghost" to={marketplaceLoginHref(internalPaths.products)}>{t('website.nav.login')}</Link>
                <Link className="gs-btn gs-btn-primary" to={sellerAddProductHref(false)}>{t('nav.addProduct')}</Link>
              </>
            )}
          </div>
        </div>

        <form className="gs-market-toolbar" onSubmit={applySearch} role="search">
          <label className="gs-market-search">
            <span className="visually-hidden">{t('market.search')}</span>
            <input
              value={searchInput}
              onChange={(event) => setSearchInput(event.target.value)}
              placeholder={t('market.searchPlaceholder')}
              dir="auto"
            />
          </label>
          <button type="submit" className="gs-btn gs-btn-primary">{t('market.search')}</button>
        </form>

        <div className="gs-market-filters">
          <label>
            {t('market.sellerType')}
            <select
              value={sellerType}
              onChange={(event) => {
                setPage(1)
                setSellerType(event.target.value as '' | 'local' | 'international')
              }}
            >
              <option value="">{t('market.allSellerTypes')}</option>
              <option value="local">{t('market.sellerLocal')}</option>
              <option value="international">{t('market.sellerInternational')}</option>
            </select>
          </label>
          <label>
            {t('market.sellerCountry')}
            <select
              value={country}
              onChange={(event) => {
                setPage(1)
                setCountry(event.target.value)
              }}
            >
              <option value="">{t('market.allCountries')}</option>
              {countryOptions.map((option) => (
                <option key={option.code} value={option.code}>{option.label}</option>
              ))}
            </select>
          </label>
        </div>

        {categories.length > 0 && (
          <div className="gs-market-chips" role="tablist" aria-label={t('market.categories')}>
            <button
              type="button"
              className={`gs-market-chip${categoryId === null ? ' is-active' : ''}`}
              aria-pressed={categoryId === null}
              onClick={() => {
                setPage(1)
                setCategoryId(null)
              }}
            >
              {t('market.allCategories')}
            </button>
            {categories.map((category) => (
              <button
                key={category.id}
                type="button"
                className={`gs-market-chip${categoryId === category.id ? ' is-active' : ''}`}
                aria-pressed={categoryId === category.id}
                onClick={() => {
                  setPage(1)
                  setCategoryId(category.id)
                }}
              >
                {categoryLabel(category)}
              </button>
            ))}
          </div>
        )}

        {loading && <p className="gs-market-status">{t('market.loadingProducts')}</p>}
        {error && (
          <p className="gs-market-status" role="alert">
            {t('market.loadProductsFailed')}
            {' '}
            <button type="button" className="gs-btn gs-btn-ghost" onClick={() => setReloadToken((current) => current + 1)}>
              {t('common.retry')}
            </button>
          </p>
        )}
        {!loading && !error && listings.length === 0 && (
          <p className="gs-market-status">{filtersActive ? t('market.noSearchResults') : t('market.noProducts')}</p>
        )}

        {!loading && !error && listings.length > 0 && (
          <div className="gs-product-grid">
            {listings.map((listing) => <ProductCard key={listing.id} listing={listing} />)}
          </div>
        )}

        {!loading && !error && lastPage > 1 && (
          <div className="gs-market-pagination">
            <button type="button" className="gs-btn gs-btn-ghost" disabled={page <= 1} onClick={() => setPage((current) => Math.max(1, current - 1))}>
              {t('common.previous')}
            </button>
            <span>{t('common.pageOf', { page, lastPage, total })}</span>
            <button type="button" className="gs-btn gs-btn-ghost" disabled={page >= lastPage} onClick={() => setPage((current) => current + 1)}>
              {t('common.next')}
            </button>
          </div>
        )}
      </div>
    </section>
  )
}
