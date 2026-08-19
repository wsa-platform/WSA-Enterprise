import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import {
  fetchPublicCategories,
  fetchPublicListings,
  type PublicListing,
  type PublicMarketCategory,
} from '../api/marketplace'

function categoryLabel(
  category: { id?: number; slug?: string; name?: string; name_ar?: string },
  language: string,
) {
  if (language.startsWith('ar') && category.name_ar) return category.name_ar
  return category.name ?? category.slug ?? String(category.id)
}

export function PublicMarketSection() {
  const { t, i18n } = useTranslation()
  const [listings, setListings] = useState<PublicListing[]>([])
  const [categories, setCategories] = useState<PublicMarketCategory[]>([])
  const [categoryId, setCategoryId] = useState<number | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    Promise.all([
      fetchPublicListings(categoryId ? { category_id: categoryId } : {}),
      fetchPublicCategories().catch(() => ({ data: [] as PublicMarketCategory[] })),
    ])
      .then(([listingRes, categoryRes]) => {
        if (cancelled) return
        setListings(listingRes.data ?? [])
        const rows = Array.isArray(categoryRes)
          ? categoryRes
          : categoryRes.data ?? []
        setCategories(rows)
        setError(null)
      })
      .catch((err) => {
        if (cancelled) return
        setError(err instanceof Error ? err.message : t('market.loadFailed'))
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })
    return () => {
      cancelled = true
    }
  }, [categoryId, t])

  const language = i18n.language ?? 'en'

  return (
    <section id="market" className="gs-section gs-section-white gs-market-section" aria-labelledby="market-title">
      <div className="gs-container">
        <div className="gs-section-header">
          <div className="gs-section-eyebrow">
            <span className="gs-section-line" />
            <span>{t('website.nav.market')}</span>
          </div>
          <h2 id="market-title">{t('market.publicTitle')}</h2>
          <p className="gs-section-subtitle">{t('market.publicSubtitle')}</p>
        </div>

        {categories.length > 0 && (
          <div className="gs-market-chips" role="tablist" aria-label={t('market.categories')}>
            <button
              type="button"
              className={`gs-market-chip${categoryId === null ? ' active' : ''}`}
              aria-pressed={categoryId === null}
              onClick={() => setCategoryId(null)}
            >
              {t('market.allCategories')}
            </button>
            {categories.map((category) => (
              <button
                key={category.id}
                type="button"
                className={`gs-market-chip${categoryId === category.id ? ' active' : ''}`}
                aria-pressed={categoryId === category.id}
                onClick={() => setCategoryId(category.id)}
              >
                {categoryLabel(category, language)}
              </button>
            ))}
          </div>
        )}

        {loading && <p>{t('common.loading')}</p>}
        {error && <p role="alert">{error}</p>}
        {!loading && !error && listings.length === 0 && <p>{t('market.emptyListings')}</p>}

        <div className="gs-product-grid">
          {listings.map((listing) => {
            const sellerName = listing.seller?.display_name
            const place = [listing.city, listing.country].filter(Boolean).join(', ')
            const listingCategory = listing.category
              ? categoryLabel(listing.category, language)
              : null

            return (
              <article key={listing.id} className="gs-product-card">
                <div className="gs-product-card-body">
                  {listingCategory && <span className="gs-market-meta">{listingCategory}</span>}
                  <h3>{listing.title}</h3>
                  {listing.description && <p>{listing.description}</p>}
                  {sellerName && (
                    <p className="gs-market-seller">
                      {t('market.seller')}: {sellerName}
                    </p>
                  )}
                  {place && <p className="gs-market-seller">{place}</p>}
                  <Link to={`/market/${listing.id}`} className="gs-btn gs-btn-primary">
                    {t('market.viewDetails')}
                  </Link>
                </div>
              </article>
            )
          })}
        </div>
      </div>
    </section>
  )
}
