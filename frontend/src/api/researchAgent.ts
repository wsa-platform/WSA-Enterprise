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
}

function publicOrganizationSlug(): string {
  return (import.meta.env.VITE_PUBLIC_ORG_SLUG as string | undefined) ?? 'wsa-demo'
}

/** POST /public/research-agent/query — Laravel proxy only; browser never calls OpenAlex/Crossref. */
export function queryPublicResearchAgent(query: string): Promise<ResearchAgentQueryResponse> {
  return request<ResearchAgentQueryResponse>('/public/research-agent/query', {
    method: 'POST',
    body: JSON.stringify({
      organization: publicOrganizationSlug(),
      query: query.trim(),
    }),
  })
}
