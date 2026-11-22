import { useEffect, useRef, useState, type FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import i18n, { getCurrentLanguage } from '../i18n/config'
import { ApiError } from '../api/client'
import { translateApiError } from '../i18n/apiErrors'
import {
  buildHomePositiveFeedbackPayload,
  fetchStableResearchResultsPage,
  queryPublicResearchAgent,
  resolveClaimAttributionLedger,
  submitResearchFeedback,
  type ResearchAgentCitation,
  type ResearchAgentQueryResponse,
  type StableResultSetPage,
} from '../api/researchAgent'
import { ResearchSourceLink } from './ResearchSourceLink'
import { ScientificResearchResultsList } from './ScientificResearchResultsList'
import { createSearchEpisode, resolveHomeRestoredEpisode } from './scientificSearchEpisode'
import { toScientificUserPresentation, userNoticeTranslationKey } from './scientificUserPresentation'
import {
  buildHomeResearchDisplayModel,
  extractInitialStablePage,
  extractStableResultSetId,
  researchFailureTranslationKey,
  type DisplayedScientificAnswer,
  type StablePaginationState,
} from './stableResearchPresentation'

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
  answers: DisplayedScientificAnswer[]
  pagination: StablePaginationState | null
  pageLoading?: boolean
  pageError?: string | null
  failureMessage?: string | null
  episodeId?: string | null
  onQueryChange: (value: string) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
  onPageChange?: (page: number) => void
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
  answers,
  pagination,
  pageLoading = false,
  pageError = null,
  failureMessage = null,
  episodeId = null,
  onQueryChange,
  onSubmit,
  onPageChange,
  feedbackState = 'idle',
  feedbackErrorMessage = null,
  onPositiveFeedback,
}: HomeScientificResearchSearchViewProps) {
  const { t } = useTranslation()
  const presentation = toScientificUserPresentation(result)
  const sources = presentation?.sources ?? []
  const results = presentation?.results ?? []
  const claimLedger = resolveClaimAttributionLedger(result)
  const noticeKey = userNoticeTranslationKey(presentation?.user_notice_code)
  const showFeedback = Boolean(result) && typeof onPositiveFeedback === 'function'
  const feedbackBusy = feedbackState === 'submitting'
  const feedbackDone = feedbackState === 'success'
  const feedbackDisabled = feedbackBusy || feedbackDone || loading || pageLoading

  const primary = answers[0] ?? null
  const alternatives = answers.slice(1)
  const hasStablePagination = Boolean(pagination && pagination.totalPages > 1 && onPageChange)
  const apiStatus = result?.status ?? null
  const failureClass = result?.failure_class ?? null
  const failureCode = result?.failure_code ?? null

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

      {!loading && !error && failureMessage && answers.length === 0 ? (
        <p
          className="hp-research-status hp-research-status--error"
          role="status"
          data-testid="home-research-failure"
        >
          {failureMessage}
        </p>
      ) : null}

      {!loading && !error && result ? (
        <div className="hp-research-result" aria-live="polite" data-testid="home-research-presentation">
          <ScientificResearchResultsList
            results={results}
            episodeId={episodeId}
            testId="home-research-results"
          />

          {primary ? (
            <div className="hp-research-answer" data-testid="home-research-primary-answer">
              <h3>{t('website.research.answerHeading')}</h3>
              {primary.answer.split('\n').map((paragraph, index) => (
                <p key={`answer-${index}`}>{paragraph}</p>
              ))}
            </div>
          ) : (
            <p className="hp-research-status" role="status" data-testid="home-research-no-answer">
              {failureMessage || t('website.research.noAnswer')}
            </p>
          )}

          {noticeKey && presentation?.human_status === 'answered' ? (
            <p className="hp-research-status" data-testid="home-research-notice" role="status">
              {t(noticeKey)}
            </p>
          ) : null}

          {alternatives.length > 0 ? (
            <div className="hp-research-alternatives" data-testid="home-research-alternatives">
              <h3>{t('website.research.independentAnswersHeading')}</h3>
              <ul>
                {alternatives.map((candidate, index) => (
                  <li key={candidate.key || candidate.result_id || `alt-${index}`}>
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

          {hasStablePagination && pagination ? (
            <nav
              className="hp-research-pagination"
              aria-label={t('website.research.paginationLabel')}
              data-testid="home-research-pagination"
            >
              <p className="hp-research-status" data-testid="home-research-pagination-meta">
                {t('website.research.paginationMeta', {
                  page: pagination.page,
                  totalPages: pagination.totalPages,
                  totalAnswers: pagination.totalAnswers,
                })}
              </p>
              <div className="hp-research-pagination-controls">
                <button
                  type="button"
                  className="gs-btn"
                  data-testid="home-research-page-prev"
                  disabled={pageLoading || pagination.page <= 1}
                  onClick={() => onPageChange?.(pagination.page - 1)}
                >
                  {t('website.research.paginationPrev')}
                </button>
                <button
                  type="button"
                  className="gs-btn"
                  data-testid="home-research-page-next"
                  disabled={pageLoading || pagination.page >= pagination.totalPages}
                  onClick={() => onPageChange?.(pagination.page + 1)}
                >
                  {t('website.research.paginationNext')}
                </button>
              </div>
              {pageLoading ? (
                <p className="hp-research-status" aria-live="polite" data-testid="home-research-page-loading">
                  {t('website.research.pageLoading')}
                </p>
              ) : null}
              {pageError ? (
                <p
                  className="hp-research-status hp-research-status--error"
                  role="alert"
                  data-testid="home-research-page-error"
                >
                  {pageError}
                </p>
              ) : null}
            </nav>
          ) : null}

          {apiStatus || failureClass ? (
            <p
              className="hp-research-status"
              data-testid="home-research-api-status"
              data-status={apiStatus ?? ''}
              data-failure-class={failureClass ?? ''}
              data-failure-code={failureCode ?? ''}
            >
              {apiStatus ?? failureClass}
            </p>
          ) : null}

          {sources.length === 0 ? (
            <div className="hp-research-citations" data-testid="home-research-sources-empty">
              <h3>{t('website.research.sourcesHeading')}</h3>
              <p>{t('website.research.noEligibleDirectCitations')}</p>
            </div>
          ) : (
            <div className="hp-research-citations" data-testid="home-research-citations">
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
                  const altForLinks = alternatives.map((a) => ({
                    result_id: a.result_id,
                    answer: a.answer,
                  }))
                  return (
                    <li key={`${source.result_id}-${index}`} data-testid="home-research-citation-item">
                      <ResearchSourceLink
                        citation={citation}
                        label={label}
                        answer={primary?.answer ?? null}
                        index={index}
                        alternatives={altForLinks}
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

          {claimLedger.length > 0 ? (
            <div
              className="hp-research-claim-ledger"
              data-testid="home-research-claim-attribution-ledger"
            >
              <h3>{t('website.research.claimAttributionHeading', { defaultValue: 'Claim attribution' })}</h3>
              <ul>
                {claimLedger.map((row, index) => (
                  <li
                    key={`${row.claim_id ?? 'claim'}-${index}`}
                    data-testid="home-research-claim-attribution-row"
                    data-claim-id={row.claim_id ?? ''}
                    data-l7={row.l7_final_claim_attribution_proven ? 'true' : 'false'}
                    data-source-keys={(row.source_keys ?? []).join(',')}
                  >
                    <span data-testid="home-research-claim-text">
                      {row.claim_text?.trim() || row.claim_id || `claim-${index + 1}`}
                    </span>
                    {(row.attributions ?? []).length > 0 ? (
                      <ul data-testid="home-research-claim-evidence-links">
                        {(row.attributions ?? []).map((edge, edgeIndex) => (
                          <li
                            key={`${edge.evidence_id ?? 'ev'}-${edgeIndex}`}
                            data-testid="home-research-claim-evidence-edge"
                            data-evidence-id={edge.evidence_id ?? ''}
                            data-source-key={edge.source_key ?? ''}
                            data-provider-record-id={edge.provider_record_id ?? ''}
                            data-citation-id={edge.citation_id ?? ''}
                          >
                            {[edge.source_key, edge.evidence_id, edge.citation_id]
                              .filter((part) => typeof part === 'string' && part.trim() !== '')
                              .join(' · ')}
                          </li>
                        ))}
                      </ul>
                    ) : null}
                  </li>
                ))}
              </ul>
            </div>
          ) : null}

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

/** Homepage scientific research search — Query API + Stable Result Set pagination (Rev 6.3). */
export function HomeScientificResearchSearch() {
  const [searchParams, setSearchParams] = useSearchParams()
  const [query, setQuery] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [result, setResult] = useState<ResearchAgentQueryResponse | null>(null)
  const [stablePage, setStablePage] = useState<StableResultSetPage | null>(null)
  const [pageLoading, setPageLoading] = useState(false)
  const [pageError, setPageError] = useState<string | null>(null)
  const [episodeId, setEpisodeId] = useState<string | null>(null)
  const [submittedQuestion, setSubmittedQuestion] = useState<string | null>(null)
  const [feedbackState, setFeedbackState] = useState<HomeFeedbackUiState>('idle')
  const [feedbackErrorMessage, setFeedbackErrorMessage] = useState<string | null>(null)

  const searchGenerationRef = useRef(0)
  const pageRequestRef = useRef(0)
  const resultSetIdRef = useRef<string | null>(null)

  const display = buildHomeResearchDisplayModel({
    queryResponse: result,
    stablePage,
  })

  const failureKey = researchFailureTranslationKey(display.failureClass, display.failureCode)
  const failureMessage = failureKey ? i18n.t(failureKey) : null

  useEffect(() => {
    const requested = searchParams.get('episode')
    const episode = resolveHomeRestoredEpisode(requested)
    if (!episode) {
      if (requested) {
        setResult(null)
        setStablePage(null)
        setEpisodeId(null)
        setSubmittedQuestion(null)
        resultSetIdRef.current = null
      }
      return
    }
    setQuery(episode.query)
    setResult(episode.response)
    setEpisodeId(episode.episodeId)
    setSubmittedQuestion(episode.query)
    const initialPage = extractInitialStablePage(episode.response)
    setStablePage(initialPage)
    resultSetIdRef.current = extractStableResultSetId(episode.response)
  }, [searchParams])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const normalized = normalizeResearchQuery(query)
    if (!normalized || loading) return

    const generation = ++searchGenerationRef.current
    pageRequestRef.current += 1
    setLoading(true)
    setError(null)
    setPageError(null)
    setFeedbackState('idle')
    setFeedbackErrorMessage(null)
    // Clear previous result set so old answers never linger as the new search.
    setResult(null)
    setStablePage(null)
    resultSetIdRef.current = null

    try {
      const response = await queryPublicResearchAgent(normalized)
      if (generation !== searchGenerationRef.current) {
        return
      }
      const episode = createSearchEpisode({
        query: normalized,
        language: getCurrentLanguage(),
        response,
      })
      const initialPage = extractInitialStablePage(response)
      const resultSetId = extractStableResultSetId(response)
      setResult(response)
      setStablePage(initialPage)
      resultSetIdRef.current = resultSetId
      setSubmittedQuestion(normalized)
      setEpisodeId(episode.episodeId)
      setSearchParams({ episode: episode.episodeId }, { replace: true })
    } catch (submitError: unknown) {
      if (generation !== searchGenerationRef.current) {
        return
      }
      setError(resolveResearchSearchError(submitError))
      setResult(null)
      setStablePage(null)
      setSubmittedQuestion(null)
      setEpisodeId(null)
      resultSetIdRef.current = null
    } finally {
      if (generation === searchGenerationRef.current) {
        setLoading(false)
      }
    }
  }

  async function handlePageChange(page: number) {
    const resultSetId = resultSetIdRef.current
    if (!resultSetId || pageLoading || loading) {
      return
    }
    if (display.pagination && (page < 1 || page > display.pagination.totalPages)) {
      return
    }

    const requestId = ++pageRequestRef.current
    const previousPage = stablePage
    setPageLoading(true)
    setPageError(null)

    try {
      const pagePayload = await fetchStableResearchResultsPage(resultSetId, page)
      if (requestId !== pageRequestRef.current) {
        return
      }
      if (pagePayload.result_set_id !== resultSetId) {
        setPageError(i18n.t('website.research.failurePageRetrieval'))
        return
      }
      if (pagePayload.rerun_performed === true) {
        // Contract violation — keep previous page and surface failure.
        setPageError(i18n.t('website.research.failurePageRetrieval'))
        return
      }
      setStablePage(pagePayload)
    } catch (pageErr: unknown) {
      if (requestId !== pageRequestRef.current) {
        return
      }
      // Keep current page content on failure.
      if (previousPage) {
        setStablePage(previousPage)
      }
      if (pageErr instanceof ApiError && pageErr.status === 404) {
        setPageError(i18n.t('website.research.failureResultSetExpired'))
      } else {
        setPageError(i18n.t('website.research.failurePageRetrieval'))
      }
    } finally {
      if (requestId === pageRequestRef.current) {
        setPageLoading(false)
      }
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
      answers={display.answers}
      pagination={display.pagination}
      pageLoading={pageLoading}
      pageError={pageError}
      failureMessage={failureMessage}
      episodeId={episodeId}
      feedbackState={feedbackState}
      feedbackErrorMessage={feedbackErrorMessage}
      onQueryChange={setQuery}
      onSubmit={(event) => {
        void handleSubmit(event)
      }}
      onPageChange={(page) => {
        void handlePageChange(page)
      }}
      onPositiveFeedback={() => {
        void handlePositiveFeedback()
      }}
    />
  )
}
