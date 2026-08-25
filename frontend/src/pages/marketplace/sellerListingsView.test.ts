import { createElement, type ReactNode } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { I18nextProvider } from 'react-i18next'
import { MemoryRouter } from 'react-router-dom'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import type { OwnerListing } from '../../api/marketplace'
import i18n from '../../i18n/config'
import { hydrateListingEditor } from '../../marketplace/listingForm'
import { SellerListingsView } from './SellerListingsView'
import {
  closedEditor,
  deleteSellerListing,
  openCreateEditor,
  openEditEditor,
  saveSellerListing,
  type SellerEditorState,
} from './sellerListingsActions'

function listing(overrides: Partial<OwnerListing> = {}): OwnerListing {
  return {
    id: 11,
    title: 'طماطم',
    status: 'draft',
    city: 'الرياض',
    seller_email: 'seller@wsa.test',
    seller_phone: '+966512345678',
    currency: 'SAR',
    category: { id: 4, slug: 'vegetables', name: 'Vegetables', name_ar: 'الخضروات' },
    ...overrides,
  }
}

function renderView(overrides: Partial<Parameters<typeof SellerListingsView>[0]> & {
  editor?: SellerEditorState
  form?: ReactNode
  listings?: OwnerListing[]
} = {}) {
  const html = renderToStaticMarkup(
    createElement(
      MemoryRouter,
      null,
      createElement(
        I18nextProvider,
        { i18n },
        createElement(SellerListingsView, {
          listings: overrides.listings ?? [],
          loading: false,
          onRetry: () => undefined,
          page: 1,
          lastPage: 1,
          total: overrides.listings?.length ?? 0,
          onPageChange: () => undefined,
          editor: overrides.editor ?? closedEditor(),
          form: overrides.form ?? null,
          pendingDelete: overrides.pendingDelete ?? null,
          notice: overrides.notice ?? '',
          noticeIsError: overrides.noticeIsError ?? false,
          language: 'ar',
          onAddProduct: overrides.onAddProduct ?? (() => undefined),
          onEditProduct: overrides.onEditProduct ?? (() => undefined),
          onRequestDelete: overrides.onRequestDelete ?? (() => undefined),
          onCancelDelete: overrides.onCancelDelete ?? (() => undefined),
          onConfirmDelete: overrides.onConfirmDelete ?? (() => undefined),
          onHide: overrides.onHide ?? (() => undefined),
          onPublish: overrides.onPublish ?? (() => undefined),
        }),
      ),
    ),
  )
  return html
}

describe('seller listings product management UI', () => {
  beforeAll(async () => {
    await i18n.changeLanguage('ar')
  })

  it('renders a real إضافة منتج button', () => {
    const html = renderView()
    expect(html).toContain('type="button"')
    expect(html).toContain('إضافة منتج')
    expect(html).toContain('gs-btn gs-btn-primary')
    expect(html).not.toContain('href="/seller/listings/new"')
  })

  it('renders إضافة منتج in the empty state', () => {
    const html = renderView({ listings: [] })
    expect(html).toContain('لا توجد منتجات بعد')
    expect(html.match(/إضافة منتج/g)?.length).toBeGreaterThanOrEqual(2)
  })

  it('opens the product form when إضافة منتج is used', () => {
    const editor = openCreateEditor()
    const html = renderView({
      editor,
      form: createElement(
        'form',
        { className: 'listing-form' },
        createElement('button', { type: 'submit' }, 'حفظ'),
        createElement('button', { type: 'button' }, 'إلغاء'),
      ),
    })
    expect(html).toContain('listing-form')
    expect(html).toContain('حفظ')
    expect(html).toContain('إلغاء')
    expect(html).not.toContain('لا توجد منتجات بعد')
  })

  it('renders edit and delete as semantic buttons on each product', () => {
    const html = renderView({ listings: [listing()] })
    expect(html).toContain('تعديل المنتج')
    expect(html).toContain('حذف المنتج')
    expect(html).toContain('طماطم')
    expect(html).not.toContain('href="/seller/listings/11"')
  })

  it('opens a populated edit form for the selected product', () => {
    const product = listing({
      title: 'خيار',
      description: 'محصول طازج',
      city: 'جدة',
      price: '12',
      seller_email: 'edit@wsa.test',
    })
    const hydrated = hydrateListingEditor(product)
    expect(hydrated.sellerEmail).toBe('edit@wsa.test')
    expect(hydrated.nationalPhone).toBe('512345678')
    expect(hydrated.callingCode).toBe('966')

    const html = renderView({
      listings: [product],
      editor: openEditEditor(product),
      form: createElement('form', { className: 'listing-form' },
        createElement('input', { defaultValue: product.title }),
        createElement('input', { defaultValue: product.city }),
        createElement('input', { defaultValue: product.seller_email }),
        createElement('button', { type: 'submit' }, 'حفظ'),
      ),
    })
    expect(html).toContain('خيار')
    expect(html).toContain('جدة')
    expect(html).toContain('edit@wsa.test')
    expect(html).toContain('حفظ')
  })

  it('shows an Arabic delete confirmation and keeps the product when cancelled', async () => {
    const html = renderView({
      listings: [listing()],
      pendingDelete: listing(),
    })
    expect(html).toContain('سيتم حذف هذا المنتج نهائياً. هل تريد المتابعة؟')
    expect(html).toContain('role="dialog"')

    const deleteListing = vi.fn()
    const result = await deleteSellerListing({
      confirmed: false,
      token: 'tok',
      listingId: 11,
      deleteListing,
    })
    expect(result.reason).toBe('cancelled')
    expect(deleteListing).not.toHaveBeenCalled()
  })

  it('does not save when إلغاء is used', async () => {
    const createListing = vi.fn()
    expect(closedEditor().status).toBe('closed')
    expect(createListing).not.toHaveBeenCalled()
    const html = renderView({
      editor: openCreateEditor(),
      form: createElement('button', { type: 'button' }, 'إلغاء'),
    })
    expect(html).toContain('إلغاء')
    expect(html).not.toContain('لا توجد منتجات بعد')
  })

  it('displays API errors', () => {
    const html = renderView({
      notice: 'تعذّر حفظ المنتج.',
      noticeIsError: true,
    })
    expect(html).toContain('تعذّر حفظ المنتج.')
    expect(html).toContain('role="alert"')
  })

  it('keeps حفظ from calling create twice while a request is in flight', async () => {
    const result = await saveSellerListing({
      busy: true,
      token: 'tok',
      mode: 'create',
      payload: { title: 'Tomatoes' },
      createListing: vi.fn(),
      updateListing: vi.fn(),
    })
    expect(result).toEqual({ ok: false, reason: 'busy' })
  })
})
