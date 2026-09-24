import type { ResearchAgentCitation, ResearchAnswerCandidate } from '../api/researchAgent'
import { authenticatedLibraryFileContentPath } from '../api/libraryCropFiles'
import { citationHref } from './citationHref'
import { isExternalSearchRedirect } from './researchViewer'

type ResearchSourceLinkProps = {
  citation: ResearchAgentCitation
  label: string
  answer?: string | null
  index?: number
  alternatives?: ResearchAnswerCandidate[]
  episodeId?: string | null
  resultId?: string
}

function isUnsafeOrInternalViewerHref(url: string): boolean {
  if (/^(javascript|data|vbscript):/i.test(url.trim())) {
    return true
  }

  try {
    const parsed = new URL(url)
    const host = parsed.hostname.toLowerCase()
    if (host === 'bing.com' || host.endsWith('.bing.com')) {
      return true
    }
    if (parsed.pathname.startsWith('/research/result')) {
      return true
    }
    return false
  } catch {
    return true
  }
}

/** Trusted original URL, DOI, or existing Library file preview. Never the metadata Viewer. */
export function researchSourceHref(citation: ResearchAgentCitation): string | null {
  const fromEvidence = citationHref({ url: citation.url, doi: citation.doi })
  if (
    fromEvidence
    && !isExternalSearchRedirect(fromEvidence)
    && !isUnsafeOrInternalViewerHref(fromEvidence)
  ) {
    return fromEvidence
  }

  const fileId = citation.library_file_id
  if (typeof fileId === 'number' && Number.isInteger(fileId) && fileId > 0) {
    return authenticatedLibraryFileContentPath(fileId)
  }

  return null
}

/** Research title opens the actual source URL or preserved Library file. Never a title-only Viewer. */
export function ResearchSourceLink({
  citation,
  label,
}: ResearchSourceLinkProps) {
  const href = researchSourceHref(citation)

  if (!href) {
    return <span data-testid="research-source-unavailable">{label}</span>
  }

  return (
    <a
      href={href}
      target="_blank"
      rel="noreferrer noopener"
      data-testid="research-source-viewer-link"
    >
      {label}
    </a>
  )
}
