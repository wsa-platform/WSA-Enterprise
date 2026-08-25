import type { PublicListing, PublicListingContact, PayContactAccessResult, ContactAccessOrder } from '../api/marketplace'
import { marketplaceLoginHref } from '../navigation/roleDestinations'
import { publicPaths } from '../navigation/paths'

export const CONTACT_PAYMENT_PAID = 'paid'

export function publicListingsOnly(listings: Array<PublicListing & { status?: string | null }>): PublicListing[] {
  return listings.filter((listing) => !listing.status || listing.status === 'published')
}

export function contactFromPaidResult(result: PayContactAccessResult | null | undefined): PublicListingContact | null {
  if (!result || result.order?.payment_status !== CONTACT_PAYMENT_PAID) return null
  const contact = result.contact
  if (!contact) return null
  if (!contact.seller_email && !contact.seller_phone) return null
  return contact
}

export function listingHasVisibleContact(listing: PublicListing | null | undefined): boolean {
  if (!listing) return false
  if (listing.contact?.seller_email || listing.contact?.seller_phone) return true
  return false
}

export function showContactClickAction(
  authenticated: boolean,
  listingId: number,
): { kind: 'login'; href: string } | { kind: 'payment' } {
  if (!authenticated) {
    return { kind: 'login', href: marketplaceLoginHref(publicPaths.listing(listingId)) }
  }
  return { kind: 'payment' }
}

export function isContactAlreadyGrantedError(error: unknown): boolean {
  if (!(error instanceof Error)) return false
  return error.message.toLowerCase().includes('already granted')
}

export async function completeContactPayment(input: {
  listingId: number
  token: string
  requestKey: string
  payKey: string
  requestContactAccess: (listingId: number, token: string, idempotencyKey: string) => Promise<ContactAccessOrder>
  payContactAccess: (orderId: number, token: string, idempotencyKey: string) => Promise<PayContactAccessResult>
  fetchEntitledListing?: (listingId: number, token: string) => Promise<PublicListing>
}): Promise<{ ok: true; contact: PublicListingContact } | { ok: false; reason: 'unpaid' | 'error'; error?: unknown }> {
  try {
    const order = await input.requestContactAccess(input.listingId, input.token, input.requestKey)
    const paid = await input.payContactAccess(order.id, input.token, input.payKey)
    const contact = contactFromPaidResult(paid)
    if (!contact) return { ok: false, reason: 'unpaid' }
    return { ok: true, contact }
  } catch (error) {
    if (isContactAlreadyGrantedError(error) && input.fetchEntitledListing) {
      try {
        const listing = await input.fetchEntitledListing(input.listingId, input.token)
        if (listingHasVisibleContact(listing) && listing.contact) {
          return { ok: true, contact: listing.contact }
        }
      } catch (entitledError) {
        return { ok: false, reason: 'error', error: entitledError }
      }
    }
    return { ok: false, reason: 'error', error }
  }
}
