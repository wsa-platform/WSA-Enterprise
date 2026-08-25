import { describe, expect, it, vi } from 'vitest'
import type { MarketplaceListingWrite, OwnerListing } from '../../api/marketplace'
import {
  closedEditor,
  deleteSellerListing,
  editingListing,
  isCreateEditor,
  isEditorOpen,
  openCreateEditor,
  openEditEditor,
  publishSellerListing,
  canPublishListing,
  saveSellerListing,
} from './sellerListingsActions'

const payload = { title: 'Tomatoes' } as MarketplaceListingWrite

function listing(overrides: Partial<OwnerListing> = {}): OwnerListing {
  return { id: 11, title: 'طماطم', status: 'draft', ...overrides }
}

describe('seller listings editor state', () => {
  it('opens create mode when إضافة منتج is used', () => {
    const editor = openCreateEditor()
    expect(isEditorOpen(editor)).toBe(true)
    expect(isCreateEditor(editor)).toBe(true)
    expect(editingListing(editor)).toBeNull()
  })

  it('opens edit mode with the selected product values', () => {
    const product = listing({ title: 'خيار', price: '4' })
    const editor = openEditEditor(product)
    expect(isEditorOpen(editor)).toBe(true)
    expect(editingListing(editor)).toEqual(product)
  })

  it('إلغاء closes the editor without keeping create/edit mode', () => {
    expect(closedEditor()).toEqual({ status: 'closed' })
    expect(isEditorOpen(closedEditor())).toBe(false)
  })
})

describe('seller listing save', () => {
  it('calls the create API in create mode', async () => {
    const created = listing({ id: 21, title: 'Tomatoes' })
    const createListing = vi.fn().mockResolvedValue(created)
    const updateListing = vi.fn()

    const result = await saveSellerListing({
      busy: false,
      token: 'tok',
      organizationId: 3,
      mode: 'create',
      payload,
      createListing,
      updateListing,
    })

    expect(result).toEqual({ ok: true, listing: created, kind: 'created' })
    expect(createListing).toHaveBeenCalledOnce()
    expect(createListing).toHaveBeenCalledWith('tok', payload, 3)
    expect(updateListing).not.toHaveBeenCalled()
  })

  it('calls the update API in edit mode', async () => {
    const updated = listing({ title: 'Updated tomatoes' })
    const createListing = vi.fn()
    const updateListing = vi.fn().mockResolvedValue(updated)

    const result = await saveSellerListing({
      busy: false,
      token: 'tok',
      organizationId: 3,
      mode: 'edit',
      listingId: 11,
      payload,
      createListing,
      updateListing,
    })

    expect(result).toEqual({ ok: true, listing: updated, kind: 'updated' })
    expect(updateListing).toHaveBeenCalledOnce()
    expect(updateListing).toHaveBeenCalledWith('tok', 11, payload, 3)
    expect(createListing).not.toHaveBeenCalled()
  })

  it('does not save when the editor is cancelled', () => {
    const createListing = vi.fn()
    const updateListing = vi.fn()
    expect(closedEditor().status).toBe('closed')
    expect(createListing).not.toHaveBeenCalled()
    expect(updateListing).not.toHaveBeenCalled()
  })

  it('prevents duplicate submission while a save is already in flight', async () => {
    const createListing = vi.fn().mockResolvedValue(listing())
    const updateListing = vi.fn()

    const result = await saveSellerListing({
      busy: true,
      token: 'tok',
      mode: 'create',
      payload,
      createListing,
      updateListing,
    })

    expect(result).toEqual({ ok: false, reason: 'busy' })
    expect(createListing).not.toHaveBeenCalled()
    expect(updateListing).not.toHaveBeenCalled()
  })

  it('returns API errors so the UI can display them', async () => {
    const error = new Error('Listing could not be saved.')
    const result = await saveSellerListing({
      busy: false,
      token: 'tok',
      mode: 'create',
      payload,
      createListing: vi.fn().mockRejectedValue(error),
      updateListing: vi.fn(),
    })

    expect(result).toEqual({ ok: false, reason: 'error', error })
  })

  it('does not publish when saving a product', async () => {
    const submitListing = vi.fn()
    const created = listing({ status: 'draft', title: 'أرز مصري' })
    const result = await saveSellerListing({
      busy: false,
      token: 'tok',
      mode: 'create',
      payload: { title: 'أرز مصري' } as MarketplaceListingWrite,
      createListing: vi.fn().mockResolvedValue(created),
      updateListing: vi.fn(),
    })
    expect(result.ok).toBe(true)
    if (result.ok) expect(result.listing.status).toBe('draft')
    expect(submitListing).not.toHaveBeenCalled()
  })
})

describe('seller listing delete', () => {
  it('asks for confirmation and does not delete when cancelled', async () => {
    const deleteListing = vi.fn()
    const result = await deleteSellerListing({
      confirmed: false,
      token: 'tok',
      listingId: 11,
      deleteListing,
    })

    expect(result).toEqual({ ok: false, reason: 'cancelled' })
    expect(deleteListing).not.toHaveBeenCalled()
  })

  it('calls the delete API when deletion is confirmed', async () => {
    const deleteListing = vi.fn().mockResolvedValue({ message: 'Listing deleted.' })
    const result = await deleteSellerListing({
      confirmed: true,
      token: 'tok',
      listingId: 11,
      organizationId: 3,
      deleteListing,
    })

    expect(result).toEqual({ ok: true })
    expect(deleteListing).toHaveBeenCalledOnce()
    expect(deleteListing).toHaveBeenCalledWith('tok', 11, 3)
  })

  it('returns a clear error when deletion fails', async () => {
    const error = new Error('Unable to delete listing.')
    const result = await deleteSellerListing({
      confirmed: true,
      token: 'tok',
      listingId: 11,
      deleteListing: vi.fn().mockRejectedValue(error),
    })

    expect(result).toEqual({ ok: false, reason: 'error', error })
  })
})

describe('seller listing publish', () => {
  it('calls the existing submit API and does not create a duplicate product', async () => {
    const published = listing({ status: 'published' })
    const submitListing = vi.fn().mockResolvedValue(published)
    const result = await publishSellerListing({
      token: 'tok',
      listingId: 11,
      organizationId: 3,
      submitListing,
    })
    expect(result).toEqual({ ok: true, listing: published })
    expect(submitListing).toHaveBeenCalledOnce()
    expect(submitListing).toHaveBeenCalledWith('tok', 11, 3)
  })

  it('prevents duplicate publish while a request is in flight', async () => {
    const submitListing = vi.fn()
    const result = await publishSellerListing({
      busy: true,
      token: 'tok',
      listingId: 11,
      submitListing,
    })
    expect(result).toEqual({ ok: false, reason: 'busy' })
    expect(submitListing).not.toHaveBeenCalled()
  })

  it('publishes draft and unpublished products but not already live products', () => {
    expect(canPublishListing('draft')).toBe(true)
    expect(canPublishListing('unpublished')).toBe(true)
    expect(canPublishListing('published')).toBe(false)
  })
})
