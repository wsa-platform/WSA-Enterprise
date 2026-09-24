import type { ResearchAgentCitation, ResearchAnswerCandidate } from '../api/researchAgent'
import { activateResearchResult, researchResultId, researchViewerPath } from './researchViewer'
import { viewerPathForResult } from './scientificSearchEpisode'

type ResearchSourceLinkProps = {
  citation: ResearchAgentCitation
  label: string
  answer?: string | null
  index?: number
  alternatives?: ResearchAnswerCandidate[]
  episodeId?: string | null
  resultId?: string
}

/** Internal WSA Research Viewer path. Never an external publisher/DOI URL. */
export function researchSourceHref(
  citation: ResearchAgentCitation,
  options?: { resultId?: string; episodeId?: string | null; index?: number },
): string {
  const id = options?.resultId || researchResultId(citation, options?.index ?? 0)
  return options?.episodeId
    ? viewerPathForResult(id, options.episodeId)
    : researchViewerPath(id)
}

/** Research title opens the internal WSA viewer. External URL is Viewer-only. */
export function ResearchSourceLink({
  citation,
  label,
  answer = null,
  index = 0,
  alternatives = [],
  episodeId = null,
  resultId,
}: ResearchSourceLinkProps) {
  const href = researchSourceHref(citation, { resultId, episodeId, index })

  return (
    <a
      href={href}
      data-testid="research-source-viewer-link"
      onClick={() => {
        activateResearchResult(citation, answer, index, alternatives)
      }}
    >
      {label}
    </a>
  )
}
