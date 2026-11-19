import type {
  ResearchAgentQueryResponse,
  ResearchMultiAnswer,
  StableResultSetEnvelope,
  StableResultSetPage,
} from '../api/researchAgent'
import {
  toScientificUserPresentation,
  type PresentedCandidate,
  type ScientificUserPresentation,
} from './scientificUserPresentation'

export type DisplayedScientificAnswer = {
  key: string
  answer: string
  result_id: string
  evidence_ids: string[]
  source_ids: string[]
  position: number
}

export type StablePaginationState = {
  resultSetId: string
  page: number
  pageSize: number
  totalAnswers: number
  totalPages: number
  expiresAt: string | null
  rerunPerformed: boolean
}

export type HomeResearchDisplayModel = {
  presentation: ScientificUserPresentation | null
  /** Page answers from Stable Result Set when available; else legacy presentation answers. */
  answers: DisplayedScientificAnswer[]
  pagination: StablePaginationState | null
  failureClass: string | null
  failureCode: string | null
  /** True when Stable Result Set is the authority for order/identity/pages. */
  usesStableResultSet: boolean
}

function normalizeAnswerText(text: string): string {
  return text.trim().replace(/\s+/g, ' ').toLowerCase()
}

function fromMultiAnswerRows(rows: ResearchMultiAnswer[] | undefined | null): DisplayedScientificAnswer[] {
  if (!Array.isArray(rows)) {
    return []
  }
  const seen = new Set<string>()
  const out: DisplayedScientificAnswer[] = []
  for (const row of rows) {
    const answer = typeof row?.answer === 'string' ? row.answer.trim() : ''
    if (answer === '') {
      continue
    }
    const norm = normalizeAnswerText(answer)
    if (seen.has(norm)) {
      continue
    }
    seen.add(norm)
    const resultId = (row.result_id ?? row.answer_id ?? `ans-${out.length}`).toString().trim() || `ans-${out.length}`
    out.push({
      key: (row.answer_id ?? resultId).toString(),
      answer,
      result_id: resultId,
      evidence_ids: Array.isArray(row.evidence_ids) ? row.evidence_ids.filter((id): id is string => typeof id === 'string') : [],
      source_ids: Array.isArray(row.source_ids) ? row.source_ids.filter((id): id is string => typeof id === 'string') : [],
      position: typeof row.position === 'number' ? row.position : out.length,
    })
  }
  return out
}

function fromLegacyPresentation(presentation: ScientificUserPresentation | null): DisplayedScientificAnswer[] {
  if (!presentation) {
    return []
  }
  const rows: DisplayedScientificAnswer[] = []
  const seen = new Set<string>()
  const push = (answer: string, resultId: string, position: number) => {
    const text = answer.trim()
    if (text === '') {
      return
    }
    const norm = normalizeAnswerText(text)
    if (seen.has(norm)) {
      return
    }
    seen.add(norm)
    rows.push({
      key: resultId || `legacy-${position}`,
      answer: text,
      result_id: resultId || `legacy-${position}`,
      evidence_ids: [],
      source_ids: [],
      position,
    })
  }
  if (presentation.primary_answer) {
    push(presentation.primary_answer, 'primary', 0)
  }
  presentation.candidates.forEach((candidate: PresentedCandidate, index) => {
    push(candidate.answer, candidate.result_id || `candidate-${index}`, index + 1)
  })
  return rows
}

export function extractStableResultSetId(
  response: ResearchAgentQueryResponse | null | undefined,
): string | null {
  const id = response?.stable_result_set?.result_set_id
  return typeof id === 'string' && id.trim() !== '' ? id.trim() : null
}

