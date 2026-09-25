import { useEffect, useState, type FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import i18n, { getCurrentLanguage } from '../i18n/config'
import { ApiError } from '../api/client'
import { translateApiError } from '../i18n/apiErrors'
import {
  buildHomePositiveFeedbackPayload,
  queryPublicResearchAgent,
  submitResearchFeedback,
  type ResearchAgentCitation,
  type ResearchAgentQueryResponse,
} from '../api/researchAgent'
import { ResearchSourceLink } from './ResearchSourceLink'
import { createSearchEpisode, resolveHomeRestoredEpisode } from './scientificSearchEpisode'
import { toScientificUserPresentation, userNoticeTranslationKey } from './scientificUserPresentation'

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
  episodeId?: string | null
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
  episodeId = null,
  onQueryChange,
  onSubmit,
  feedbackState = 'idle',
  feedbackErrorMessage = null,
  onPositiveFeedback,
}: HomeScientificResearchSearchViewProps) {
  const { t } = useTranslation()
  const presentation = toScientificUserPresentation(result)
  const answer = presentation?.primary_answer ?? null
  const alternativeAnswers = presentation?.candidates ?? []
  const sources = presentation?.sources ?? []
  const noticeKey = userNoticeTranslationKey(presentation?.user_notice_code)
  const showFeedback = Boolean(result) && typeof onPositiveFeedback === 'function'
  const feedbackBusy = feedbackState === 'submitting'
  const feedbackDone = feedbackState === 'success'
  const feedbackDisabled = feedbackBusy || feedbackDone || loading

  return (
    <section
      id="home-research"
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
        <div className="hp-research-result" aria-live="polite" data-testid="home-research-presentation">
          {answer ? (
            <div className="hp-research-answer" data-testid="home-research-primary-answer">
              <h3>{t('website.research.answerHeading')}</h3>
              {answer.split('\n').map((paragraph, index) => (
                <p key={`answer-${index}`}>{paragraph}</p>
              ))}
            </div>
          ) : (
            <p className="hp-research-status" role="status" data-testid="home-research-no-answer">
              {t('website.research.noAnswer')}
            </p>
          )}

          {noticeKey && presentation?.human_status === 'answered' ? (
            <p className="hp-research-status" data-testid="home-research-notice" role="status">
              {t(noticeKey)}
            </p>
          ) : null}

          {alternativeAnswers.length > 0 ? (
            <div className="hp-research-alternatives" data-testid="home-research-alternatives">
              <h3>{t('website.research.alternativeAnswersHeading')}</h3>
              <ul>
                {alternativeAnswers.map((candidate, index) => (
                  <li key={candidate.result_id ?? `alt-${index}`}>
                    {episodeId ? (
                      <a
                        href={`/research/result/${encodeURIComponent(candidate.result_id)}?episode=${encodeURIComponent(episodeId)}`}
                        data-testid="home-research-candidate-link"
                      >
                        {candidate.answer}
                      </a>
                    ) : (
                      candidate.answer
                    )}
                  </li>
                ))}
              </ul>
            </div>
          ) : null}

          {sources.length === 0 ? (
            <div className="hp-research-citations" data-testid="home-research-sources-empty">
              <h3>{t('website.research.sourcesHeading')}</h3>
              <p>{t('website.research.noEligibleDirectCitations')}</p>
            </div>
          ) : (
            <div className="hp-research-citations">
              <h3>{t('website.research.sourcesHeading')}</h3>
              <ul>
                {sources.map((source, index) => {
                  const citation: ResearchAgentCitation = {
                    citation_id: source.result_id,
                    title: source.title,
                    url: source.original_url,
                    authors: source.authors,
                    organization: source.organization,
                    journal: source.journal,
                    publication_year: source.publication_year,
                  }
                  const label = citationLabel(
                    citation,
                    index,
                    t('website.research.citationFallback', { index: index + 1 }),
                  )
                  return (
                    <li key={`${source.result_id}-${index}`}>
                      <ResearchSourceLink
                        citation={citation}
                        label={label}
                        answer={answer}
                        index={index}
                        alternatives={alternativeAnswers}
                        episodeId={episodeId}
                        resultId={source.result_id}
                      />
                      {source.organization ? (
                        <span className="hp-research-cite-meta"> — {source.organization}</span>
                      ) : null}
                    </li>
                  )
                })}
              </ul>
            </div>
          )}

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
  const [searchParams, setSearchParams] = useSearchParams()
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [result, setResult] = useState<ResearchAgentQueryResponse | null>(null)
  const [episodeId, setEpisodeId] = useState<string | null>(null)
  const [submittedQuestion, setSubmittedQuestion] = useState<string | null>(null)
  const [feedbackState, setFeedbackState] = useState<HomeFeedbackUiState>('idle')
  const [feedbackErrorMessage, setFeedbackErrorMessage] = useState<string | null>(null)

  useEffect(() => {
    const requested = searchParams.get('episode')
    const episode = resolveHomeRestoredEpisode(requested)
    if (!episode) {
      if (requested) {
        setResult(null)
        setEpisodeId(null)
        setSubmittedQuestion(null)
      }
      return
    }
    setQuery(episode.query)
    setResult(episode.response)
    setEpisodeId(episode.episodeId)
    setSubmittedQuestion(episode.query)
  }, [searchParams])

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
      const episode = createSearchEpisode({
        query: normalized,
        language: getCurrentLanguage(),
        response,
      })
      setResult(response)
      setSubmittedQuestion(normalized)
      setEpisodeId(episode.episodeId)
      setSearchParams({ episode: episode.episodeId }, { replace: true })
    } catch (submitError: unknown) {
      setError(resolveResearchSearchError(submitError))
      setResult(null)
      setSubmittedQuestion(null)
      setEpisodeId(null)
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
      episodeId={episodeId}
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
