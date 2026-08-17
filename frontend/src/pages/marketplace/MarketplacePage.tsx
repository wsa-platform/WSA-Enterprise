import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import { fetchPublicListings, type PublicListing } from '../../api/marketplace'
import { PublicLayout } from '../../public/PublicLayout'

export function MarketplacePage() {
  const { t } = useTranslation()
  const [listings, setListings] = useState<PublicListing[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    fetchPublicListings()
      .then((res) => setListings(res.data ?? []))
      .catch((err) => setError(err instanceof Error ? err.message : t('market.loadFailed')))
      .finally(() => setLoading(false))
  }, [t])

  return (
    <PublicLayout>
      <section className="gs-section">
        <div className="gs-container">
          <h1>{t('market.publicTitle')}</h1>
          <p>{t('market.publicSubtitle')}</p>
          <p>
            <Link to="/seller/listings" className="gs-btn gs-btn-ghost">{t('website.nav.sell')}</Link>
          </p>
          {loading && <p>{t('common.loading')}</p>}
          {error && <p role="alert">{error}</p>}
          <div className="gs-card-grid">
            {listings.map((listing) => (
              <article key={listing.id} className="gs-card">
                <h2>{listing.title}</h2>
                <p>{listing.description}</p>
                <p>
                  {listing.city ?? ''} {listing.country ?? ''}
                </p>
                {listing.price && (
                  <p>
                    {listing.price} {listing.currency}
                  </p>
                )}
                <Link to={`/market/${listing.id}`} className="gs-btn gs-btn-primary">
                  {t('market.viewDetails')}
                </Link>
              </article>
            ))}
          </div>
        </div>
      </section>
    </PublicLayout>
  )
}
