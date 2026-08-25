import { describe, expect, it, vi } from 'vitest'
import {
  completeContactPayment,
  contactFromPaidResult,
  listingHasVisibleContact,
  publicListingsOnly,
  showContactClickAction,
} from './contactUnlock'

describe('marketplace contact unlock', () => {
  it('hides unpublished products from the public catalog', () => {
    const visible = publicListingsOnly([
      { id: 1, title: 'أرز مصري', status: 'published' },
      { id: 2, title: 'Draft rice', status: 'draft' },
      { id: 3, title: 'Hidden rice', status: 'unpublished' },
    ])
    expect(visible.map((listing) => listing.title)).toEqual(['أرز مصري'])
  })

  it('does not treat unpaid or empty payment results as unlocked contact', () => {
    expect(contactFromPaidResult({ order: { payment_status: 'pending' }, contact: { seller_email: 'a@b.c' } })).toBeNull()
    expect(contactFromPaidResult({ order: { payment_status: 'paid' }, contact: {} })).toBeNull()
    expect(contactFromPaidResult({
      order: { payment_status: 'paid' },
      contact: {
        seller_display_name: 'Oasis Farm',
        seller_email: 'seller@wsa.test',
        seller_phone: '+966512345678',
      },
    })).toEqual({
      seller_display_name: 'Oasis Farm',
      seller_email: 'seller@wsa.test',
      seller_phone: '+966512345678',
    })
  })

  it('does not show contact on the initial public product payload', () => {
    expect(listingHasVisibleContact({ id: 7, title: 'أرز مصري' })).toBe(false)
    expect(listingHasVisibleContact({ id: 7, title: 'أرز مصري', contact: { seller_email: 'hidden@wsa.test' } })).toBe(true)
  })

  it('opens login with a return to the product, then payment after authentication', () => {
    expect(showContactClickAction(false, 7)).toEqual({
      kind: 'login',
      href: '/login?next=%2Fmarket%2F7',
    })
    expect(showContactClickAction(true, 7)).toEqual({ kind: 'payment' })
  })

  it('returns contact only after the pay API reports a successful payment', async () => {
    const requestContactAccess = vi.fn().mockResolvedValue({ id: 22, payment_status: 'pending' })
    const payContactAccess = vi.fn().mockResolvedValue({
      order: { payment_status: 'paid' },
      contact: {
        seller_display_name: 'Oasis Farm',
        seller_email: 'seller@wsa.test',
        seller_phone: '+966500000000',
      },
    })
    const result = await completeContactPayment({
      listingId: 7,
      token: 'tok',
      requestKey: 'req-1',
      payKey: 'pay-1',
      requestContactAccess,
      payContactAccess,
    })
    expect(result).toEqual({
      ok: true,
      contact: {
        seller_display_name: 'Oasis Farm',
        seller_email: 'seller@wsa.test',
        seller_phone: '+966500000000',
      },
    })
    expect(requestContactAccess).toHaveBeenCalledWith(7, 'tok', 'req-1')
    expect(payContactAccess).toHaveBeenCalledWith(22, 'tok', 'pay-1')
  })

  it('does not reveal contact when payment is not paid', async () => {
    const result = await completeContactPayment({
      listingId: 7,
      token: 'tok',
      requestKey: 'req-1',
      payKey: 'pay-1',
      requestContactAccess: vi.fn().mockResolvedValue({ id: 22 }),
      payContactAccess: vi.fn().mockResolvedValue({
        order: { payment_status: 'failed' },
        contact: { seller_email: 'seller@wsa.test' },
      }),
    })
    expect(result).toEqual({ ok: false, reason: 'unpaid' })
  })
})
