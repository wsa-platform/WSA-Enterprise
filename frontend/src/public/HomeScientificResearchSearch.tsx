import { useState, type FormEvent } from 'react'
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
      return 'تعذر العثور على خدمة البحث العلمي أو مؤسسة المنصة. تحقق من تشغيل الخادم وإعدادات المؤسسة.'
    }
    if (error.status === 0 || error.status >= 500) {
      return 'تعذر الاتصال بخدمة البحث العلمي. تحقق من تشغيل الخادم ثم أعد المحاولة.'
    }
    return error.message || 'تعذر إكمال البحث العلمي حالياً. يرجى المحاولة لاحقاً.'
  }

  return 'تعذر الاتصال بخدمة البحث العلمي. تحقق من تشغيل الخادم ثم أعد المحاولة.'
}

export type HomeScientificResearchSearchViewProps = {
  query: string
  loading: boolean
  error: string | null
  result: ResearchAgentQueryResponse | null
  onQueryChange: (value: string) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

function citationLabel(citation: ResearchAgentCitation, index: number): string {
  return citation.title?.trim() || `مصدر ${index + 1}`
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
  const answer = result?.answer?.trim() || result?.concise_summary?.trim() || null
  const citations = result?.citations ?? []

  return (
    <section
      className="hp-research-section"
      aria-labelledby="home-research-title"
    >
      <div className="hp-research-heading">
        <h2 id="home-research-title">البحث العلمي الزراعي</h2>
        <span className="hp-category-rule" aria-hidden="true" />
      </div>
      <p className="hp-research-support">
        اطرح سؤالاً زراعياً واحصل على إجابة مستندة إلى مصادر علمية موثوقة عبر خادم المنصة.
      </p>

      <form className="hp-research-form" onSubmit={onSubmit} noValidate>
        <label className="hp-research-label" htmlFor="home-research-query">
          سؤال البحث العلمي
        </label>
        <div className="hp-research-controls">
          <input
            id="home-research-query"
            className="hp-research-input"
            type="search"
            name="research_query"
            value={query}
            onChange={(event) => onQueryChange(event.target.value)}
            placeholder="مثال: جدولة الري لمحاصيل الحبوب في المناطق الجافة"
            disabled={loading}
            autoComplete="off"
          />
          <button
            type="submit"
            className="gs-btn gs-btn-primary hp-research-submit"
            disabled={loading}
          >
            بحث علمي
          </button>
        </div>
      </form>

      {loading ? (
        <p className="hp-research-status" aria-live="polite">
          جاري البحث في المصادر العلمية...
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
              <h3>الإجابة</h3>
              {answer.split('\n').map((paragraph, index) => (
                <p key={`answer-${index}`}>{paragraph}</p>
              ))}
            </div>
          ) : (
            <p className="hp-research-status" role="status">
              لم تتوفر إجابة موثقة كافية لهذا السؤال حالياً.
            </p>
          )}

          {result.status ? (
            <p className="hp-research-meta">الحالة: {result.status}</p>
          ) : null}

          <div className="hp-research-citations">
            <h3>المصادر</h3>
            {citations.length === 0 ? (
              <p className="hp-research-status">لا توجد استشهادات معروضة.</p>
            ) : (
              <ul>
                {citations.map((citation, index) => (
                  <li key={`${citation.doi ?? citation.url ?? citation.title ?? 'c'}-${index}`}>
                    <strong>{citationLabel(citation, index)}</strong>
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
