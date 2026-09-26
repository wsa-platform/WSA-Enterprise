import type {
  ResearchAgentQueryResponse,
  ResearchAnswerCandidate,
  ResearchPresentationResult,
} from '../api/researchAgent'
import { citationHref } from './citationHref'
import { isExternalSearchRedirect } from './researchViewer'

export const SCIENTIFIC_CANDIDATE_THRESHOLD = 0.50

/** Stage 4 evidence-item confidence. Distinct from overallConfidence. */
export const SEARCH_RESULT_CONFIDENCE_THRESHOLD = 0.50

export type PresentedSource = {
  result_id: string
  title: string
  authors?: string[]
  organization?: string | null
  journal?: string | null
  publication_year?: number | null
  original_url?: string | null
}

export type PresentedResearchResult = PresentedSource & {
  doi?: string | null
  abstract?: string | null
  pdf_url?: string | null
  confidence?: number
}

export type PresentedCandidate = {
  result_id: string
  answer: string
}

export type ScientificUserPresentation = {
  primary_answer: string | null
  human_status: 'answered' | 'insufficient'
  user_notice_code: string | null
  candidates: PresentedCandidate[]
  sources: PresentedSource[]
  results: PresentedResearchResult[]
  answer_language?: string | null
  candidate_selection?: {
    threshold: number
    presented_count: number
    confidence_exposed: boolean
    directness_unchanged: boolean
  }
}

function presentedSourceFromCitation(
  citation: NonNullable<ResearchAgentQueryResponse['citations']>[number],
  index: number,
): PresentedSource {
  const resultId = citation.citation_id?.trim()
    || citation.evidence_id?.trim()
    || citation.source_id?.trim()
    || `src-${index}`
  const rawUrl = citationHref(citation)
  const originalUrl = rawUrl && !isExternalSearchRedirect(rawUrl) ? rawUrl : null

  return {
    result_id: resultId,
    title: citation.title?.trim() || 'Research source',
    authors: citation.authors,
    organization: citation.organization ?? null,
    journal: citation.journal ?? null,
    publication_year: citation.publication_year ?? null,
    original_url: originalUrl,
  }
}

export function isEligibleScientificResearchResult(
  result: { result_id?: string; confidence?: number | null },
): boolean {
  const resultId = result.result_id?.trim() ?? ''
  if (resultId === '') {
    return false
  }
  if (typeof result.confidence !== 'number' || Number.isNaN(result.confidence)) {
    return false
  }
  return result.confidence >= SEARCH_RESULT_CONFIDENCE_THRESHOLD
}

function presentedResearchResults(
  rows: ResearchPresentationResult[] | undefined,
): PresentedResearchResult[] {
  if (!Array.isArray(rows)) {
    return []
  }
  const seen = new Set<string>()
  const results: PresentedResearchResult[] = []
  for (const row of rows) {
    if (!row || typeof row !== 'object') {
      continue
    }
    const resultId = row.result_id?.trim() ?? ''
    if (resultId === '' || seen.has(resultId) || !isEligibleScientificResearchResult(row)) {
      continue
    }
    seen.add(resultId)
    const rawUrl = row.original_url?.trim() || null
    results.push({
      result_id: resultId,
      title: row.title?.trim() || 'Research source',
      authors: Array.isArray(row.authors) ? row.authors : [],
      organization: row.organization ?? null,
      journal: row.journal ?? null,
      publication_year: row.publication_year ?? null,
      doi: row.doi ?? null,
      original_url: rawUrl && !isExternalSearchRedirect(rawUrl) ? rawUrl : null,
      abstract: row.abstract?.trim() || null,
      pdf_url: row.pdf_url?.trim() || null,
    })
  }
  return results
}

function presentedCandidates(
  candidates: ResearchAnswerCandidate[] | undefined,
  primary: string | null,
): PresentedCandidate[] {
  const seen = new Set<string>()
  const rows: PresentedCandidate[] = []
  for (const candidate of candidates ?? []) {
    const text = candidate.answer?.trim() ?? ''
    if (text === '') {
      continue
    }
    if (primary && text === primary) {
      continue
    }
    const key = text.toLowerCase()
    if (seen.has(key)) {
      continue
    }
    seen.add(key)
    rows.push({
      result_id: candidate.result_id?.trim() || `candidate-${rows.length}`,
      answer: text,
    })
  }
  return rows
}

/** Map any research payload to the user-facing presentation. Never forwards raw diagnostics. */
export function toScientificUserPresentation(
  result: ResearchAgentQueryResponse | null | undefined,
): ScientificUserPresentation | null {
  if (!result) {
    return null
  }

  const provided = result.user_presentation
  if (provided && typeof provided === 'object') {
    const primary = provided.primary_answer?.trim() || null
    return {
      primary_answer: primary,
      human_status: provided.human_status === 'insufficient' ? 'insufficient' : 'answered',
      user_notice_code: provided.user_notice_code ?? null,
      candidates: Array.isArray(provided.candidates) ? provided.candidates : [],
      sources: Array.isArray(provided.sources) ? provided.sources : [],
      results: presentedResearchResults(provided.results),
      answer_language: provided.answer_language ?? result.language ?? null,
      candidate_selection: {
        threshold: provided.candidate_selection?.threshold ?? SCIENTIFIC_CANDIDATE_THRESHOLD,
        presented_count: provided.candidate_selection?.presented_count ?? 0,
        confidence_exposed: false,
        directness_unchanged: true,
      },
    }
  }

  const sources = (result.citations ?? []).map((citation, index) => presentedSourceFromCitation(citation, index))

  return {
    primary_answer: null,
    human_status: 'insufficient',
    user_notice_code: 'insufficient_direct_evidence',
    candidates: presentedCandidates(result.answer_candidates, null),
    sources,
    results: [],
    answer_language: result.language ?? null,
    candidate_selection: {
      threshold: SCIENTIFIC_CANDIDATE_THRESHOLD,
      presented_count: presentedCandidates(result.answer_candidates, null).length,
      confidence_exposed: false,
      directness_unchanged: true,
    },
  }
}

export function userNoticeTranslationKey(code: string | null | undefined): string | null {
  if (code === 'insufficient_direct_evidence') {
    return 'website.research.noAnswer'
  }
  if (code === 'review_sources') {
    return 'website.research.reviewSourcesNotice'
  }
  return null
}
