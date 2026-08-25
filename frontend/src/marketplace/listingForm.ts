import type { MarketplaceListingWrite, ProductAvailability, ProductSellerType, ProductSellerTypeFlag, PublicMarketCategory } from '../api/marketplace'
import { PRODUCT_AVAILABILITIES } from '../api/marketplace'
import { countryForCurrency, isMappedCurrency, resolveCurrencyCountry } from './currencyCountries'
import { isProductCategorySlug, resolveCategorySlug } from './productCategories'
import { isValidSellerEmail, normalizeSellerEmail, splitE164Phone, toE164Phone } from './phone'

export type SellerTypeSelection = {
  local: boolean
  international: boolean
}

export type ListingEditorValues = {
  title: string
  description: string
  categorySlug: string
  productType: string
  brand: string
  sellerLocal: boolean
  sellerInternational: boolean
  availability: string
  price: string
  currency: string
  originCountry: string
  city: string
  sellerRegion: string
  unitId: string
  minOrderQuantity: string
  availableQuantity: string
  productionCapacity: string
  wholesale: boolean
  retail: boolean
  exportReady: boolean
  packaging: string
  specificationsText: string
  callingCode: string
  nationalPhone: string
  sellerEmail: string
}

export const REMOVED_LISTING_FIELDS = [
  'shipping_terms',
  'lead_time_days',
  'video_url',
  'model',
  'model_number',
  'condition',
  'export_countries',
  'export_destination',
  'export_regions',
] as const

export function sellerTypesFromSelection(selection: SellerTypeSelection): ProductSellerTypeFlag[] {
  const types: ProductSellerTypeFlag[] = []
  if (selection.local) types.push('local')
  if (selection.international) types.push('international')
  return types
}

export function sellerSelectionFromApi(value?: string | string[] | null): SellerTypeSelection {
  if (Array.isArray(value)) {
    const set = new Set(value.map((entry) => String(entry).trim().toLowerCase()))
    return { local: set.has('local'), international: set.has('international') }
  }
  const normalized = (value ?? '').trim().toLowerCase()
  if (!normalized) return { local: false, international: false }
  if (
    normalized === 'both' ||
    normalized === 'local_international' ||
    (normalized.includes('local') && normalized.includes('international'))
  ) {
    return { local: true, international: true }
  }
  if (normalized === 'international') return { local: false, international: true }
  if (normalized === 'local') return { local: true, international: false }
  return { local: false, international: false }
}

export function sellerSelectionToApi(selection: SellerTypeSelection): ProductSellerType | null {
  if (selection.local && selection.international) return 'both'
  if (selection.local) return 'local'
  if (selection.international) return 'international'
  return null
}

export function sellerTypeLabelKey(
  value?: string | string[] | null,
): 'market.sellerBoth' | 'market.sellerInternational' | 'market.sellerLocal' | null {
  const selection = sellerSelectionFromApi(value)
  if (selection.local && selection.international) return 'market.sellerBoth'
  if (selection.international) return 'market.sellerInternational'
  if (selection.local) return 'market.sellerLocal'
  return value ? 'market.sellerLocal' : null
}

export function canonicalAvailability(value?: string | null): ProductAvailability | '' {
  const normalized = (value ?? '').trim().toLowerCase()
  if (normalized === 'on_demand' || normalized === 'made_to_order') return 'made_to_order'
  if ((PRODUCT_AVAILABILITIES as readonly string[]).includes(normalized)) {
    return normalized as ProductAvailability
  }
  return ''
}

function numberOrNull(value: string): number | null {
  const trimmed = value.trim()
  if (!trimmed) return null
  const parsed = Number(trimmed)
  return Number.isFinite(parsed) ? parsed : null
}

export function validateListingEditor(
  values: Pick<ListingEditorValues, 'title' | 'categorySlug' | 'sellerLocal' | 'sellerInternational' | 'currency' | 'callingCode' | 'nationalPhone' | 'sellerEmail'>,
  knownCategorySlugs: string[] = [],
): string[] {
  const errors: string[] = []
  if (!values.title.trim()) errors.push('market.productName')
  const slug = values.categorySlug.trim()
  if (!slug || (!isProductCategorySlug(slug) && !knownCategorySlugs.includes(slug))) {
    errors.push('market.categoryRequired')
  }
  if (!values.sellerLocal && !values.sellerInternational) errors.push('market.sellerTypeRequired')
  if (!isMappedCurrency(values.currency)) errors.push('market.currencyRequired')
  if (!values.callingCode.trim() || !values.nationalPhone.trim()) errors.push('market.sellerPhoneRequired')
  else if (!toE164Phone(values.callingCode, values.nationalPhone)) errors.push('market.sellerPhoneInvalid')
  if (!normalizeSellerEmail(values.sellerEmail)) errors.push('market.sellerEmailRequired')
  else if (!isValidSellerEmail(values.sellerEmail)) errors.push('market.sellerEmailInvalid')
  return errors
}

