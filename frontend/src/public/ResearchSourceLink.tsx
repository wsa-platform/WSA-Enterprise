import type { ResearchAgentCitation, ResearchAnswerCandidate } from '../api/researchAgent'
import { activateResearchResult, researchViewerPath, researchViewerRecordFromCitation } from './researchViewer'
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

/** Search-result title opens the internal WSA Research Viewer. Never Google. */
export function ResearchSourceLink({
  citation,
  label,
  answer = null,
  index = 0,
  alternatives = [],
  episodeId = null,
  resultId,
}: ResearchSourceLinkProps) {
  const record = researchViewerRecordFromCitation(citation, answer, index, alternatives)
  const id = resultId || record.resultId
  const href = episodeId ? viewerPathForResult(id, episodeId) : researchViewerPath(id)

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
