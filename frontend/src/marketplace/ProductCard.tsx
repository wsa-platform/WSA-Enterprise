import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'
import type { PublicListing } from '../api/marketplace'
import { publicPaths } from '../navigation/paths'
import { countryDisplayName } from './isoCountries'
import { sellerTypeLabelKey } from './listingForm'
import { productCategoryLabel, isProductCategorySlug } from './productCategories'
import { availabilityI18nKey, formatQuantity, primaryListingImage, toPublicProduct } from './productDisplay'

function unitLabel(listing: PublicListing, language: string) {
  const unit = listing.unit
  if (!unit) return null
  if (language.startsWith('ar') && unit.name_ar) return unit.name_ar
  return unit.name ?? unit.slug ?? null
}

export function ProductCard({ listing }: { listing: PublicListing }) {
  const { t, i18n } = useTranslation()
  const product = toPublicProduct(listing)
  const language = i18n.language ?? 'ar'
  const image = primaryListingImage(product)
  const category = product.category
    ? (language.startsWith('ar') && product.category.name_ar ? product.category.name_ar : product.category.name)
      || productCategoryLabel(product.category.slug ?? product.product_type ?? '', language)
    : productCategoryLabel(product.product_type ?? '', language) || null
  const origin = countryDisplayName(product.origin_country, language)
  const sellerTypeValue = product.seller_type ?? product.seller?.seller_type
  const sellerCountry = countryDisplayName(product.seller_country ?? product.country ?? product.seller?.country, language)
  const sellerTypeKey = sellerTypeLabelKey(sellerTypeValue)
  const sellerType = sellerTypeKey ? t(sellerTypeKey) : null
  const availabilityKey = availabilityI18nKey(product.availability)
  const availability = availabilityKey ? t(availabilityKey) : null
  const unit = unitLabel(product, language)
  const city = product.city ?? product.seller?.city
  const region = product.seller_region ?? product.seller?.region
  const place = [city, region, sellerCountry].filter(Boolean).join(' · ')
  const minOrder = formatQuantity(product.min_order_quantity)
  const availableQty = formatQuantity(product.available_quantity)
  const production = formatQuantity(product.production_capacity)

  return (
    <article className="gs-product-card gs-market-product-card">
      <div className="gs-product-card-media" aria-hidden={image ? undefined : true}>
        {image ? (
          <img src={image} alt="" />
        ) : (
          <span className="gs-product-card-fallback">🌿</span>
        )}
      </div>
      <div className="gs-product-card-body">
        {category && <span className="gs-market-meta">{category}</span>}
        <h3>{product.title}</h3>
        {product.brand && <p className="gs-market-brand">{product.brand}</p>}
        {product.product_type && !isProductCategorySlug(product.product_type) && (
          <p className="gs-market-seller">{t('market.productType')}: {product.product_type}</p>
        )}
        <ul className="gs-market-facts">
          {origin && <li>{t('market.originCountry')}: {origin}</li>}
          {place && <li>{t('market.sellerLocation')}: {place}</li>}
          {sellerType && <li>{t('market.sellerType')}: {sellerType}</li>}
          {availability && <li>{t('market.availabilityLabel')}: {availability}</li>}
          {unit && <li>{t('market.unit')}: {unit}</li>}
          {minOrder && <li>{t('market.minOrderQuantity')}: {minOrder}</li>}
          {availableQty && <li>{t('market.availableQuantity')}: {availableQty}</li>}
          {production && <li>{t('market.productionCapacity')}: {production}</li>}
        </ul>
        <p className="gs-market-flags">
          {product.wholesale && <span>{t('market.wholesale')}</span>}
          {product.retail && <span>{t('market.retail')}</span>}
          {product.export_ready && <span>{t('market.exportReady')}</span>}
        </p>
        <Link to={publicPaths.listing(product.id)} className="gs-btn gs-btn-primary">
          {t('market.viewDetails')}
        </Link>
      </div>
    </article>
  )
}
