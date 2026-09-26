import { useTranslation } from 'react-i18next'
import type { ResearchAgentCitation } from '../api/researchAgent'
import { ResearchSourceLink } from './ResearchSourceLink'
import type { PresentedResearchResult } from './scientificUserPresentation'

export type ScientificResearchResultsListProps = {
  results: PresentedResearchResult[]
  episodeId?: string | null
  testId?: string
}

function resultMeta(result: PresentedResearchResult): string {
  const parts = [
    result.organization?.trim(),
    result.journal?.trim(),
    result.publication_year != null ? String(result.publication_year) : '',
  ].filter((part) => part !== '')
  return parts.join(' — ')
}

/** Eligible scientific research results. Identity is result_id, never title/DOI/URL. */
export function ScientificResearchResultsList({
  results,
  episodeId = null,
  testId = 'scientific-research-results',
}: ScientificResearchResultsListProps) {
  const { t } = useTranslation()
  if (results.length === 0) {
    return null
  }

  return (
    <div className="hp-research-citations hp-research-results" data-testid={testId}>
      <h3>{t('website.research.resultsHeading')}</h3>
      <ul>
        {results.map((result, index) => {
          const citation: ResearchAgentCitation = {
            citation_id: result.result_id,
            source_id: result.result_id,
            title: result.title,
            url: result.original_url,
            doi: result.doi,
            authors: result.authors,
            organization: result.organization,
            journal: result.journal,
            publication_year: result.publication_year,
          }
          const meta = resultMeta(result)
          return (
            <li key={result.result_id} data-testid="scientific-research-result">
              <ResearchSourceLink
                citation={citation}
                label={result.title}
                index={index}
                episodeId={episodeId}
                resultId={result.result_id}
              />
              {meta ? <span className="hp-research-cite-meta"> — {meta}</span> : null}
            </li>
          )
        })}
      </ul>
    </div>
  )
}
