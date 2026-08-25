import { describe, expect, it } from 'vitest'
import {
  availabilityI18nKey,
  listingImageUrl,
  parseSpecificationLines,
  specificationLines,
  toPublicProduct,
} from './productDisplay'

describe('productDisplay', () => {
  it('builds storage URLs for relative image paths', () => {
    expect(listingImageUrl('products/tomato.jpg')).toBe('/storage/products/tomato.jpg')
    expect(listingImageUrl('/uploads/apple.png')).toBe('/uploads/apple.png')
    expect(listingImageUrl('https://cdn.example/olive.jpg')).toBe('https://cdn.example/olive.jpg')
    expect(listingImageUrl(null)).toBeNull()
  })

  it('strips contact fields from public product payloads', () => {
    const product = toPublicProduct({
      id: 7,
      title: 'Dates',
      seller_email: 'hidden@example.com',
      seller_phone: '0500000000',
      seller_whatsapp: '0500000000',
      whatsapp: '0500000000',
      address: 'Hidden street',
      seller_address: 'Hidden street',
      email: 'hidden@example.com',
      phone: '0500000000',
      contact_access_required: true,
      contact_access_price: 25,
      contact_access_currency: 'SAR',
      contact: { seller_email: 'hidden@example.com' },
      seller: {
        display_name: 'Oasis Farm',
        country: 'SA',
        city: 'Al-Ahsa',
        region: 'Eastern',
        seller_type: 'local',
        verified: true,
        email: 'hidden@example.com',
      } as { display_name: string; country: string; city: string; region: string; seller_type: string; verified: boolean },
    })

    expect(product.title).toBe('Dates')
    expect(product.seller?.display_name).toBe('Oasis Farm')
    expect(product.seller).not.toHaveProperty('email')
    expect(product).not.toHaveProperty('seller_email')
    expect(product).not.toHaveProperty('seller_phone')
    expect(product).not.toHaveProperty('seller_whatsapp')
    expect(product).not.toHaveProperty('whatsapp')
    expect(product).not.toHaveProperty('address')
    expect(product).not.toHaveProperty('seller_address')
    expect(product).not.toHaveProperty('email')
    expect(product).not.toHaveProperty('phone')
    expect(product).not.toHaveProperty('contact')
    expect(product).not.toHaveProperty('contact_access_required')
    expect(product).not.toHaveProperty('contact_access_price')
    expect(product).not.toHaveProperty('contact_access_currency')
  })

  it('parses and serializes specification lines', () => {
    const parsed = parseSpecificationLines('variety: Valencia\ngrade: A\n')
    expect(parsed).toEqual({ variety: 'Valencia', grade: 'A' })
    expect(specificationLines(parsed)).toBe('variety: Valencia\ngrade: A')
    expect(parseSpecificationLines('')).toBeNull()
  })

  it('maps only approved availability values', () => {
    expect(availabilityI18nKey('available_now')).toBe('market.availability.available_now')
    expect(availabilityI18nKey('seasonal')).toBe('market.availability.seasonal')
    expect(availabilityI18nKey('made_to_order')).toBe('market.availability.made_to_order')
    expect(availabilityI18nKey('on_demand')).toBe('market.availability.made_to_order')
    expect(availabilityI18nKey('unavailable')).toBe('market.availability.unavailable')
    expect(availabilityI18nKey('new')).toBeNull()
  })
})
