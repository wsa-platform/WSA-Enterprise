import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  createListing,
  fetchMyListings,
  fetchPublicCategories,
  fetchPublicListing,
  fetchPublicListings,
  payContactAccess,
  requestContactAccess,
} from './marketplace'
import { fetchPublicListings as fetchPublicListingsFromBarrel } from './index'

describe('marketplace API', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('is re-exported from the API barrel', () => {
    expect(fetchPublicListingsFromBarrel).toBe(fetchPublicListings)
  })

  it('loads public listings without a token', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify({
          data: [{ id: 7, title: 'Tomatoes' }],
          current_page: 1,
          last_page: 1,
          total: 1,
        }),
        { status: 200, headers: { 'Content-Type': 'application/json' } },
      ),
    )

    const result = await fetchPublicListings({ search: 'tomato' })

    expect(result.data[0]?.title).toBe('Tomatoes')
    expect(result.total).toBe(1)
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/public/market/listings')
    expect(String(url)).toContain('search=tomato')
    expect(init?.headers).not.toHaveProperty('Authorization')
  })

  it('loads public listings filtered by category', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ data: [], current_page: 1, last_page: 1, total: 0 }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    await fetchPublicListings({ category_id: 3 })

    expect(String(fetchMock.mock.calls[0][0])).toContain('/public/market/listings')
    expect(String(fetchMock.mock.calls[0][0])).toContain('category_id=3')
  })

  it('loads public marketplace categories without a token', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ data: [{ id: 3, name: 'Produce', name_ar: 'منتجات' }] }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await fetchPublicCategories()

    expect(result.data[0]?.name).toBe('Produce')
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/public/market/categories')
    expect(init?.headers).not.toHaveProperty('Authorization')
  })

  it('uses the public listing path when unauthenticated', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ id: 7, title: 'Tomatoes' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    await fetchPublicListing(7)

    expect(String(fetchMock.mock.calls[0][0])).toContain('/public/market/listings/7')
  })

  it('uses the authenticated listing path when a token is present', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ id: 7, title: 'Tomatoes', contact_access_required: true }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    await fetchPublicListing(7, 'token-1')

    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/listings/7')
    expect(String(url)).not.toContain('/public/market/listings/7')
    expect((init?.headers as Record<string, string>).Authorization).toBe('Bearer token-1')
  })

  it('requests and pays for contact access', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch')
      .mockResolvedValueOnce(
        new Response(JSON.stringify({ id: 22, payment_status: 'pending' }), {
          status: 201,
          headers: { 'Content-Type': 'application/json' },
        }),
      )
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            order: { payment_status: 'paid' },
            contact: { seller_email: 'seller@wsa.test' },
          }),
          { status: 200, headers: { 'Content-Type': 'application/json' } },
        ),
      )

    const order = await requestContactAccess(7, 'token-1', 'order-key')
    const paid = await payContactAccess(order.id, 'token-1', 'pay-key')

    expect(order.id).toBe(22)
    expect(paid.contact?.seller_email).toBe('seller@wsa.test')
    expect(String(fetchMock.mock.calls[0][0])).toContain('/market/listings/7/request-contact-access')
    expect(String(fetchMock.mock.calls[1][0])).toContain('/market/contact-access-orders/22/pay')
    expect(JSON.parse(String(fetchMock.mock.calls[0][1]?.body))).toEqual({ idempotency_key: 'order-key' })
  })

  it('loads seller listings with the owner token', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(
        JSON.stringify({
          data: [{ id: 9, title: 'Honey', status: 'draft' }],
          current_page: 1,
          last_page: 1,
          total: 1,
        }),
        { status: 200, headers: { 'Content-Type': 'application/json' } },
      ),
    )

    const result = await fetchMyListings('token-1', 4, 2)

    expect(result.data[0]?.status).toBe('draft')
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/my-listings')
    expect(String(url)).toContain('page=2')
    expect((init?.headers as Record<string, string>).Authorization).toBe('Bearer token-1')
    expect((init?.headers as Record<string, string>)['X-Organization-Id']).toBe('4')
  })

  it('creates a seller listing', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ id: 11, title: 'Olives', status: 'draft' }), {
        status: 201,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const created = await createListing('token-1', { title: 'Olives', seller_type: 'local' }, 4)

    expect(created.id).toBe(11)
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/listings')
    expect(init?.method).toBe('POST')
    expect(JSON.parse(String(init?.body))).toMatchObject({ title: 'Olives', seller_type: 'local' })
  })
})
