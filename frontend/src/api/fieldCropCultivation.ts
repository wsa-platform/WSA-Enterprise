import { request } from './client'

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

export type FieldCropCultivationProfile = {
  crop: {
    id: string
    name: string
    category_id: string
    category_name: string
  }
  service_option: string
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
}

export type FieldCropCultivationQuery = {
  selectedCropId: string
  selectedCropName: string
  selectedCategoryId: string
  selectedCategoryName: string
  knowledgeOption?: string
  scientificName?: string
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
