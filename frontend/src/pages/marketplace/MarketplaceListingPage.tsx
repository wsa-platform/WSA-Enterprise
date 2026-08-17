import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import {
  fetchPublicListing,
  payContactAccess,
  requestContactAccess,
  type PublicListing,
} from '../../api/marketplace'
import { PublicLayout } from '../../public/PublicLayout'

export function MarketplaceListingPage() {
  const { t } = useTranslation()
  const { id } = useParams()
  const { token } = useAuth()
  const [listing, setListing] = useState<PublicListing | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [paying, setPaying] = useState(false)
  const loginNext = `/login?next=${encodeURIComponent(`/market/${id ?? ''}`)}`

  const load = async () => {
    if (!id) return
    setLoading(true)
    try {
      const data = await fetchPublicListing(Number(id), token)
      setListing(data)
      setError(null)
    } catch (err) {
      setError(err instanceof Error ? err.message : t('market.loadFailed'))
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [id, token])

  const handleRequestContact = async () => {
    if (!token || !listing) {
      setError(t('market.loginRequired'))
      return
    }
    setPaying(true)
    setError(null)
    try {
      const order = await requestContactAccess(listing.id, token, `order-${listing.id}-${Date.now()}`)
      const paid = await payContactAccess(order.id as number, token, `pay-${listing.id}-${Date.now()}`)
      if (paid.contact) {
        setListing({ ...listing, contact: paid.contact, contact_access_required: false })
      } else {
        setError(t('market.paymentFailed'))
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : t('market.paymentFailed'))
    } finally {
      setPaying(false)
    }
  }

  return (
    <PublicLayout>
      <section className="gs-section">
        <div className="gs-container">
          <Link to="/market">← {t('market.backToMarket')}</Link>
          {loading && <p>{t('common.loading')}</p>}
          {error && <p role="alert">{error}</p>}
          {listing && (
            <article className="gs-card">
              <h1>{listing.title}</h1>
              <p>{listing.description}</p>
              <p>
                {t('market.seller')}: {listing.seller?.display_name ?? '—'} — {listing.city} {listing.country}
              </p>
              {listing.contact ? (
                <div>
                  <h2>{t('market.contactDetails')}</h2>
                  <p>{t('common.email')}: {listing.contact.seller_email ?? '—'}</p>
                  <p>{t('market.sellerPhone')}: {listing.contact.seller_phone ?? '—'}</p>
                </div>
              ) : (
                <div>
                  <p>{t('market.contactProtected', { price: listing.contact_access_price ?? '—', currency: listing.contact_access_currency ?? 'SAR' })}</p>
                  {!token ? (
                    <Link to={loginNext} className="gs-btn gs-btn-primary">
                      {t('market.loginToRequest')}
                    </Link>
                  ) : (
                    <button type="button" className="gs-btn gs-btn-primary" disabled={paying} onClick={() => void handleRequestContact()}>
                      {paying ? t('market.paying') : t('market.requestContact')}
                    </button>
                  )}
                </div>
              )}
            </article>
          )}
        </div>
      </section>
    </PublicLayout>
  )
}
