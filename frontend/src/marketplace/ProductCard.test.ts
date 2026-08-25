import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { MemoryRouter } from 'react-router-dom'
import { describe, expect, it } from 'vitest'
import { ProductCard } from './ProductCard'
import { publicListingsOnly } from './contactUnlock'
import { productNameFromListing } from './units'

describe('public marketplace product cards', () => {
  it('renders only the seller-entered product name and links to public details', () => {
    const html = renderToStaticMarkup(
      createElement(
        MemoryRouter,
        null,
        createElement(ProductCard, { listing: { id: 7, title: 'أرز مصري', brand: 'should-not-show', price: 12 } }),
      ),
    )
    expect(html).toContain('أرز مصري')
    expect(html).toContain('href="/market/7"')
    expect(html).not.toContain('should-not-show')
    expect(html).not.toContain('12')
    expect(productNameFromListing({ title: 'نوتيلا إيطالي' })).toBe('نوتيلا إيطالي')
  })

  it('keeps unpublished products out of the public catalog', () => {
    expect(publicListingsOnly([
      { id: 1, title: 'أرز مصري', status: 'published' },
      { id: 2, title: 'مسودة', status: 'draft' },
    ]).map((listing) => listing.title)).toEqual(['أرز مصري'])
  })
})
