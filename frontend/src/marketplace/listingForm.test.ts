import { describe, expect, it } from 'vitest'
import {
  REMOVED_LISTING_FIELDS,
  buildListingWritePayload,
  sellerSelectionFromApi,
  sellerSelectionToApi,
  sellerTypeLabelKey,
  validateListingEditor,
  type ListingEditorValues,
} from './listingForm'

function values(overrides: Partial<ListingEditorValues> = {}): ListingEditorValues {
  return {
    title: 'Tomatoes',
    description: 'Fresh crop',
    categorySlug: 'vegetables',
    productType: '',
    brand: 'Oasis',
    sellerLocal: true,
    sellerInternational: false,
    availability: 'available_now',
    price: '10',
    currency: 'SAR',
    originCountry: '',
    city: 'Riyadh',
    sellerRegion: '',
    unitId: '',
    minOrderQuantity: '',
    availableQuantity: '',
    productionCapacity: '',
    wholesale: true,
    retail: false,
    exportReady: false,
    packaging: '',
    specificationsText: '',
    callingCode: '966',
    nationalPhone: '512345678',
    sellerEmail: 'seller@wsa.test',
    ...overrides,
  }
}

describe('listing editor form mapping', () => {
  it('allows local, international, or both seller types and rejects neither', () => {
    expect(sellerSelectionToApi({ local: true, international: false })).toBe('local')
    expect(sellerSelectionToApi({ local: false, international: true })).toBe('international')
    expect(sellerSelectionToApi({ local: true, international: true })).toBe('both')
    expect(sellerSelectionToApi({ local: false, international: false })).toBeNull()
    expect(validateListingEditor(values({ sellerLocal: false, sellerInternational: false }))).toContain('market.sellerTypeRequired')
  })

  it('hydrates legacy seller_type values without crashing', () => {
    expect(sellerSelectionFromApi('local')).toEqual({ local: true, international: false })
    expect(sellerSelectionFromApi('international')).toEqual({ local: false, international: true })
    expect(sellerSelectionFromApi('both')).toEqual({ local: true, international: true })
    expect(sellerSelectionFromApi('weird-legacy')).toEqual({ local: false, international: false })
    expect(sellerSelectionFromApi(['local', 'international'])).toEqual({ local: true, international: true })
    expect(sellerTypeLabelKey('both')).toBe('market.sellerBoth')
    expect(sellerTypeLabelKey(['local'])).toBe('market.sellerLocal')
  })

  it('omits removed fields from create and update payloads', () => {
    const created = buildListingWritePayload(values(), {
      categories: [{ id: 4, slug: 'vegetables', name: 'Vegetables', name_ar: 'الخضروات' }],
      sellerDisplayName: 'Seller',
    })
    const updated = buildListingWritePayload(
      values({ title: 'Updated tomatoes', sellerLocal: true, sellerInternational: true, currency: 'EGP' }),
      { categories: [{ id: 4, slug: 'vegetables' }] },
    )

    expect(created.errors).toEqual([])
    expect(updated.errors).toEqual([])
    expect(created.payload.seller_type).toBe('local')
    expect(created.payload.seller_types).toEqual(['local'])
    expect(created.payload.currency).toBe('SAR')
    expect(created.payload.country).toBe('SA')
    expect(created.payload.seller_email).toBe('seller@wsa.test')
    expect(created.payload.seller_phone).toBe('+966512345678')
    expect(created.payload.availability).toBe('available_now')
    expect(created.payload).not.toHaveProperty('shipping_terms')
    expect(created.payload).not.toHaveProperty('lead_time_days')
    expect(created.payload).not.toHaveProperty('video_url')
    expect(created.payload).not.toHaveProperty('model')
    expect(created.payload).not.toHaveProperty('model_number')
    expect(created.payload).not.toHaveProperty('condition')
    expect(created.payload).not.toHaveProperty('export_countries')
    expect(updated.payload.seller_type).toBe('both')
    expect(updated.payload.seller_types).toEqual(['local', 'international'])
    expect(updated.payload.country).toBe('EG')
    for (const field of REMOVED_LISTING_FIELDS) {
      expect(created.payload).not.toHaveProperty(field)
      expect(updated.payload).not.toHaveProperty(field)
    }
  })

  it('requires a predefined category and mapped currency', () => {
    expect(validateListingEditor(values({ categorySlug: '' }))).toContain('market.categoryRequired')
    expect(validateListingEditor(values({ categorySlug: 'typed-by-hand' }))).toContain('market.categoryRequired')
    expect(validateListingEditor(values({ currency: 'ABC' }))).toContain('market.currencyRequired')
  })

  it('requires international phone and email and rejects invalid values', () => {
    expect(validateListingEditor(values({ callingCode: '', nationalPhone: '512345678' }))).toContain('market.sellerPhoneRequired')
    expect(validateListingEditor(values({ callingCode: '20', nationalPhone: '12' }))).toContain('market.sellerPhoneInvalid')
    expect(validateListingEditor(values({ nationalPhone: 'abc' }))).toContain('market.sellerPhoneInvalid')
    expect(validateListingEditor(values({ sellerEmail: '' }))).toContain('market.sellerEmailRequired')
    expect(validateListingEditor(values({ sellerEmail: 'not-an-email' }))).toContain('market.sellerEmailInvalid')
    expect(validateListingEditor(values({ sellerEmail: '  seller@wsa.test  ' }))).toEqual([])
  })

  it('maps made_to_order availability and unit_id into the write payload', () => {
    const created = buildListingWritePayload(
      values({ availability: 'on_demand', unitId: '8' }),
      { categories: [{ id: 4, slug: 'vegetables', name: 'Vegetables', name_ar: 'الخضروات' }] },
    )
    expect(created.errors).toEqual([])
    expect(created.payload.availability).toBe('made_to_order')
    expect(created.payload.unit_id).toBe(8)
    expect(created.payload).not.toHaveProperty('export_destination')
  })

  it('saves the seller-entered product name as title without publishing', () => {
    const created = buildListingWritePayload(
      values({ title: 'أرز مصري', unitId: '3' }),
      { categories: [{ id: 4, slug: 'vegetables', name: 'Vegetables', name_ar: 'الخضروات' }] },
    )
    expect(created.errors).toEqual([])
    expect(created.payload.title).toBe('أرز مصري')
    expect(created.payload.unit_id).toBe(3)
    expect(created.payload).not.toHaveProperty('status')
    expect(validateListingEditor(values({ title: '   ' }))).toContain('market.productName')
  })
})
