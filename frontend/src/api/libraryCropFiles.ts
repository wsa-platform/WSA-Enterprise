export type CropLibraryFileRecord = {
  id: number
  title: string
  title_ar: string
  extension: string
  preview_mode: 'inline_browser' | 'download_only'
  file_name: string
}

type CropLibraryFilesResponse = {
  data: CropLibraryFileRecord[]
}

function publicOrgSlug(): string {
  return (import.meta.env.VITE_PUBLIC_ORG_SLUG as string | undefined) ?? 'wsa-demo'
}

export async function fetchCropLibraryFiles(params: {
  plantProductionCategoryId: string
  fieldCropId: string
  libraryFileSection: string
}): Promise<CropLibraryFileRecord[]> {
  const query = new URLSearchParams({
    organization: publicOrgSlug(),
    plant_production_category_id: params.plantProductionCategoryId,
    field_crop_id: params.fieldCropId,
    library_file_section: params.libraryFileSection,
  })

  const response = await fetch(`/api/v1/public/library/crop-files?${query.toString()}`)
  if (!response.ok) {
    throw new Error('crop_library_files_unavailable')
  }

  const payload = (await response.json()) as CropLibraryFilesResponse
  return payload.data ?? []
}

export function buildCropLibraryFileContentUrl(fileId: number): string {
  const query = new URLSearchParams({
    organization: publicOrgSlug(),
  })
  return `/api/v1/public/library/crop-files/${fileId}/content?${query.toString()}`
}
