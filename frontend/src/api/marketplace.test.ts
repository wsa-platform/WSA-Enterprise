import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  createListing,
  deleteListing,
  fetchMyListing,
  fetchMyListings,
  fetchPublicCategories,
  fetchPublicUnits,
  fetchPublicListing,
  fetchPublicListings,
  payContactAccess,
  requestContactAccess,
  fetchSellerContact,
  unpublishListing,
  updateListing,
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

  it('loads public listings with supported seller and country filters', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ data: [], current_page: 1, last_page: 1, total: 0 }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    await fetchPublicListings({ country: 'SA', seller_type: 'local', per_page: 12, page: 2 })

    const url = String(fetchMock.mock.calls[0][0])
    expect(url).toContain('/public/market/listings')
    expect(url).toContain('country=SA')
    expect(url).toContain('seller_type=local')
    expect(url).toContain('per_page=12')
    expect(url).toContain('page=2')
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

  it('loads public marketplace units without a token', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ data: [{ id: 2, slug: 'kg', name: 'Kilogram', name_ar: 'كجم' }] }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await fetchPublicUnits()

    expect(result.data[0]?.slug).toBe('kg')
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/public/market/units')
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

  it('loads paid seller contact from the private order endpoint', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ seller_email: 'seller@wsa.test', seller_phone: '+966512345678' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const contact = await fetchSellerContact(22, 'token-1')

    expect(contact.seller_phone).toBe('+966512345678')
    expect(String(fetchMock.mock.calls[0][0])).toContain('/market/contact-access-orders/22/seller-contact')
    expect(String(fetchMock.mock.calls[0][0])).not.toContain('/public/')
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

    const created = await createListing('token-1', { title: 'Olives', seller_type: 'local', category_id: 3 }, 4)

    expect(created.id).toBe(11)
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/listings')
    expect(init?.method).toBe('POST')
    expect(JSON.parse(String(init?.body))).toMatchObject({ title: 'Olives', seller_type: 'local', category_id: 3 })
  })

  it('loads an owned listing by id', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ id: 9, title: 'Honey', status: 'draft' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await fetchMyListing('token-1', 9, 4)

    expect(result.title).toBe('Honey')
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/my-listings/9')
    expect((init?.headers as Record<string, string>).Authorization).toBe('Bearer token-1')
  })

  it('unpublishes an owned listing', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ id: 9, title: 'Honey', status: 'unpublished' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await unpublishListing('token-1', 9, 4)

    expect(result.status).toBe('unpublished')
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/listings/9/unpublish')
    expect(init?.method).toBe('POST')
  })

  it('creates a new listing for every save instead of overwriting the previous product', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch')
      .mockResolvedValueOnce(
        new Response(JSON.stringify({ id: 11, title: 'Olives', status: 'draft' }), {
          status: 201,
          headers: { 'Content-Type': 'application/json' },
        }),
      )
      .mockResolvedValueOnce(
        new Response(JSON.stringify({ id: 12, title: 'Honey', status: 'draft' }), {
          status: 201,
          headers: { 'Content-Type': 'application/json' },
        }),
      )

    const first = await createListing('token-1', { title: 'Olives', seller_type: 'local' }, 4)
    const second = await createListing('token-1', { title: 'Honey', seller_type: 'local' }, 4)

    expect(first.id).toBe(11)
    expect(second.id).toBe(12)
    expect(fetchMock.mock.calls).toHaveLength(2)
    expect(String(fetchMock.mock.calls[0][0])).toContain('/market/listings')
    expect(String(fetchMock.mock.calls[1][0])).toContain('/market/listings')
    expect(String(fetchMock.mock.calls[0][0])).not.toMatch(/\/market\/listings\/\d+$/)
    expect(String(fetchMock.mock.calls[1][0])).not.toMatch(/\/market\/listings\/\d+$/)
    expect(fetchMock.mock.calls[0][1]?.method).toBe('POST')
    expect(fetchMock.mock.calls[1][1]?.method).toBe('POST')
  })

  it('updates an owned listing by id without creating a second product', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ id: 11, title: 'Olive oil', status: 'draft' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    const result = await updateListing('token-1', 11, { title: 'Olive oil' }, 4)

    expect(result.id).toBe(11)
    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/listings/11')
    expect(init?.method).toBe('PATCH')
    expect(JSON.parse(String(init?.body))).toMatchObject({ title: 'Olive oil' })
  })

  it('deletes only the requested owned listing', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ message: 'deleted' }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )

    await deleteListing('token-1', 11, 4)

    const [url, init] = fetchMock.mock.calls[0]
    expect(String(url)).toContain('/market/listings/11')
    expect(String(url)).not.toContain('/market/listings/12')
    expect(init?.method).toBe('DELETE')
    expect((init?.headers as Record<string, string>).Authorization).toBe('Bearer token-1')
  })
})
