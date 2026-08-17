import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { fetchPublicListings, type PublicListing } from '../../api/marketplace'
import { PublicLayout } from '../../public/PublicLayout'

export function MarketplacePage() {
  const [listings, setListings] = useState<PublicListing[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    fetchPublicListings()
      .then((res) => setListings(res.data ?? []))
      .catch((err) => setError(err instanceof Error ? err.message : 'Failed to load listings'))
      .finally(() => setLoading(false))
  }, [])

  return (
    <PublicLayout>
      <section className="gs-section">
        <div className="gs-container">
          <h1>السوق</h1>
          <p>تصفّح إعلانات المنتجات الزراعية. بيانات التواصل متاحة بعد الدفع.</p>
          {loading && <p>جاري التحميل…</p>}
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
                  عرض التفاصيل
                </Link>
              </article>
            ))}
          </div>
        </div>
      </section>
    </PublicLayout>
  )
}
