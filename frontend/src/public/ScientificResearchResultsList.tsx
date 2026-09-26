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
          const resultId = result.result_id?.trim() ?? ''
          const citation: ResearchAgentCitation = {
            citation_id: resultId,
            source_id: resultId,
            title: result.title,
            url: result.original_url,
            doi: result.doi,
            authors: result.authors,
            organization: result.organization,
            journal: result.journal,
            publication_year: result.publication_year,
            abstract: result.abstract,
            pdf_url: result.pdf_url,
          }
          const meta = resultMeta(result)
          return (
            <li key={resultId || `result-${index}`} data-testid="scientific-research-result">
              <span data-testid="scientific-research-result-title">{result.title}</span>
              {meta ? <span className="hp-research-cite-meta"> — {meta}</span> : null}
              {resultId !== '' ? (
                <span className="hp-research-view-result">
                  {' '}
                  <ResearchSourceLink
                    citation={citation}
                    label={t('website.research.viewResult')}
                    index={index}
                    episodeId={episodeId}
                    resultId={resultId}
                  />
                </span>
              ) : null}
            </li>
          )
        })}
      </ul>
    </div>
  )
}
