import { request } from './client'
import type { PaginatedResponse } from './types'

export type PublicListingContact = {
  seller_email?: string | null
  seller_phone?: string | null
  seller_display_name?: string | null
}

export const PRODUCT_AVAILABILITIES = ['available_now', 'seasonal', 'on_demand', 'unavailable'] as const
export type ProductAvailability = (typeof PRODUCT_AVAILABILITIES)[number]
export type ProductSellerType = 'local' | 'international'

export type PublicListingImage = {
  id?: number
  path?: string
  alt_text?: string | null
  sort_order?: number
}

export type PublicListingUnit = {
  id?: number
  slug?: string
  name?: string
  name_ar?: string | null
}

export type PublicListing = {
  id: number
  title: string
  brand?: string | null
  description?: string | null
  product_type?: string | null
  seller_type?: ProductSellerType | string
  availability?: ProductAvailability | string | null
  price?: string | number | null
  currency?: string | null
  country?: string | null
  seller_country?: string | null
  origin_country?: string | null
  city?: string | null
  seller_region?: string | null
  export_ready?: boolean | null
  min_order_quantity?: string | number | null
  available_quantity?: string | number | null
  production_capacity?: string | number | null
  wholesale?: boolean | null
  retail?: boolean | null
  packaging?: string | null
  shipping_terms?: string | null
  lead_time_days?: number | null
  specifications?: Record<string, unknown> | null
  video_url?: string | null
  unit?: PublicListingUnit | null
  images?: PublicListingImage[]
  contact_access_price?: string | number
  contact_access_currency?: string
  contact_access_required?: boolean
  contact?: PublicListingContact
  seller?: {
    display_name?: string
    country?: string
    city?: string
    region?: string
    seller_type?: string
    verified?: boolean
  }
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

export async function fetchPublicListings(params: {
  page?: number
  search?: string
  category_id?: number
  country?: string
  seller_type?: ProductSellerType | string
  per_page?: number
} = {}) {
  const query = new URLSearchParams()
  if (params.page) query.set('page', String(params.page))
  if (params.search) query.set('search', params.search)
  if (params.category_id) query.set('category_id', String(params.category_id))
  if (params.country) query.set('country', params.country)
  if (params.seller_type) query.set('seller_type', params.seller_type)
  if (params.per_page) query.set('per_page', String(params.per_page))
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
  product_type?: string | null
  brand?: string | null
  seller_type?: ProductSellerType
  availability?: ProductAvailability | null
  unit_id?: number | null
  price?: number | null
  currency?: string
  country?: string
  origin_country?: string | null
  city?: string
  seller_region?: string | null
  seller_display_name?: string
  export_ready?: boolean
  min_order_quantity?: number | null
  available_quantity?: number | null
  production_capacity?: number | null
  wholesale?: boolean
  retail?: boolean
  packaging?: string | null
  shipping_terms?: string | null
  lead_time_days?: number | null
  specifications?: Record<string, unknown> | null
  video_url?: string | null
  category_id?: number | null
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
