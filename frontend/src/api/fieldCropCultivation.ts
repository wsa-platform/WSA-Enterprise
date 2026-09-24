import { request } from './client'
import type { ResearchAgentCitation, ResearchAnswerCandidate } from './researchAgent'

export type FieldCropCultivationReference = {
  organization?: string
  title?: string
  year?: number
  url?: string
  source_type?: string
}

export type FieldCropCultivationSection = {
  key: string
  title: string
  content: string
  source: FieldCropCultivationReference | null
  verified: boolean
}

/** Stage 5 claim shape from P7-U1 Crop dual-emit (presentation only). */
export type FieldCropAnswerClaim = {
  claim_id?: string
  claim_text?: string
  evidence_ids?: string[]
  source_ids?: string[]
  validation_status?: string
  claim_relationship?: string
  confidence?: number
  numerical_values?: string[]
  limitations?: string[]
  conditions?: string | null
  question_claim_id?: string | null
}

export type FieldCropAnswerConflict =
  | string
  | {
      type?: string
      detail?: string
      claim_id?: string
      relationship?: string
      [key: string]: unknown
    }

/**
 * P7-U1 dual-emit Crop profile: Stage 5 canonical fields at root + legacy compatibility.
 * Stage 5 fields are optional for older responses / library-only fixtures.
 */
export type FieldCropCultivationProfile = {
  crop: {
    id: string
    name: string
    category_id: string
    category_name: string
    scientific_name?: string
  }
  service_option: string
  knowledge_option?: string
  title: string
  load_state:
    | 'library_complete'
    | 'library_partial_completed'
    | 'library_missing'
    | 'scientific_generated'
    | 'insufficient_verified_sources'
    | 'retrieval_error'
    | 'knowledge_option_not_implemented'
    | 'organization_not_found'
    | 'public_organization_unavailable'
    | string
  message: string | null
  sections: FieldCropCultivationSection[]
  references: FieldCropCultivationReference[]
  library: {
    item_id: number | null
    slug: string | null
    reused_existing: boolean
    was_missing_before_retrieval?: boolean
    missing_sections_filled: string[]
    scientific_sections_retrieved?: string[]
    discoverers_used?: string[]
  }
  // —— P7-U1 canonical Stage 5 (optional for backward compatibility) ——
  status?: string
  stage?: number
  answer?: string | null
  concise_summary?: string | null
  detailed_explanation?: string | null
  additional_information?: string | null
  key_findings?: string[]
  claims?: FieldCropAnswerClaim[]
  citations?: ResearchAgentCitation[]
  answer_candidates?: ResearchAnswerCandidate[]
  evidence_references?: Array<Record<string, unknown>>
  confidence?: number
  limitations?: string[]
  uncertainty?: string | null
  conflicts?: FieldCropAnswerConflict[]
  language?: string
  research_metadata?: Record<string, unknown>
  observability?: Record<string, unknown>
  research_agent?: Record<string, unknown>
}

export type FieldCropCultivationQuery = {
  selectedCropId: string
  selectedCropName: string
  selectedCategoryId: string
  selectedCategoryName: string
  knowledgeOption?: string
  scientificName?: string
}

/** Primary scientific answer text from Stage 5 root fields (never UI locale). */
export function resolveCanonicalCropAnswerText(
  profile: Pick<FieldCropCultivationProfile, 'answer' | 'concise_summary'>,
): string | null {
  const answer = profile.answer?.trim()
  if (answer) return answer
  const summary = profile.concise_summary?.trim()
  if (summary) return summary
  return null
}

export function hasCanonicalStage5Answer(
  profile: Pick<FieldCropCultivationProfile, 'answer' | 'concise_summary'>,
): boolean {
  return resolveCanonicalCropAnswerText(profile) !== null
}

/** Statuses where legacy sections must not be presented as a normal scientific answer. */
export function isLimitedScientificStatus(status: string | undefined | null): boolean {
  if (!status) return false
  const normalized = status.toLowerCase()
  return (
    normalized.includes('insufficient') ||
    normalized === 'conflicted' ||
    normalized.includes('conflict') ||
    normalized.includes('validation_fail') ||
    normalized.includes('retrieval_error')
  )
}

/**
 * Explicit rendering mode for Crop dual-emit.
 * - canonical: Stage 5 answer body is primary
 * - limited: Stage 5 reports insufficient/conflict (no legacy-as-answer fallback)
 * - legacy: no usable Stage 5 answer — compatibility sections
 */
export function resolveCropAnswerRenderMode(
  profile: Pick<FieldCropCultivationProfile, 'answer' | 'concise_summary' | 'status'>,
): 'canonical' | 'limited' | 'legacy' {
  if (hasCanonicalStage5Answer(profile)) {
    return 'canonical'
  }
  if (isLimitedScientificStatus(profile.status)) {
    return 'limited'
  }
  return 'legacy'
}

export function formatCropConflictText(conflict: FieldCropAnswerConflict): string {
  if (typeof conflict === 'string') return conflict
  const detail = conflict.detail ?? conflict.relationship ?? conflict.type
  if (typeof detail === 'string' && detail.trim() !== '') return detail
  try {
    return JSON.stringify(conflict)
  } catch {
    return 'conflict'
  }
}

export function fetchFieldCropKnowledgeProfile(
  query: FieldCropCultivationQuery,
): Promise<FieldCropCultivationProfile> {
  const orgSlug = (import.meta.env.VITE_PUBLIC_ORG_SLUG as string | undefined) ?? 'wsa-demo'
  const params = new URLSearchParams({
    selected_crop_id: query.selectedCropId,
    selected_crop_name: query.selectedCropName,
    selected_category_id: query.selectedCategoryId,
    selected_category_name: query.selectedCategoryName,
    knowledge_option: query.knowledgeOption ?? 'farming-needs',
  })

  if (query.scientificName) {
    params.set('scientific_name', query.scientificName)
  }

  if (orgSlug) {
    params.set('organization', orgSlug)
  }

  return request<FieldCropCultivationProfile>(
    `/public/field-crops/farming-needs-profile?${params.toString()}`,
  )
}

export function fetchFieldCropFarmingNeedsProfile(
  query: FieldCropCultivationQuery,
): Promise<FieldCropCultivationProfile> {
  return fetchFieldCropKnowledgeProfile({ ...query, knowledgeOption: 'farming-needs' })
}
