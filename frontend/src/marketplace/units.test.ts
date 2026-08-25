import { describe, expect, it } from 'vitest'
import {
  CANONICAL_MARKET_UNITS,
  mergeMarketUnits,
  productNameFromListing,
  unitOptionLabel,
} from './units'

describe('marketplace units', () => {
  it('includes the required Arabic unit catalog', () => {
    const labels = CANONICAL_MARKET_UNITS.map((unit) => unit.name_ar)
    expect(labels).toEqual([
      'طن',
      'طن متري',
      'كجم',
      'جرام',
      'لتر',
      'مل',
      'متر',
      'متر مربع',
      'متر مكعب',
      'سم',
      'قطعة',
      'حبة',
      'كيس',
      'صندوق',
      'عبوة',
      'زجاجة',
      'برميل',
      'أخرى',
    ])
  })

  it('uses API ids when merging the selectable unit list', () => {
    const options = mergeMarketUnits([
      { id: 4, slug: 'kg', name: 'Kilogram', name_ar: 'كجم' },
      { id: 9, slug: 'bag', name: 'Bag', name_ar: 'كيس' },
      { id: 99, slug: 'sack', name: 'Sack', name_ar: 'شوال' },
    ])
    expect(options.find((unit) => unit.slug === 'kg')?.id).toBe(4)
    expect(options.find((unit) => unit.slug === 'bag')?.id).toBe(9)
    expect(options.find((unit) => unit.slug === 'ton')).toBeUndefined()
    expect(options.find((unit) => unit.slug === 'sack')?.id).toBe(99)
    expect(unitOptionLabel({ slug: 'kg', name: 'Kilogram', name_ar: 'كجم' }, 'ar')).toBe('كجم')
  })

  it('populates the seller unit dropdown from the canonical catalog when API ids exist', () => {
    const apiUnits = CANONICAL_MARKET_UNITS.map((unit, index) => ({
      id: index + 1,
      slug: unit.slug,
      name: unit.name,
      name_ar: unit.name_ar,
    }))
    const options = mergeMarketUnits(apiUnits)
    expect(options.map((unit) => unit.name_ar)).toEqual(CANONICAL_MARKET_UNITS.map((unit) => unit.name_ar))
    expect(options.every((unit) => unit.id > 0)).toBe(true)
  })

  it('keeps the seller-entered product name as the public title', () => {
    expect(productNameFromListing({ title: 'أرز مصري' })).toBe('أرز مصري')
    expect(productNameFromListing({ title: '  نوتيلا إيطالي  ' })).toBe('نوتيلا إيطالي')
  })
})
