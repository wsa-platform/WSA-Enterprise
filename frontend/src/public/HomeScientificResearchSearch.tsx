import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import i18n, { getCurrentLanguage } from '../i18n/config'
import { ApiError } from '../api/client'
import { translateApiError } from '../i18n/apiErrors'
import {
  buildHomePositiveFeedbackPayload,
  formatResearchConflictText,
  queryPublicResearchAgent,
  submitResearchFeedback,
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
    if (
      error.status === 503 ||
      /public_organization_unavailable/i.test(error.message)
    ) {
      return i18n.t('website.research.errorUnavailable')
    }
    if (error.status === 404) {
      return i18n.t('website.research.errorNotFound')
    }
    if (error.status === 504 || error.status === 408) {
      return i18n.t('website.research.errorTimeout')
    }
    if (error.status === 0 || error.status === 502) {
      return i18n.t('website.research.errorUnavailable')
    }
    if (error.status >= 500) {
      return i18n.t('website.research.errorGeneric')
    }
    return error.message || i18n.t('website.research.errorGeneric')
  }

  return i18n.t('website.research.errorUnavailable')
}

export type HomeFeedbackUiState = 'idle' | 'submitting' | 'success' | 'error'

export type HomeScientificResearchSearchViewProps = {
  query: string
  loading: boolean
  error: string | null
  result: ResearchAgentQueryResponse | null
  onQueryChange: (value: string) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
  feedbackState?: HomeFeedbackUiState
  feedbackErrorMessage?: string | null
  onPositiveFeedback?: () => void
}

function citationLabel(citation: ResearchAgentCitation, _index: number, fallback: string): string {
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
  feedbackState = 'idle',
  feedbackErrorMessage = null,
  onPositiveFeedback,
}: HomeScientificResearchSearchViewProps) {
  const { t } = useTranslation()
  const answer = result?.answer?.trim() || result?.concise_summary?.trim() || null
  const citations = result?.citations ?? []
  const conflicts = result?.conflicts ?? []
  const uncertainty = result?.uncertainty?.trim() || null
  const showFeedback = Boolean(result) && typeof onPositiveFeedback === 'function'
  const feedbackBusy = feedbackState === 'submitting'
  const feedbackDone = feedbackState === 'success'
  const feedbackDisabled = feedbackBusy || feedbackDone || loading

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

          {typeof result.confidence === 'number' ? (
            <p className="hp-research-meta" data-testid="home-research-confidence">
              {t('website.research.confidence', {
                confidence: Math.round(result.confidence * 100) / 100,
                defaultValue: `Confidence: ${Math.round(result.confidence * 100) / 100}`,
              })}
            </p>
          ) : null}

          {Array.isArray(result.limitations) && result.limitations.length > 0 ? (
            <div className="hp-research-limitations" data-testid="home-research-limitations">
              <h3>{t('website.research.limitationsHeading', { defaultValue: 'Limitations' })}</h3>
              <ul>
                {result.limitations.map((limitation, index) => (
                  <li key={`limitation-${index}`}>{limitation}</li>
                ))}
              </ul>
            </div>
          ) : null}

          {uncertainty ? (
            <div className="hp-research-uncertainty" data-testid="home-research-uncertainty">
              <h3>{t('website.research.uncertaintyHeading', { defaultValue: 'Uncertainty' })}</h3>
              <p>{uncertainty}</p>
            </div>
          ) : null}

          {conflicts.length > 0 ? (
            <div className="hp-research-conflicts" data-testid="home-research-conflicts">
              <h3>{t('website.research.conflictsHeading', { defaultValue: 'Conflicts' })}</h3>
              <ul>
                {conflicts.map((conflict, index) => (
                  <li key={`conflict-${index}`}>{formatResearchConflictText(conflict)}</li>
                ))}
              </ul>
            </div>
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

          {showFeedback ? (
            <div className="hp-research-feedback" data-testid="home-research-feedback">
              <button
                type="button"
                className="gs-btn hp-research-feedback-positive"
                data-testid="home-research-feedback-positive"
                disabled={feedbackDisabled}
                onClick={() => onPositiveFeedback?.()}
              >
                {feedbackBusy
                  ? t('website.research.feedbackSubmitting')
                  : t('website.research.feedbackPositive')}
              </button>
              {feedbackState === 'success' ? (
                <p
                  className="hp-research-status"
                  data-testid="home-research-feedback-success"
                  role="status"
                >
                  {t('website.research.feedbackSuccess')}
                </p>
              ) : null}
              {feedbackState === 'error' ? (
                <p
                  className="hp-research-status hp-research-status--error"
                  data-testid="home-research-feedback-error"
                  role="alert"
                >
                  {feedbackErrorMessage || t('website.research.feedbackError')}
                </p>
              ) : null}
            </div>
          ) : null}
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
  const [submittedQuestion, setSubmittedQuestion] = useState<string | null>(null)
  const [feedbackState, setFeedbackState] = useState<HomeFeedbackUiState>('idle')
  const [feedbackErrorMessage, setFeedbackErrorMessage] = useState<string | null>(null)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const normalized = normalizeResearchQuery(query)
    if (!normalized || loading) return

    setLoading(true)
    setError(null)
    setFeedbackState('idle')
    setFeedbackErrorMessage(null)

    try {
      const response = await queryPublicResearchAgent(normalized)
      setResult(response)
      setSubmittedQuestion(normalized)
    } catch (submitError: unknown) {
      setError(resolveResearchSearchError(submitError))
      setResult(null)
      setSubmittedQuestion(null)
    } finally {
      setLoading(false)
    }
  }

  async function handlePositiveFeedback() {
    if (!result || !submittedQuestion) return
    if (feedbackState === 'submitting' || feedbackState === 'success') return

    setFeedbackState('submitting')
    setFeedbackErrorMessage(null)
    try {
      const payload = buildHomePositiveFeedbackPayload({
        question: submittedQuestion,
        uiLocale: getCurrentLanguage(),
        answerLanguage: result.language,
      })
      const response = await submitResearchFeedback(payload)
      if (response.persisted) {
        setFeedbackState('success')
      } else {
        setFeedbackState('error')
        setFeedbackErrorMessage(response.reason || i18n.t('website.research.feedbackError'))
      }
    } catch (submitError: unknown) {
      setFeedbackState('error')
      setFeedbackErrorMessage(translateApiError(submitError) || i18n.t('website.research.feedbackError'))
    }
  }

  return (
    <HomeScientificResearchSearchView
      query={query}
      loading={loading}
      error={error}
      result={result}
      feedbackState={feedbackState}
      feedbackErrorMessage={feedbackErrorMessage}
      onQueryChange={setQuery}
      onSubmit={(event) => {
        void handleSubmit(event)
      }}
      onPositiveFeedback={() => {
        void handlePositiveFeedback()
      }}
    />
  )
}
