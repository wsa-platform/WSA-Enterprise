import { createElement } from 'react'
import { renderToStaticMarkup } from 'react-dom/server'
import { describe, expect, it } from 'vitest'
import { ResearchSourceLink, researchSourceHref } from './ResearchSourceLink'
import { isClickableAnchorMarkup } from './researchViewer'
import { researchViewerPath } from './researchViewer'

describe('research source title destination', () => {
  it('A: citation title routes to the internal Viewer, not the external URL', () => {
    const citation = {
      citation_id: 'cite-1',
      title: 'Effect of growth temperature on chloroplast structure and activity in barley',
      url: 'https://www.semanticscholar.org/paper/abc123',
    }
    expect(researchSourceHref(citation)).toBe(researchViewerPath('cite-1'))
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, {
        citation,
        label: citation.title,
      }),
    )
    expect(isClickableAnchorMarkup(html, researchViewerPath('cite-1'))).toBe(true)
    expect(html).toContain(citation.title)
    expect(html).not.toContain('https://www.semanticscholar.org/paper/abc123')
    expect(html).not.toContain('target="_blank"')
  })

  it('B: DOI-only citations still open the internal Viewer, not doi.org', () => {
    const citation = { citation_id: 'doi-1', title: 'DOI Paper', doi: '10.1234/abcd.efg' }
    expect(researchSourceHref(citation)).toBe(researchViewerPath('doi-1'))
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, { citation, label: 'DOI Paper' }),
    )
    expect(isClickableAnchorMarkup(html, researchViewerPath('doi-1'))).toBe(true)
    expect(html).not.toContain('https://doi.org/')
    expect(html).not.toContain('target="_blank"')
  })

  it('C: Library file paths are not a citation destination', () => {
    const citation = {
      citation_id: 'file-1',
      title: 'Preserved paper',
      url: null,
      doi: null,
    }
    expect(researchSourceHref(citation)).toBe(researchViewerPath('file-1'))
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, { citation, label: 'Preserved paper' }),
    )
    expect(isClickableAnchorMarkup(html, researchViewerPath('file-1'))).toBe(true)
    expect(html).not.toContain('/api/v1/library/files/')
  })

  it('D: title-only citations open the internal Viewer, not an external URL', () => {
    const citation = { citation_id: 'title-1', title: 'Title Only' }
    expect(researchSourceHref(citation)).toBe(researchViewerPath('title-1'))
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, { citation, label: 'Title Only' }),
    )
    expect(isClickableAnchorMarkup(html, researchViewerPath('title-1'))).toBe(true)
    expect(html).not.toContain('target="_blank"')
  })

  it('G: untrusted URLs are not used as the citation href', () => {
    const html = renderToStaticMarkup(
      createElement(ResearchSourceLink, {
        citation: { citation_id: 'unsafe-1', url: 'javascript:alert(1)', title: 'x' },
        label: 'x',
      }),
    )
    expect(html).toContain(researchViewerPath('unsafe-1'))
    expect(html).not.toContain('javascript:')
    expect(researchSourceHref({
      citation_id: 'google-1',
      url: 'https://www.google.com/search?q=wheat',
      title: 'x',
    })).toBe(researchViewerPath('google-1'))
    expect(researchSourceHref({
      citation_id: 'ep-1',
      title: 'x',
    }, { episodeId: 'episode-a' })).toBe(`${researchViewerPath('ep-1')}?episode=episode-a`)
  })
})
