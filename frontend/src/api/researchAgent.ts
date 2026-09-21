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
  citation_id?: string
  source_id?: string
  evidence_id?: string
}

export type ResearchAgentQueryResponse = {
  status?: string
  answer?: string | null
  concise_summary?: string | null
  citations?: ResearchAgentCitation[]
  confidence?: number
  limitations?: string[]
  message?: string
  error?: {
    code?: string
    http_status?: number
    message?: string
    details?: unknown
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
