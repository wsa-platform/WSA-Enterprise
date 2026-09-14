import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import i18n from '../i18n/config'
import { ApiError } from '../api/client'
import {
  queryPublicResearchAgent,
  type ResearchAgentCitation,
  type ResearchAgentQueryResponse,
} from '../api/researchAgent'

/** Returns trimmed query, or null when empty (skip submit). */
export function normalizeResearchQuery(value: string): string | null {
  const trimmed = value.trim()
  return trimmed.length > 0 ? trimmed : null
}

export function resolveResearchSearchError(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.status === 404) {
      return i18n.t('website.research.errorNotFound')
    }
    if (error.status === 0 || error.status >= 500) {
      return i18n.t('website.research.errorUnavailable')
    }
    return error.message || i18n.t('website.research.errorGeneric')
  }

  return i18n.t('website.research.errorUnavailable')
}

export type HomeScientificResearchSearchViewProps = {
  query: string
  loading: boolean
  error: string | null
  result: ResearchAgentQueryResponse | null
  onQueryChange: (value: string) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

function citationLabel(citation: ResearchAgentCitation, index: number, fallback: string): string {
  return citation.title?.trim() || fallback
}

/** Presentational scientific research search block for the homepage. */
export function HomeScientificResearchSearchView({
  query,
  loading,
  error,
  result,
  onQueryChange,
  onSubmit,
}: HomeScientificResearchSearchViewProps) {
  const { t } = useTranslation()
  const answer = result?.answer?.trim() || result?.concise_summary?.trim() || null
  const citations = result?.citations ?? []

  return (
    <section
      className="hp-research-section"
      aria-labelledby="home-research-title"
    >
      <div className="hp-research-heading">
        <h2 id="home-research-title">{t('website.research.title')}</h2>
        <span className="hp-category-rule" aria-hidden="true" />
      </div>
      <p className="hp-research-support">
        {t('website.research.support')}
      </p>

      <form className="hp-research-form" onSubmit={onSubmit} noValidate>
        <label className="hp-research-label" htmlFor="home-research-query">
          {t('website.research.queryLabel')}
        </label>
        <div className="hp-research-controls">
          <input
            id="home-research-query"
            className="hp-research-input"
            type="search"
            name="research_query"
            value={query}
            onChange={(event) => onQueryChange(event.target.value)}
            placeholder={t('website.research.queryPlaceholder')}
            disabled={loading}
            autoComplete="off"
          />
          <button
            type="submit"
            className="gs-btn gs-btn-primary hp-research-submit"
            disabled={loading}
          >
            {t('website.research.submit')}
          </button>
        </div>
      </form>

      {loading ? (
        <p className="hp-research-status" aria-live="polite">
          {t('website.research.loading')}
        </p>
      ) : null}

      {error ? (
        <p className="hp-research-status hp-research-status--error" role="alert">
          {error}
        </p>
      ) : null}

      {!loading && !error && result ? (
        <div className="hp-research-result" aria-live="polite">
          {answer ? (
            <div className="hp-research-answer">
              <h3>{t('website.research.answerHeading')}</h3>
              {answer.split('\n').map((paragraph, index) => (
                <p key={`answer-${index}`}>{paragraph}</p>
              ))}
            </div>
          ) : (
            <p className="hp-research-status" role="status">
              {t('website.research.noAnswer')}
            </p>
          )}

          {result.status ? (
            <p className="hp-research-meta">{t('website.research.status', { status: result.status })}</p>
          ) : null}

          <div className="hp-research-citations">
            <h3>{t('website.research.sourcesHeading')}</h3>
            {citations.length === 0 ? (
              <p className="hp-research-status">{t('website.research.noCitations')}</p>
            ) : (
              <ul>
                {citations.map((citation, index) => (
                  <li key={`${citation.doi ?? citation.url ?? citation.title ?? 'c'}-${index}`}>
                    <strong>{citationLabel(citation, index, t('website.research.citationFallback', { index: index + 1 }))}</strong>
                    {citation.organization ? (
                      <span className="hp-research-cite-meta"> — {citation.organization}</span>
                    ) : null}
                    {citation.doi ? (
                      <span className="hp-research-cite-meta"> · DOI: {citation.doi}</span>
                    ) : null}
                    {citation.url ? (
                      <>
                        {' '}
                        <a href={citation.url} target="_blank" rel="noreferrer noopener">
                          {citation.url}
                        </a>
                      </>
                    ) : null}
                    {citation.source_type ? (
                      <span className="hp-research-cite-meta"> · {citation.source_type}</span>
                    ) : null}
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      ) : null}
    </section>
  )
}

/** Homepage scientific research search — calls Laravel `/public/research-agent/query` only. */
export function HomeScientificResearchSearch() {
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [result, setResult] = useState<ResearchAgentQueryResponse | null>(null)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const normalized = normalizeResearchQuery(query)
    if (!normalized || loading) return

    setLoading(true)
    setError(null)

    try {
      const response = await queryPublicResearchAgent(normalized)
      setResult(response)
    } catch (submitError: unknown) {
      setError(resolveResearchSearchError(submitError))
      setResult(null)
    } finally {
      setLoading(false)
    }
  }

  return (
    <HomeScientificResearchSearchView
      query={query}
      loading={loading}
      error={error}
      result={result}
      onQueryChange={setQuery}
      onSubmit={(event) => {
        void handleSubmit(event)
      }}
    />
  )
}
