import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { authenticatedLibraryFileContentPath } from '../api/libraryCropFiles'
import { ResearchSourceLink, researchSourceHref } from './ResearchSourceLink'
import { isClickableAnchorMarkup } from './researchViewer'
import { researchViewerPath } from './researchViewer'

describe('research source title destination', () => {
  it('A: valid HTTPS source is the title href, not the metadata Viewer', () => {
    const citation = {
      citation_id: 'cite-1',
      title: 'Effect of growth temperature on chloroplast structure and activity in barley',
      url: 'https://www.semanticscholar.org/paper/abc123',
    }
    expect(researchSourceHref(citation)).toBe('https://www.semanticscholar.org/paper/abc123')
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, {
        citation,
        label: citation.title,
      }),
    )
    expect(isClickableAnchorMarkup(html, 'https://www.semanticscholar.org/paper/abc123')).toBe(true)
    expect(html).toContain(citation.title)
    expect(html).not.toContain(researchViewerPath('cite-1'))
    expect(html).not.toContain('/research/result/')
  })

  it('B: DOI-only uses the existing doi.org convention', () => {
    const citation = { citation_id: 'doi-1', title: 'DOI Paper', doi: '10.1234/abcd.efg' }
    expect(researchSourceHref(citation)).toBe('https://doi.org/10.1234/abcd.efg')
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, { citation, label: 'DOI Paper' }),
    )
    expect(isClickableAnchorMarkup(html, 'https://doi.org/10.1234/abcd.efg')).toBe(true)
    expect(html).not.toContain('/research/result/')
  })

  it('C: preserved Library file uses the existing Library content path', () => {
    const citation = { citation_id: 'file-1', title: 'Preserved paper', library_file_id: 42 }
    expect(researchSourceHref(citation)).toBe(authenticatedLibraryFileContentPath(42))
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, { citation, label: 'Preserved paper' }),
    )
    expect(isClickableAnchorMarkup(html, '/api/v1/library/files/42/content')).toBe(true)
    expect(html).not.toContain('/research/result/')
  })

  it('D: no URL, DOI, or file — title is not a link and does not open the Viewer', () => {
    const citation = { citation_id: 'title-1', title: 'Title Only' }
    expect(researchSourceHref(citation)).toBeNull()
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, { citation, label: 'Title Only' }),
    )
    expect(html).toContain('data-testid="research-source-unavailable"')
    expect(html).toContain('Title Only')
    expect(html).not.toContain('<a')
    expect(html).not.toContain('/research/result/')
  })

  it('G: rejects untrusted and Viewer-shaped destinations', () => {
    expect(researchSourceHref({ url: 'javascript:alert(1)', title: 'x' })).toBeNull()
    expect(researchSourceHref({ url: 'https://www.google.com/search?q=wheat', title: 'x' })).toBeNull()
    expect(researchSourceHref({ url: 'https://www.bing.com/search?q=wheat', title: 'x' })).toBeNull()
    expect(researchSourceHref({ url: 'https://example.org/research/result/cite-1', title: 'x' })).toBeNull()
    expect(researchSourceHref({ doi: 'not-a-doi', title: 'x' })).toBeNull()
    expect(researchSourceHref({ library_file_id: 0, title: 'x' })).toBeNull()
    expect(researchSourceHref({ library_file_id: -3, title: 'x' })).toBeNull()
  })
})
