import type { ResearchAgentCitation, ResearchAnswerCandidate } from '../api/researchAgent'
import { activateResearchResult, researchViewerPath, researchViewerRecordFromCitation } from './researchViewer'

type ResearchSourceLinkProps = {
  citation: ResearchAgentCitation
  label: string
  answer?: string | null
  index?: number
  alternatives?: ResearchAnswerCandidate[]
}

/** Search-result title opens the internal WSA Research Viewer. Never Google. */
export function ResearchSourceLink({
  citation,
  label,
  answer = null,
  index = 0,
  alternatives = [],
}: ResearchSourceLinkProps) {
  const record = researchViewerRecordFromCitation(citation, answer, index, alternatives)

  return (
    <a
      href={researchViewerPath(record.resultId)}
      data-testid="research-source-viewer-link"
      onClick={() => {
        activateResearchResult(citation, answer, index, alternatives)
      }}
    >
      {label}
    </a>
  )
}
