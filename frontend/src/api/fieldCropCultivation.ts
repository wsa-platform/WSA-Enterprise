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
    | 'scientific_generated'
    | 'insufficient_verified_sources'
    | 'organization_not_found'
  message: string | null
  sections: FieldCropCultivationSection[]
  references: FieldCropCultivationReference[]
  library: {
    item_id: number | null
    slug: string | null
    reused_existing: boolean
    missing_sections_filled: string[]
    scientific_sections_retrieved?: string[]
  }
}

export type FieldCropCultivationQuery = {
  selectedCropId: string
  selectedCropName: string
  selectedCategoryId: string
  selectedCategoryName: string
}

export function fetchFieldCropFarmingNeedsProfile(
  query: FieldCropCultivationQuery,
): Promise<FieldCropCultivationProfile> {
  const orgSlug = (import.meta.env.VITE_PUBLIC_ORG_SLUG as string | undefined) ?? 'wsa-demo'
  const params = new URLSearchParams({
    selected_crop_id: query.selectedCropId,
    selected_crop_name: query.selectedCropName,
    selected_category_id: query.selectedCategoryId,
    selected_category_name: query.selectedCategoryName,
  })

  if (orgSlug) {
    params.set('organization', orgSlug)
  }

  return request<FieldCropCultivationProfile>(
    `/public/field-crops/farming-needs-profile?${params.toString()}`,
  )
}
