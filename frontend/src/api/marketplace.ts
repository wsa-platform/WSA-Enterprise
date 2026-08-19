import { request } from './client'
import type { PaginatedResponse } from './types'

export type PublicListingContact = {
  seller_email?: string | null
  seller_phone?: string | null
  seller_display_name?: string | null
}

export type PublicListing = {
  id: number
  title: string
  description?: string
  price?: string | number
  currency?: string
  country?: string
  city?: string
  seller_type?: string
  contact_access_price?: string | number
  contact_access_currency?: string
  contact_access_required?: boolean
  contact?: PublicListingContact
  seller?: { display_name?: string; country?: string; city?: string; seller_type?: string; verified?: boolean }
  category?: { id?: number; slug?: string; name?: string; name_ar?: string } | null
}

export type PublicMarketCategory = {
  id: number
  slug?: string
  name?: string
  name_ar?: string
}

export type ContactAccessOrder = {
  id: number
  payment_status?: string
}

export type PayContactAccessResult = {
  order: { payment_status: string }
  contact?: PublicListingContact | null
}

export async function fetchPublicListings(params: { page?: number; search?: string; category_id?: number } = {}) {
  const query = new URLSearchParams()
  if (params.page) query.set('page', String(params.page))
  if (params.search) query.set('search', params.search)
  if (params.category_id) query.set('category_id', String(params.category_id))
  const suffix = query.toString() ? `?${query}` : ''
  return request<PaginatedResponse<PublicListing>>(`/public/market/listings${suffix}`)
}

export async function fetchPublicCategories() {
  return request<{ data: PublicMarketCategory[] }>('/public/market/categories')
}

export async function fetchPublicListing(id: number, token?: string | null) {
  const path = token ? `/market/listings/${id}` : `/public/market/listings/${id}`
  return request<PublicListing>(path, {}, token ?? undefined)
}

export async function requestContactAccess(listingId: number, token: string, idempotencyKey: string) {
  return request<ContactAccessOrder>(
    `/market/listings/${listingId}/request-contact-access`,
    { method: 'POST', body: JSON.stringify({ idempotency_key: idempotencyKey }) },
    token,
  )
}

export async function payContactAccess(orderId: number, token: string, idempotencyKey: string) {
  return request<PayContactAccessResult>(
    `/market/contact-access-orders/${orderId}/pay`,
    { method: 'POST', body: JSON.stringify({ idempotency_key: idempotencyKey }) },
    token,
  )
}

export type OwnerListing = PublicListing & {
  status?: string
  seller_email?: string | null
  seller_phone?: string | null
  organization_id?: number | null
}

export type MarketplaceListingWrite = {
  title: string
  description?: string
  seller_type?: 'local' | 'international'
  price?: number | null
  currency?: string
  country?: string
  city?: string
  seller_display_name?: string
  seller_email?: string
  seller_phone?: string
  contact_access_price?: number | null
}

export async function fetchMyListings(token: string, organizationId?: number, page = 1, perPage = 15) {
  return request<PaginatedResponse<OwnerListing>>(
    `/market/my-listings?page=${page}&per_page=${perPage}`,
    {},
    token,
    organizationId,
  )
}

export async function fetchMyListing(token: string, listingId: number, organizationId?: number) {
  return request<OwnerListing>(`/market/my-listings/${listingId}`, {}, token, organizationId)
}

export async function createListing(
  token: string,
  payload: MarketplaceListingWrite,
  organizationId?: number,
) {
  return request<OwnerListing>(
    '/market/listings',
    { method: 'POST', body: JSON.stringify(payload) },
    token,
    organizationId,
  )
}

export async function updateListing(
  token: string,
  listingId: number,
  payload: Partial<MarketplaceListingWrite>,
  organizationId?: number,
) {
  return request<OwnerListing>(
    `/market/listings/${listingId}`,
    { method: 'PATCH', body: JSON.stringify(payload) },
    token,
    organizationId,
  )
}

export async function deleteListing(token: string, listingId: number, organizationId?: number) {
  return request<{ message: string }>(
    `/market/listings/${listingId}`,
    { method: 'DELETE' },
    token,
    organizationId,
  )
}

export async function submitListing(token: string, listingId: number, organizationId?: number) {
  return request<OwnerListing>(
    `/market/listings/${listingId}/submit`,
    { method: 'POST' },
    token,
    organizationId,
  )
}

export async function unpublishListing(token: string, listingId: number, organizationId?: number) {
  return request<OwnerListing>(
    `/market/listings/${listingId}/unpublish`,
    { method: 'POST' },
    token,
    organizationId,
  )
}