export function extractInitialStablePage(
  response: ResearchAgentQueryResponse | null | undefined,
): StableResultSetPage | null {
  const envelope = response?.stable_result_set
  if (!envelope || typeof envelope !== 'object') {
    return null
  }
  const page = envelope.page
  if (page && typeof page === 'object' && Array.isArray(page.answers)) {
    return page
  }
  // Synthesize page-1 slice from multi_answers when envelope lacks embedded page.
  const id = extractStableResultSetId(response)
  if (!id) {
    return null
  }
  const answers = fromMultiAnswerRows(response?.multi_answers)
  if (answers.length === 0 && Array.isArray(response?.multi_answers) === false) {
    return null
  }
  const pageSize = typeof envelope.page_size === 'number' && envelope.page_size > 0 ? envelope.page_size : 10
  return {
    result_set_id: id,
    page: 1,
    page_size: pageSize,
    total_answers: typeof envelope.total_answers === 'number' ? envelope.total_answers : answers.length,
    total_pages: typeof envelope.total_pages === 'number' ? envelope.total_pages : (answers.length === 0 ? 0 : 1),
    answers: response?.multi_answers ?? [],
    references: response?.citations,
    status: envelope.status,
    expires_at: envelope.expires_at ?? undefined,
    rerun_performed: false,
  }
}

export function paginationFromPage(page: StableResultSetPage): StablePaginationState {
  return {
    resultSetId: page.result_set_id,
    page: page.page,
    pageSize: page.page_size > 0 ? page.page_size : 10,
    totalAnswers: page.total_answers,
    totalPages: page.total_pages,
    expiresAt: page.expires_at ?? null,
    rerunPerformed: page.rerun_performed === true,
  }
}

/**
 * Build display model: Stable Result Set page is authority when present;
 * otherwise fall back to user_presentation / candidates without claiming stable pagination.
 */
export function buildHomeResearchDisplayModel(input: {
  queryResponse: ResearchAgentQueryResponse | null
  stablePage: StableResultSetPage | null
}): HomeResearchDisplayModel {
  const presentation = toScientificUserPresentation(input.queryResponse)
  const failureClass = input.stablePage?.failure_class
    ?? input.queryResponse?.failure_class
    ?? null
  const failureCode = input.stablePage?.failure_code
    ?? input.queryResponse?.failure_code
    ?? null

  if (input.stablePage && input.stablePage.result_set_id) {
    const answers = fromMultiAnswerRows(input.stablePage.answers)
    return {
      presentation,
      answers,
      pagination: paginationFromPage(input.stablePage),
      failureClass: typeof failureClass === 'string' ? failureClass : null,
      failureCode: typeof failureCode === 'string' ? failureCode : null,
      usesStableResultSet: true,
    }
  }

  // Prefer multi_answers from query when stable set missing but multi_answers present.
  const multi = fromMultiAnswerRows(input.queryResponse?.multi_answers)
  if (multi.length > 0) {
    return {
      presentation,
      answers: multi,
      pagination: null,
      failureClass: typeof failureClass === 'string' ? failureClass : null,
      failureCode: typeof failureCode === 'string' ? failureCode : null,
      usesStableResultSet: false,
    }
  }

  return {
    presentation,
    answers: fromLegacyPresentation(presentation),
    pagination: null,
    failureClass: typeof failureClass === 'string' ? failureClass : null,
    failureCode: typeof failureCode === 'string' ? failureCode : null,
    usesStableResultSet: false,
  }
}

/** User-facing i18n key for failure_class — never expose raw codes in UI copy. */
export function researchFailureTranslationKey(
  failureClass: string | null | undefined,
  failureCode?: string | null,
): string | null {
  const code = (failureCode ?? '').trim()
  if (code === 'stable_result_set_missing_or_expired') {
    return 'website.research.failureResultSetExpired'
  }
  switch ((failureClass ?? '').trim()) {
    case 'NO_SCIENTIFIC_MATCH':
      return 'website.research.failureNoMatch'
    case 'SOURCE_UNAVAILABLE':
      return 'website.research.failureSourceUnavailable'
    case 'ALL_RESULTS_REJECTED':
      return 'website.research.failureAllRejected'
    case 'INSUFFICIENT_EVIDENCE':
      return 'website.research.failureInsufficientEvidence'
    case 'COMPOSER_FAILURE':
      return 'website.research.failureComposer'
    case 'RESULT_SET_FAILURE':
      return 'website.research.failureResultSet'
    case 'MULTI_ANSWER_PRODUCTION_FAILURE':
      return 'website.research.failureMultiAnswer'
    default:
      return null
  }
}

export function isStableEnvelopeReady(envelope: StableResultSetEnvelope | null | undefined): boolean {
  return Boolean(envelope?.created && typeof envelope.result_set_id === 'string' && envelope.result_set_id.trim() !== '')
}