export function categoryIdForSlug(slug: string, categories: PublicMarketCategory[]): number | null {
  const match = categories.find((category) => category.slug === slug)
  return typeof match?.id === 'number' ? match.id : null
}

export function hydrateListingEditor(listing: {
  seller_type?: string | null
  seller_types?: string[] | null
  currency?: string | null
  country?: string | null
  seller_country?: string | null
  product_type?: string | null
  category?: { slug?: string | null } | null
  seller_email?: string | null
  seller_phone?: string | null
  availability?: string | null
  unit?: { id?: number | null } | null
  unit_id?: number | null
}): {
  categorySlug: string
  sellerLocal: boolean
  sellerInternational: boolean
  currency: string
  country: string
  matchedCurrency: boolean
  callingCode: string
  nationalPhone: string
  sellerEmail: string
  availability: ProductAvailability | ''
  unitId: string
} {
  const seller = sellerSelectionFromApi(listing.seller_types ?? listing.seller_type)
  const resolved = resolveCurrencyCountry(listing.currency, listing.country ?? listing.seller_country)
  const phone = splitE164Phone(listing.seller_phone)
  const unitId = listing.unit?.id ?? listing.unit_id
  return {
    categorySlug: resolveCategorySlug({ category: listing.category, product_type: listing.product_type }),
    sellerLocal: seller.local,
    sellerInternational: seller.international,
    currency: resolved.matched ? resolved.currency : (listing.currency ?? '').trim().toUpperCase(),
    country: resolved.country,
    matchedCurrency: resolved.matched,
    callingCode: phone.dial,
    nationalPhone: phone.national,
    sellerEmail: listing.seller_email ?? '',
    availability: canonicalAvailability(listing.availability),
    unitId: unitId != null ? String(unitId) : '',
  }
}

export function buildListingWritePayload(
  values: ListingEditorValues,
  options: {
    categories: PublicMarketCategory[]
    sellerDisplayName?: string
    specifications?: Record<string, unknown> | null
  },
): { payload: MarketplaceListingWrite; errors: string[] } {
  const knownSlugs = options.categories.map((category) => category.slug).filter((slug): slug is string => Boolean(slug))
  const errors = validateListingEditor(values, knownSlugs)
  const sellerSelection = {
    local: values.sellerLocal,
    international: values.sellerInternational,
  }
  const sellerType = sellerSelectionToApi(sellerSelection)
  const sellerTypes = sellerTypesFromSelection(sellerSelection)
  const country = countryForCurrency(values.currency)
  if (!sellerType || sellerTypes.length === 0) errors.push('market.sellerTypeRequired')
  if (!country) errors.push('market.currencyRequired')

  const payload: MarketplaceListingWrite = {
    title: values.title.trim(),
    description: values.description || undefined,
    product_type: values.productType.trim() || (isProductCategorySlug(values.categorySlug) ? values.categorySlug : null),
    brand: values.brand.trim() || null,
    seller_type: sellerType ?? undefined,
    seller_types: sellerTypes.length > 0 ? sellerTypes : undefined,
    availability: canonicalAvailability(values.availability) || null,
    unit_id: numberOrNull(values.unitId),
    seller_display_name: options.sellerDisplayName,
    price: numberOrNull(values.price),
    currency: values.currency || undefined,
    country: country ?? undefined,
    origin_country: values.originCountry || null,
    city: values.city || undefined,
    seller_region: values.sellerRegion.trim() || null,
    category_id: categoryIdForSlug(values.categorySlug, options.categories),
    export_ready: values.exportReady,
    min_order_quantity: numberOrNull(values.minOrderQuantity),
    available_quantity: numberOrNull(values.availableQuantity),
    production_capacity: numberOrNull(values.productionCapacity),
    wholesale: values.wholesale,
    retail: values.retail,
    packaging: values.packaging.trim() || null,
    specifications: options.specifications ?? null,
    seller_email: normalizeSellerEmail(values.sellerEmail),
    seller_phone: toE164Phone(values.callingCode, values.nationalPhone),
  }

  const record = payload as Record<string, unknown>
  for (const field of REMOVED_LISTING_FIELDS) {
    delete record[field]
  }

  return { payload, errors: [...new Set(errors)] }
}
