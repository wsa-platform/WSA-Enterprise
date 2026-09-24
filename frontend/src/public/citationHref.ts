export type CitationLinkInput = {
  url?: string | null
  doi?: string | null
}

const HTTP_URL = /^https?:\/\//i
const DOI_PREFIX = /^(?:doi:|https?:\/\/doi\.org\/)/i
const DOI_BODY = /^10\.\d{4,9}\/\S+$/i

function normalizeDoi(raw: string): string | null {
  const trimmed = raw.trim()
  if (trimmed === '') {
    return null
  }

  const withoutPrefix = trimmed.replace(DOI_PREFIX, '').trim()
  if (!DOI_BODY.test(withoutPrefix)) {
    return null
  }

  return withoutPrefix
}

/**
 * Trusted http(s) URL first. Real DOI becomes https://doi.org/{doi}.
 * Never fabricates a URL or DOI.
 */
export function citationHref(citation: CitationLinkInput): string | null {
  const url = citation.url?.trim()
  if (url && HTTP_URL.test(url)) {
    return url
  }

  const doi = citation.doi ? normalizeDoi(citation.doi) : null
  if (doi) {
    return `https://doi.org/${doi}`
  }

  return null
}

export type SourcePresentationState =
  | 'has_clickable_sources'
  | 'no_eligible_direct_citations'
  | 'no_url_or_doi'

export function sourcePresentationState(
  citations: CitationLinkInput[] | undefined,
): SourcePresentationState {
  const items = citations ?? []
  if (items.length === 0) {
    return 'no_eligible_direct_citations'
  }
  if (items.some((citation) => citationHref(citation) !== null)) {
    return 'has_clickable_sources'
  }

  return 'no_url_or_doi'
}
