import type { MarketplaceListingWrite, OwnerListing } from '../../api/marketplace'

export type SellerEditorState =
  | { status: 'closed' }
  | { status: 'create' }
  | { status: 'edit'; listing: OwnerListing }

export function closedEditor(): SellerEditorState {
  return { status: 'closed' }
}

export function openCreateEditor(): SellerEditorState {
  return { status: 'create' }
}

export function openEditEditor(listing: OwnerListing): SellerEditorState {
  return { status: 'edit', listing }
}

export function isEditorOpen(state: SellerEditorState): boolean {
  return state.status !== 'closed'
}

export function isCreateEditor(state: SellerEditorState): boolean {
  return state.status === 'create'
}

export function editingListing(state: SellerEditorState): OwnerListing | null {
  return state.status === 'edit' ? state.listing : null
}

export type SaveSellerListingInput = {
  busy: boolean
  token: string
  organizationId?: number
  mode: 'create' | 'edit'
  listingId?: number
  payload: MarketplaceListingWrite
  createListing: (
    token: string,
    payload: MarketplaceListingWrite,
    organizationId?: number,
  ) => Promise<OwnerListing>
  updateListing: (
    token: string,
    listingId: number,
    payload: Partial<MarketplaceListingWrite>,
    organizationId?: number,
  ) => Promise<OwnerListing>
}

export type SaveSellerListingResult =
  | { ok: true; listing: OwnerListing; kind: 'created' | 'updated' }
  | { ok: false; reason: 'busy' | 'invalid_mode' | 'error'; error?: unknown }

export async function saveSellerListing(input: SaveSellerListingInput): Promise<SaveSellerListingResult> {
  if (input.busy) {
    return { ok: false, reason: 'busy' }
  }

  try {
    if (input.mode === 'create') {
      const listing = await input.createListing(input.token, input.payload, input.organizationId)
      return { ok: true, listing, kind: 'created' }
    }
    if (input.mode === 'edit' && input.listingId) {
      const listing = await input.updateListing(
        input.token,
        input.listingId,
        input.payload,
        input.organizationId,
      )
      return { ok: true, listing, kind: 'updated' }
    }
    return { ok: false, reason: 'invalid_mode' }
  } catch (error) {
    return { ok: false, reason: 'error', error }
  }
}

export type DeleteSellerListingResult =
  | { ok: true }
  | { ok: false; reason: 'cancelled' | 'busy' | 'error'; error?: unknown }

export async function deleteSellerListing(input: {
  confirmed: boolean
  busy?: boolean
  token: string
  listingId: number
  organizationId?: number
  deleteListing: (token: string, listingId: number, organizationId?: number) => Promise<unknown>
}): Promise<DeleteSellerListingResult> {
  if (!input.confirmed) {
    return { ok: false, reason: 'cancelled' }
  }
  if (input.busy) {
    return { ok: false, reason: 'busy' }
  }
  try {
    await input.deleteListing(input.token, input.listingId, input.organizationId)
    return { ok: true }
  } catch (error) {
    return { ok: false, reason: 'error', error }
  }
}

export async function publishSellerListing(input: {
  busy?: boolean
  token: string
  listingId: number
  organizationId?: number
  submitListing: (token: string, listingId: number, organizationId?: number) => Promise<OwnerListing>
}): Promise<{ ok: true; listing: OwnerListing } | { ok: false; reason: 'busy' | 'error'; error?: unknown }> {
  if (input.busy) {
    return { ok: false, reason: 'busy' }
  }
  try {
    const listing = await input.submitListing(input.token, input.listingId, input.organizationId)
    return { ok: true, listing }
  } catch (error) {
    return { ok: false, reason: 'error', error }
  }
}

export function canPublishListing(status?: string | null): boolean {
  return status === 'draft' || status === 'rejected' || status === 'unpublished' || status === 'pending_review'
}

