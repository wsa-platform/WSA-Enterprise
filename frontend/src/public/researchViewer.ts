import type { ResearchAgentCitation, ResearchAnswerCandidate } from '../api/researchAgent'
import { citationHref } from './citationHref'

export const RESEARCH_VIEWER_PATH = '/research/result'

export type ResearchViewerRecord = {
  resultId: string
  title: string
  answer?: string | null
  alternativeAnswers?: string[]
  authors?: string[]
  organization?: string | null
  journal?: string | null
  publicationYear?: number | null
  abstract?: string | null
  doi?: string | null
  originalUrl?: string | null
  pdfUrl?: string | null
  fullText?: string | null
}

const STORAGE_PREFIX = 'wsa.research.viewer.'

export function researchViewerPath(resultId: string): string {
  return `${RESEARCH_VIEWER_PATH}/${encodeURIComponent(resultId)}`
}

function isDoiIdentity(value: string): boolean {
  const normalized = value.replace(/^(?:doi:|https?:\/\/(?:dx\.)?doi\.org\/)/i, '').trim()
  return /^10\.\d{4,9}\//.test(normalized)
}

function isCatalogIdentity(value: string): boolean {
  if (/(?:^|:\/\/)(?:[a-z0-9.-]+\.)?openalex\.org\//i.test(value)) {
    return true
  }
  if (/^[WAIC]\d+$/.test(value)) {
    return true
  }
  if (/^(?:consensus|semantic_scholar):/.test(value)) {
    return true
  }
  return /^[a-f0-9]{40}$/i.test(value)
}

function isInternalIdentity(value: string): boolean {
  const trimmed = value.trim()
  if (trimmed === '' || isDoiIdentity(trimmed)) {
    return false
  }
  if (isCatalogIdentity(trimmed)) {
    return true
  }
  return !/^https?:\/\//i.test(trimmed)
}

/** New IDs are never title-derived. Old src-N-title session keys still load if stored. */
export function researchResultId(citation: ResearchAgentCitation, fallbackIndex = 0): string {
  const citationId = citation.citation_id?.trim()
  if (citationId) {
    return citationId
  }
  const evidenceId = citation.evidence_id?.trim()
  if (evidenceId) {
    return evidenceId
  }
  const sourceId = citation.source_id?.trim()
  if (sourceId && isInternalIdentity(sourceId)) {
    return sourceId
  }
  return `src-${fallbackIndex}`
}

export function researchViewerRecordFromCitation(
  citation: ResearchAgentCitation,
  answer?: string | null,
  fallbackIndex = 0,
  alternatives: ResearchAnswerCandidate[] = [],
): ResearchViewerRecord {
  const alternativeAnswers = alternatives
    .map((candidate) => candidate.answer?.trim() ?? '')
    .filter((text) => text !== '' && text !== (answer ?? '').trim())

  return {
    resultId: researchResultId(citation, fallbackIndex),
    title: citation.title?.trim() || 'Research source',
    answer: answer ?? null,
    alternativeAnswers,
    authors: Array.isArray(citation.authors) ? citation.authors.filter((author) => author.trim() !== '') : [],
    organization: citation.organization ?? null,
    journal: citation.journal ?? null,
    publicationYear: citation.publication_year ?? null,
    abstract: citation.abstract?.trim() || null,
    doi: citation.doi ?? null,
    originalUrl: citationHref(citation),
    pdfUrl: citation.pdf_url?.trim() || null,
  }
}

export function storeResearchViewerRecord(record: ResearchViewerRecord): void {
  if (typeof sessionStorage === 'undefined') {
    return
  }
  sessionStorage.setItem(STORAGE_PREFIX + record.resultId, JSON.stringify(record))
}

export function loadResearchViewerRecord(resultId: string): ResearchViewerRecord | null {
  if (typeof sessionStorage === 'undefined') {
    return null
  }
  const raw = sessionStorage.getItem(STORAGE_PREFIX + resultId)
  if (!raw) {
    return null
  }
  try {
    const parsed = JSON.parse(raw) as ResearchViewerRecord
    if (!parsed || typeof parsed.title !== 'string') {
      return null
    }
    return parsed
  } catch {
    return null
  }
}

export function activateResearchResult(
  citation: ResearchAgentCitation,
  answer?: string | null,
  fallbackIndex = 0,
  alternatives: ResearchAnswerCandidate[] = [],
): { viewerPath: string; record: ResearchViewerRecord } {
  const record = researchViewerRecordFromCitation(citation, answer, fallbackIndex, alternatives)
  storeResearchViewerRecord(record)
  return {
    viewerPath: researchViewerPath(record.resultId),
    record,
  }
}

export function isExternalSearchRedirect(url: string | null | undefined): boolean {
  if (!url) {
    return false
  }
  try {
    const host = new URL(url).hostname.toLowerCase()
    return host === 'google.com' || host.endsWith('.google.com') || host === 'www.google.com'
  } catch {
    return false
  }
}

/** A real clickable source must be an anchor. A plain-text URL must fail. */
export function isClickableAnchorMarkup(html: string, href: string): boolean {
  if (!href || !html.includes('<a')) {
    return false
  }
  const escaped = href.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  return new RegExp(`<a\\b[^>]*\\bhref=["']${escaped}["']`, 'i').test(html)
}
