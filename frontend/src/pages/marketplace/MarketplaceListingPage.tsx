import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router-dom'
import { fetchPublicListing, type PublicListing } from '../../api/marketplace'
import { countryDisplayName } from '../../marketplace/isoCountries'
import { availabilityI18nKey, listingImageUrl, listingImages, specificationLines, toPublicProduct } from '../../marketplace/productDisplay'
import { publicPaths } from '../../navigation/paths'
import { PublicLayout } from '../../public/PublicLayout'

export function MarketplaceListingPage() {
  const { t, i18n } = useTranslation()
  const { id } = useParams()
  const [listing, setListing] = useState<PublicListing | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const language = i18n.language ?? 'ar'

  useEffect(() => {
    if (!id) return
    let cancelled = false
    setLoading(true)
    fetchPublicListing(Number(id))
      .then((data) => {
        if (cancelled) return
        setListing(toPublicProduct(data))
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
  }, [id, t])

  const product = listing ? toPublicProduct(listing) : null
  const images = product ? listingImages(product) : []
  const category = product?.category
    ? (language.startsWith('ar') && product.category.name_ar ? product.category.name_ar : product.category.name)
    : null
  const unit = product?.unit
    ? (language.startsWith('ar') && product.unit.name_ar ? product.unit.name_ar : product.unit.name)
    : null
  const specText = specificationLines(product?.specifications ?? null)
  const availabilityKey = availabilityI18nKey(product?.availability)

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
                    return src ? <img key={image.path} src={src} alt={image.alt_text || product.title} /> : null
                  })}
                </div>
              )}
              <h1>{product.title}</h1>
              {product.brand && <p className="gs-market-brand">{product.brand}</p>}
              {product.description && <p className="gs-market-description">{product.description}</p>}
              {product.video_url && (
                <p>
                  <a href={product.video_url} target="_blank" rel="noreferrer">{t('market.video')}</a>
                </p>
              )}
              <dl className="gs-market-specs">
                {category && <><dt>{t('market.category')}</dt><dd>{category}</dd></>}
                {product.product_type && <><dt>{t('market.productType')}</dt><dd>{product.product_type}</dd></>}
                {product.origin_country && <><dt>{t('market.originCountry')}</dt><dd>{countryDisplayName(product.origin_country, language)}</dd></>}
                {(product.seller_type ?? product.seller?.seller_type) && (
                  <><dt>{t('market.sellerType')}</dt><dd>{(product.seller_type ?? product.seller?.seller_type) === 'international' ? t('market.sellerInternational') : t('market.sellerLocal')}</dd></>
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
                <><dt>{t('market.exportReady')}</dt><dd>{product.export_ready ? t('common.yes') : t('common.no')}</dd></>
                {product.packaging && <><dt>{t('market.packaging')}</dt><dd>{product.packaging}</dd></>}
                {product.shipping_terms && <><dt>{t('market.shippingTerms')}</dt><dd>{product.shipping_terms}</dd></>}
                {product.lead_time_days != null && <><dt>{t('market.leadTimeDays')}</dt><dd>{product.lead_time_days}</dd></>}
                {product.price != null && <><dt>{t('market.price')}</dt><dd>{String(product.price)} {product.currency ?? ''}</dd></>}
              </dl>
              {specText && (
                <section>
                  <h2>{t('market.specifications')}</h2>
                  <pre className="gs-market-spec-block">{specText}</pre>
                </section>
              )}
            </article>
          )}
        </div>
      </section>
    </PublicLayout>
  )
}
