import { describe, expect, it } from 'vitest'
import { citationHref, sourcePresentationState } from './citationHref'

describe('citationHref', () => {
  it('uses a trusted http(s) URL without fabrication', () => {
    expect(citationHref({ url: 'https://example.org/paper', doi: '10.1/a' })).toBe(
      'https://example.org/paper',
    )
  })

  it('builds https://doi.org/{doi} only for a real DOI', () => {
    expect(citationHref({ doi: '10.1234/abcd.efg' })).toBe('https://doi.org/10.1234/abcd.efg')
    expect(citationHref({ doi: 'https://doi.org/10.1234/abcd.efg' })).toBe(
      'https://doi.org/10.1234/abcd.efg',
    )
    expect(citationHref({ doi: 'not-a-doi' })).toBeNull()
  })

  it('returns null when neither URL nor DOI exists', () => {
    expect(citationHref({ title: 'Only title' } as { url?: string })).toBeNull()
  })

  it('classifies empty citations as no eligible direct citations', () => {
    expect(sourcePresentationState([])).toBe('no_eligible_direct_citations')
    expect(sourcePresentationState(undefined)).toBe('no_eligible_direct_citations')
  })

  it('classifies title-only citations as non-clickable', () => {
    expect(sourcePresentationState([{ title: 'Only title' } as { url?: string }])).toBe('no_url_or_doi')
  })

  it('rejects javascript and other non-http URLs', () => {
    expect(citationHref({ url: 'javascript:alert(1)' })).toBeNull()
    expect(citationHref({ url: 'ftp://example.org/file' })).toBeNull()
  })
})
