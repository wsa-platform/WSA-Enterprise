import { PRODUCT_AVAILABILITIES, type PublicListing, type PublicListingImage, type ProductAvailability } from '../api/marketplace'

const CONTACT_KEYS = [
  'contact',
  'seller_email',
  'seller_phone',
  'seller_whatsapp',
  'whatsapp',
  'address',
  'seller_address',
  'phone',
  'email',
  'contact_access_required',
  'contact_access_price',
  'contact_access_currency',
] as const

export function listingImageUrl(path?: string | null): string | null {
  if (!path) return null
  if (/^https?:\/\//i.test(path)) return path
  if (path.startsWith('/')) return path
  return `/storage/${path}`
}

export function listingImages(listing: PublicListing): PublicListingImage[] {
  return (listing.images ?? []).filter((image) => Boolean(image.path))
}

export function primaryListingImage(listing: PublicListing): string | null {
  return listingImageUrl(listingImages(listing)[0]?.path)
}

/** Public catalog/detail: drop contact and contact-unlock fields. */
export function toPublicProduct(listing: PublicListing): PublicListing {
  const product = { ...listing }
  for (const key of CONTACT_KEYS) {
    delete (product as Record<string, unknown>)[key]
  }
  if (product.seller) {
    const { display_name, country, city, region, seller_type, verified } = product.seller
    product.seller = { display_name, country, city, region, seller_type, verified }
  }
  return product
}

export function formatQuantity(value: string | number | null | undefined): string | null {
  if (value === null || value === undefined || value === '') return null
  return String(value)
}

export function parseSpecificationLines(text: string): Record<string, string> | null {
  const entries = text
    .split('\n')
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => {
      const separator = line.indexOf(':')
      if (separator <= 0) return null
      const key = line.slice(0, separator).trim()
      const value = line.slice(separator + 1).trim()
      if (!key || !value) return null
      return [key, value] as const
    })
    .filter((entry): entry is readonly [string, string] => entry !== null)

  if (entries.length === 0) return null
  return Object.fromEntries(entries)
}

export function specificationLines(specifications: Record<string, unknown> | null | undefined): string {
  if (!specifications) return ''
  return Object.entries(specifications)
    .map(([key, value]) => `${key}: ${value == null ? '' : String(value)}`)
    .join('\n')
}

export function isProductAvailability(value: string | null | undefined): value is ProductAvailability {
  return Boolean(value && (PRODUCT_AVAILABILITIES as readonly string[]).includes(value))
}

export function availabilityI18nKey(value: string | null | undefined): string | null {
  return isProductAvailability(value) ? `market.availability.${value}` : null
}
