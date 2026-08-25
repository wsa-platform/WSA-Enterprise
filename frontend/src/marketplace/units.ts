import type { PublicMarketUnit } from '../api/marketplace'

export type CanonicalMarketUnit = {
  slug: string
  name: string
  name_ar: string
}

/** Canonical seller unit catalog. API rows are preferred when present. */
export const CANONICAL_MARKET_UNITS: CanonicalMarketUnit[] = [
  { slug: 'ton', name: 'Ton', name_ar: 'طن' },
  { slug: 'metric_ton', name: 'Metric ton', name_ar: 'طن متري' },
  { slug: 'kg', name: 'Kilogram', name_ar: 'كجم' },
  { slug: 'gram', name: 'Gram', name_ar: 'جرام' },
  { slug: 'liter', name: 'Liter', name_ar: 'لتر' },
  { slug: 'ml', name: 'Milliliter', name_ar: 'مل' },
  { slug: 'meter', name: 'Meter', name_ar: 'متر' },
  { slug: 'square_meter', name: 'Square meter', name_ar: 'متر مربع' },
  { slug: 'cubic_meter', name: 'Cubic meter', name_ar: 'متر مكعب' },
  { slug: 'cm', name: 'Centimeter', name_ar: 'سم' },
  { slug: 'piece', name: 'Piece', name_ar: 'قطعة' },
  { slug: 'each', name: 'Each', name_ar: 'حبة' },
  { slug: 'bag', name: 'Bag', name_ar: 'كيس' },
  { slug: 'box', name: 'Box', name_ar: 'صندوق' },
  { slug: 'pack', name: 'Pack', name_ar: 'عبوة' },
  { slug: 'bottle', name: 'Bottle', name_ar: 'زجاجة' },
  { slug: 'barrel', name: 'Barrel', name_ar: 'برميل' },
  { slug: 'other', name: 'Other', name_ar: 'أخرى' },
]

export type MarketUnitOption = {
  id: number
  slug: string
  name: string
  name_ar: string
}

export function mergeMarketUnits(apiUnits: PublicMarketUnit[] = []): MarketUnitOption[] {
  const bySlug = new Map<string, PublicMarketUnit>()
  for (const unit of apiUnits) {
    if (unit.slug) bySlug.set(unit.slug, unit)
  }

  const merged: MarketUnitOption[] = CANONICAL_MARKET_UNITS.map((canonical) => {
    const match = bySlug.get(canonical.slug)
    return {
      id: typeof match?.id === 'number' ? match.id : 0,
      slug: canonical.slug,
      name: match?.name || canonical.name,
      name_ar: match?.name_ar || canonical.name_ar,
    }
  })

  for (const unit of apiUnits) {
    if (!unit.slug || CANONICAL_MARKET_UNITS.some((entry) => entry.slug === unit.slug)) continue
    merged.push({
      id: unit.id,
      slug: unit.slug,
      name: unit.name ?? unit.slug,
      name_ar: unit.name_ar ?? unit.name ?? unit.slug,
    })
  }

  return merged.filter((unit) => unit.id > 0)
}

export function unitOptionLabel(unit: Pick<MarketUnitOption, 'name' | 'name_ar' | 'slug'>, language: string): string {
  if (language.startsWith('ar') && unit.name_ar) return unit.name_ar
  return unit.name || unit.slug
}

export function productNameFromListing(listing: { title?: string | null }): string {
  return (listing.title ?? '').trim()
}
