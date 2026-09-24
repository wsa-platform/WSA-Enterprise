import { request } from './client'

export type ResearchAgentCitation = {
  title?: string
  doi?: string | null
  url?: string | null
  source_type?: string | null
  authors?: string[]
  organization?: string | null
  journal?: string | null
  publication_year?: number | null
  abstract?: string | null
  citation_id?: string
  source_id?: string
  evidence_id?: string
}

/** Stage 5 conflict item as already emitted by backend synthesis (string or detail map). */
export type ResearchAgentConflict =
  | string
  | {
      type?: string
      detail?: string
      claim_id?: string
      relationship?: string
      [key: string]: unknown
    }

export type ResearchAnswerCandidate = {
  answer: string
  result_id?: string
  evidence_ids?: string[]
  source_ids?: string[]
}

export type ResearchUserPresentationPayload = {
  primary_answer?: string | null
  human_status?: 'answered' | 'insufficient'
  user_notice_code?: string | null
  candidates?: Array<{ result_id: string; answer: string }>
  sources?: Array<{
    result_id: string
    title: string
    authors?: string[]
    organization?: string | null
    journal?: string | null
    publication_year?: number | null
    original_url?: string | null
  }>
  answer_language?: string | null
  candidate_selection?: {
    threshold: number
    presented_count: number
    confidence_exposed: boolean
    directness_unchanged: boolean
  }
}

export type ResearchAgentQueryResponse = {
  status?: string
  answer?: string | null
  concise_summary?: string | null
  additional_information?: string | null
  citations?: ResearchAgentCitation[]
  answer_candidates?: ResearchAnswerCandidate[]
  user_presentation?: ResearchUserPresentationPayload
  confidence?: number
  limitations?: string[]
  uncertainty?: string | null
  conflicts?: ResearchAgentConflict[]
  language?: string | null
  research_metadata?: {
    direct_evidence_gate?: string
    evidence_sufficient?: boolean
    sufficiency_mode?: string
    [key: string]: unknown
  }
  message?: string
  error?: {
    code?: string
    http_status?: number
    message?: string
    details?: unknown
  }
}

/** Present backend conflict payloads without scientific reinterpretation. */
export function formatResearchConflictText(conflict: ResearchAgentConflict): string {
  if (typeof conflict === 'string') return conflict
  const detail = conflict.detail ?? conflict.relationship ?? conflict.type
  if (typeof detail === 'string' && detail.trim() !== '') return detail
  try {
    return JSON.stringify(conflict)
  } catch {
    return 'conflict'
  }
}

export type HomePositiveFeedbackPayload = {
  polarity: 'positive'
  question?: string
  question_language?: string
  answer_language?: string
  ui_locale?: string
  research_source: 'home'
  what_worked?: string
}

/** Build the existing R7 positive-only Home feedback payload (no new fields). */
export function buildHomePositiveFeedbackPayload(input: {
  question: string
  uiLocale: string
  answerLanguage?: string | null
}): HomePositiveFeedbackPayload {
  return {
    polarity: 'positive',
    research_source: 'home',
    question: input.question.trim(),
    ui_locale: input.uiLocale,
    ...(input.answerLanguage && input.answerLanguage.trim() !== ''
      ? { answer_language: input.answerLanguage.trim() }
      : {}),
  }
}

/**
 * Compatibility slug only (R1 MODEL B).
 * Server resolves the public tenant; this value is ignored for persistence ownership.
 */
function publicOrganizationSlug(): string {
  return (import.meta.env.VITE_PUBLIC_ORG_SLUG as string | undefined) ?? 'wsa-demo'
}

/** POST /public/research-agent/query — Laravel proxy only; browser never calls OpenAlex/Crossref. */
export function queryPublicResearchAgent(query: string): Promise<ResearchAgentQueryResponse> {
  return request<ResearchAgentQueryResponse>('/public/research-agent/query', {
    method: 'POST',
    body: JSON.stringify({
      // Non-authoritative compatibility field — server binds public tenant (MODEL B / R1).
      organization: publicOrganizationSlug(),
      query: query.trim(),
    }),
  })
}

/** POST /public/research-agent/feedback — R7 positive-feedback-only dataset. */
export function submitResearchFeedback(payload: {
  polarity: 'positive' | 'negative'
  question?: string
  question_language?: string
  answer_language?: string
  ui_locale?: string
  research_source?: 'home' | 'crop'
  library_item_id?: number
  what_worked?: string
}): Promise<{ status: string; persisted: boolean; reason?: string }> {
  return request('/public/research-agent/feedback', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}
